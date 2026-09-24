<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\User;
use common\models\Personnel;
use common\models\Department;
use common\models\AuditLog;
use backend\models\AdminUserForm;

/**
 * UserController handles system administrator accounts.
 * Accessible exclusively by superadmin users.
 */
class UserController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['superadmin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'toggle-status' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all system admin users.
     *
     * @return string
     */
    public function actionIndex()
    {
        $users = User::find()
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
            ->where(['in', 'auth_assignment.item_name', ['superadmin', 'admin']])
            ->with(['department', 'personnel.department', 'personnel.position'])
            ->orderBy(['user.id' => SORT_ASC])
            ->all();

        $canManage = Yii::$app->user->can('superadmin');

        return $this->render('index', [
            'users' => $users,
            'canManage' => $canManage,
        ]);
    }

    /**
     * Creates a new admin user (appointing existing personnel or standalone).
     *
     * @return string|\yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionCreate()
    {
        $this->ensureCanManage();

        $model = new AdminUserForm();
        $departments = Department::find()
            ->where(['status' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])
            ->all();

        // Get personnel eligible to be appointed as admin (active, and not already admin/superadmin)
        $assignedUserIds = (new \yii\db\Query())
            ->select('user_id')
            ->from('auth_assignment')
            ->where(['in', 'item_name', ['superadmin', 'admin']])
            ->column();

        $eligiblePersonnel = Personnel::find()
            ->with(['department', 'position', 'user'])
            ->where(['status' => 10])
            ->andWhere(['not in', 'user_id', $assignedUserIds])
            ->orderBy(['first_name_th' => SORT_ASC, 'last_name_th' => SORT_ASC])
            ->all();

        if ($model->load(Yii::$app->request->post())) {
            $savedUser = $model->save();
            if ($savedUser) {
                if ($model->create_mode === AdminUserForm::MODE_APPOINT && $savedUser->personnel) {
                    Yii::$app->session->setFlash('success', 'แต่งตั้ง "' . HtmlEncode($savedUser->personnel->fullName) . '" เป็นผู้ดูแลระบบเรียบร้อยแล้ว บุคลากรสามารถใช้บัญชีเดิมเข้าสู่ระบบหลังบ้านได้ทันที');
                } else {
                    Yii::$app->session->setFlash('success', 'เพิ่มผู้ดูแลระบบ "' . HtmlEncode($savedUser->username) . '" เรียบร้อยแล้ว');
                }
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'departments' => $departments,
            'eligiblePersonnel' => $eligiblePersonnel,
        ]);
    }

    /**
     * Updates an existing admin user.
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionUpdate($id)
    {
        $this->ensureCanManage();

        $user = $this->findModel($id);
        $model = new AdminUserForm();
        $model->loadUser($user);

        $departments = Department::find()
            ->where(['status' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])
            ->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $displayName = $user->personnel ? $user->personnel->fullName : $model->username;
            Yii::$app->session->setFlash('success', 'อัปเดตข้อมูลผู้ดูแลระบบ "' . HtmlEncode($displayName) . '" เรียบร้อยแล้ว');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'user' => $user,
            'departments' => $departments,
        ]);
    }

    /**
     * Deletes a standalone admin user, or revokes admin role if linked to a personnel.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $this->ensureCanManage();

        if ((int)$id === (int)Yii::$app->user->id) {
            Yii::$app->session->setFlash('danger', 'ไม่สามารถลบหรือถอดถอนสิทธิ์บัญชีผู้ดูแลระบบที่กำลังเข้าสู่ระบบอยู่ได้');
            return $this->redirect(['index']);
        }

        if ((int)$id === 1) {
            Yii::$app->session->setFlash('danger', 'ไม่สามารถลบหรือถอดถอนสิทธิ์บัญชี Superadmin หลักของระบบได้');
            return $this->redirect(['index']);
        }

        $user = $this->findModel($id);
        $username = $user->username;
        $personnel = $user->personnel;
        $auth = Yii::$app->authManager;

        if ($personnel) {
            // Revoke admin and superadmin roles, safely preserving personnel and user record
            $superRole = $auth->getRole('superadmin');
            $adminRole = $auth->getRole('admin');
            if ($superRole) $auth->revoke($superRole, $user->id);
            if ($adminRole) $auth->revoke($adminRole, $user->id);

            // Restore base role if none remain
            $remainingRoles = $auth->getRolesByUser($user->id);
            if (empty($remainingRoles)) {
                $baseRole = $personnel->is_supervisor ? $auth->getRole('supervisor') : $auth->getRole('personnel');
                if ($baseRole) {
                    $auth->assign($baseRole, $user->id);
                }
            }

            AuditLog::log('revoke_admin_role', 'User', $user->id, [
                'personnel_id' => $personnel->id,
                'name' => $personnel->fullName,
            ]);

            Yii::$app->session->setFlash('success', 'ถอดถอนสิทธิ์ผู้ดูแลระบบของ "' . HtmlEncode($personnel->fullName) . '" เรียบร้อยแล้ว (บัญชีและประวัติบุคลากรยังคงใช้งานได้ตามปกติ)');
        } else {
            // Standalone admin account without linked personnel
            $auth->revokeAll($user->id);
            $user->delete();

            AuditLog::log('delete_admin_user', 'User', $id, [
                'username' => $username,
            ]);

            Yii::$app->session->setFlash('success', 'ลบบัญชีผู้ดูแลระบบเฉพาะกิจ "' . HtmlEncode($username) . '" เรียบร้อยแล้ว');
        }

        return $this->redirect(['index']);
    }

    /**
     * Toggles active/inactive status.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionToggleStatus($id)
    {
        $this->ensureCanManage();

        if ((int)$id === (int)Yii::$app->user->id) {
            Yii::$app->session->setFlash('danger', 'ไม่สามารถปิดการใช้งานบัญชีที่กำลังเข้าสู่ระบบอยู่ได้');
            return $this->redirect(['index']);
        }

        if ((int)$id === 1) {
            Yii::$app->session->setFlash('danger', 'ไม่สามารถปิดการใช้งานบัญชี Superadmin หลักได้');
            return $this->redirect(['index']);
        }

        $user = $this->findModel($id);
        $user->status = ($user->status == User::STATUS_ACTIVE) ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
        $user->save(false);

        $name = $user->personnel ? $user->personnel->fullName : $user->username;
        $statusText = ($user->status == User::STATUS_ACTIVE) ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';
        Yii::$app->session->setFlash('info', "{$statusText}ผู้ดูแลระบบ \"{$name}\" เรียบร้อยแล้ว");
        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     *
     * @param int $id
     * @return User
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('ไม่พบบัญชีผู้ใช้งานที่ระบุ');
    }

    /**
     * Ensures the current user has permission to manage admin users.
     *
     * @throws ForbiddenHttpException
     */
    protected function ensureCanManage()
    {
        if (!Yii::$app->user->can('superadmin')) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ในการจัดการบัญชีผู้ดูแลระบบ (เฉพาะ Superadmin เท่านั้น)');
        }
    }
}

function HtmlEncode($str)
{
    return \yii\helpers\Html::encode($str);
}
