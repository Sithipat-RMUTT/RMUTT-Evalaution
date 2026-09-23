<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\User;
use common\models\Department;
use backend\models\AdminUserForm;

/**
 * UserController handles system administrator accounts.
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
                        'roles' => ['superadmin', 'admin'],
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
            ->with(['department'])
            ->orderBy(['user.id' => SORT_ASC])
            ->all();

        $canManage = Yii::$app->user->can('superadmin') || Department::isCentralAdmin();

        return $this->render('index', [
            'users' => $users,
            'canManage' => $canManage,
        ]);
    }

    /**
     * Creates a new admin user.
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

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'เพิ่มผู้ดูแลระบบ "' . HtmlEncode($model->username) . '" เรียบร้อยแล้ว');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'departments' => $departments,
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
            Yii::$app->session->setFlash('success', 'อัปเดตข้อมูลผู้ดูแลระบบ "' . HtmlEncode($model->username) . '" เรียบร้อยแล้ว');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'user' => $user,
            'departments' => $departments,
        ]);
    }

    /**
     * Deletes an admin user.
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
            Yii::$app->session->setFlash('danger', 'ไม่สามารถลบบัญชีผู้ดูแลระบบที่กำลังเข้าสู่ระบบอยู่ได้');
            return $this->redirect(['index']);
        }

        if ((int)$id === 1) {
            Yii::$app->session->setFlash('danger', 'ไม่สามารถลบบัญชี Superadmin หลักของระบบได้');
            return $this->redirect(['index']);
        }

        $user = $this->findModel($id);
        $username = $user->username;

        // Revoke auth assignments
        Yii::$app->authManager->revokeAll($user->id);
        $user->delete();

        Yii::$app->session->setFlash('success', 'ลบผู้ดูแลระบบ "' . HtmlEncode($username) . '" เรียบร้อยแล้ว');
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

        $statusText = ($user->status == User::STATUS_ACTIVE) ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';
        Yii::$app->session->setFlash('info', "{$statusText}ผู้ดูแลระบบ \"{$user->username}\" เรียบร้อยแล้ว");
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
        if (!Yii::$app->user->can('superadmin') && !Department::isCentralAdmin()) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ในการจัดการบัญชีผู้ดูแลระบบ (เฉพาะ Superadmin หรือ Central HR เท่านั้น)');
        }
    }
}

function HtmlEncode($str)
{
    return \yii\helpers\Html::encode($str);
}
