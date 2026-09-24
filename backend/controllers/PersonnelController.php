<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\Personnel;
use common\models\PersonnelType;
use common\models\Department;
use common\models\Position;
use common\models\User;
use common\models\AuditLog;
use common\services\UniversitySyncService;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\web\ForbiddenHttpException;

/**
 * PersonnelController manages personnel records, supervisor hierarchy, and assignments.
 */
class PersonnelController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin', 'superadmin', 'central_hr'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = Personnel::find()->with(['personnelType', 'department.parent', 'position', 'supervisor']);
        
        $typeId = Yii::$app->request->get('type_id');
        $orgId = Yii::$app->request->get('org_id');
        $deptId = Yii::$app->request->get('dept_id');
        $search = Yii::$app->request->get('search');

        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $myDepartment = $myDeptId ? Department::findOne($myDeptId) : null;
        $myRootOrgId = $myDepartment ? ($myDepartment->parent_id ?: $myDepartment->id) : null;
        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

        // 1. Department / Organization scoping
        if (!$isSuperAdmin && $myDeptId) {
            // Non-central admin (e.g. admin_arit) is strictly restricted to their own organization & sub-divisions
            $orgId = $myRootOrgId;
            if ($deptId && in_array((int)$deptId, $scopedDeptIds, true)) {
                $query->andWhere(['{{%personnel}}.department_id' => (int)$deptId]);
            } else {
                $query->andWhere(['in', '{{%personnel}}.department_id', $scopedDeptIds]);
            }
        } else {
            // Superadmin or Central HR Admin can view all or filter by specific division/org
            if ($deptId) {
                $query->andWhere(['{{%personnel}}.department_id' => (int)$deptId]);
            } elseif ($orgId) {
                $orgScopedIds = Department::getAllScopedDeptIds((int)$orgId);
                $query->andWhere(['in', '{{%personnel}}.department_id', $orgScopedIds]);
            }
        }

        // 2. Personnel type filter
        if ($typeId) {
            $query->andWhere(['{{%personnel}}.personnel_type_id' => (int)$typeId]);
        }

        // 3. Search query
        if ($search) {
            $cleanSearch = trim($search);
            $query->leftJoin('{{%positions}}', '{{%positions}}.id = {{%personnel}}.position_id');
            $query->andWhere([
                'or',
                ['like', '{{%personnel}}.employee_code', $cleanSearch],
                ['like', '{{%personnel}}.first_name_th', $cleanSearch],
                ['like', '{{%personnel}}.last_name_th', $cleanSearch],
                ['like', '{{%personnel}}.email', $cleanSearch],
                ['like', '{{%personnel}}.phone', $cleanSearch],
                ['like', '{{%positions}}.name_th', $cleanSearch],
                ['like', new \yii\db\Expression("CONCAT(COALESCE({{%personnel}}.first_name_th, ''), ' ', COALESCE({{%personnel}}.last_name_th, ''))"), $cleanSearch],
                ['like', new \yii\db\Expression("CONCAT(COALESCE({{%personnel}}.prefix_th, ''), COALESCE({{%personnel}}.first_name_th, ''), ' ', COALESCE({{%personnel}}.last_name_th, ''))"), $cleanSearch],
                ['like', new \yii\db\Expression("CONCAT(COALESCE({{%personnel}}.prefix_th, ''), ' ', COALESCE({{%personnel}}.first_name_th, ''), ' ', COALESCE({{%personnel}}.last_name_th, ''))"), $cleanSearch],
            ]);
        }

        $personnelList = $query->orderBy(['{{%personnel}}.id' => SORT_ASC])->all();
        $types = PersonnelType::find()->all();

        // 4. Fetch Organizations and Divisions for separate dropdowns
        if ($isSuperAdmin) {
            $organizations = Department::find()
                ->where(['parent_id' => null, 'status' => 1])
                ->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])
                ->all();

            $divisionsQuery = Department::find()
                ->where(['status' => 1])
                ->andWhere(['not', ['parent_id' => null]])
                ->orderBy(['parent_id' => SORT_ASC, 'sort_order' => SORT_ASC, 'name_th' => SORT_ASC]);

            if ($orgId) {
                $divisionsQuery->andWhere(['parent_id' => (int)$orgId]);
            }
            $divisions = $divisionsQuery->all();
        } else {
            // Strictly limited to this admin's organization and its divisions
            $organizations = $myRootOrgId 
                ? Department::find()->where(['id' => $myRootOrgId, 'status' => 1])->all() 
                : [];
            $divisions = $myRootOrgId 
                ? Department::find()->where(['parent_id' => $myRootOrgId, 'status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all() 
                : [];
        }

        return $this->render('index', [
            'personnelList' => $personnelList,
            'types' => $types,
            'organizations' => $organizations,
            'divisions' => $divisions,
            'isSuperAdmin' => $isSuperAdmin,
            'typeId' => $typeId,
            'orgId' => $orgId,
            'deptId' => $deptId,
            'search' => $search,
        ]);
    }

    public function actionCreate()
    {
        $model = new Personnel();
        $model->status = 10;
        $model->is_supervisor = 0;

        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

        if ($model->load(Yii::$app->request->post())) {
            if (!$isSuperAdmin && $myDeptId) {
                if (!in_array((int)$model->department_id, $scopedDeptIds, true)) {
                    throw new \yii\web\ForbiddenHttpException('ไม่อนุญาตให้สร้างบุคลากรนอกสังกัดที่รับผิดชอบ');
                }
            }

            // Create user account if not exists
            $username = Yii::$app->request->post('username');
            $password = Yii::$app->request->post('password', 'password123');

            $user = User::findOne(['username' => $username]);
            if (!$user) {
                $user = new User();
                $user->username = $username;
                $user->email = $model->email ?: "{$username}@rmutt.ac.th";
                $user->setPassword($password);
                $user->generateAuthKey();
                $user->status = User::STATUS_ACTIVE;
                $user->save(false);

                // Assign RBAC
                $auth = Yii::$app->authManager;
                $role = $model->is_supervisor ? $auth->getRole('supervisor') : $auth->getRole('personnel');
                if ($role) $auth->assign($role, $user->id);
            }

            $model->user_id = $user->id;
            if ($model->save()) {
                AuditLog::log('create_personnel', 'Personnel', $model->id);
                Yii::$app->session->setFlash('success', 'เพิ่มข้อมูลบุคลากรเรียบร้อยแล้ว');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

        if ($model->load(Yii::$app->request->post())) {
            if (!$isSuperAdmin && $myDeptId) {
                if (!in_array((int)$model->department_id, $scopedDeptIds, true)) {
                    throw new \yii\web\ForbiddenHttpException('ไม่อนุญาตให้ย้ายบุคลากรไปยังสังกัดนอกความรับผิดชอบ');
                }
            }
            if ($model->save()) {
                AuditLog::log('update_personnel', 'Personnel', $model->id);
                Yii::$app->session->setFlash('success', 'บันทึกการแก้ไขข้อมูลบุคลากรเรียบร้อยแล้ว');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionHierarchy()
    {
        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $myDept = $myDeptId ? Department::findOne($myDeptId) : null;

        // 1. Determine Current Organization (Parent Department or Self if root)
        $orgId = Yii::$app->request->get('org_id');
        if (!$orgId) {
            if ($myDept) {
                $orgId = $myDept->parent_id ?: $myDept->id;
            } else {
                $arit = Department::findOne(['code' => 'ARIT']);
                $orgId = $arit ? $arit->id : 1;
            }
        }
        $currentOrg = Department::findOne((int)$orgId);
        if (!$currentOrg) {
            $currentOrg = Department::find()->where(['parent_id' => null, 'status' => 1])->orderBy(['sort_order' => SORT_ASC])->one();
            $orgId = $currentOrg ? $currentOrg->id : null;
        }

        // 2. Fetch Divisions inside this organization
        $divisions = Department::find()
            ->where(['parent_id' => $orgId, 'status' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])
            ->all();

        // If org has no sub-departments, treat the org itself as the single division
        if (empty($divisions) && $currentOrg) {
            $divisions = [$currentOrg];
        }

        $divIds = \yii\helpers\ArrayHelper::getColumn($divisions, 'id');
        if ($currentOrg && !in_array($currentOrg->id, $divIds, true)) {
            $divIds[] = $currentOrg->id;
        }

        // 3. Filter by Selected Division and Work Unit
        $selectedDivId = Yii::$app->request->get('dept_id');
        $selectedWorkUnit = Yii::$app->request->get('work_unit');

        // Query available work units in this division or org
        $unitQuery = Personnel::find()
            ->select('work_unit')
            ->distinct()
            ->where(['is not', 'work_unit', null])
            ->andWhere(['!=', 'work_unit', '']);

        if ($selectedDivId && $selectedDivId !== 'all') {
            $unitQuery->andWhere(['department_id' => (int)$selectedDivId]);
        } else {
            $unitQuery->andWhere(['in', 'department_id', $divIds]);
        }
        $workUnits = $unitQuery->orderBy(['work_unit' => SORT_ASC])->column();

        $query = Personnel::find()->with(['personnelType', 'department', 'position', 'supervisor', 'divisionHead']);

        if ($selectedDivId && $selectedDivId !== 'all') {
            $query->andWhere(['department_id' => (int)$selectedDivId]);
            $currentDept = Department::findOne((int)$selectedDivId);
        } else {
            $query->andWhere(['in', 'department_id', $divIds]);
            $currentDept = null;
        }

        if ($selectedWorkUnit && $selectedWorkUnit !== 'all') {
            $query->andWhere(['work_unit' => $selectedWorkUnit]);
            $currentWorkUnit = $selectedWorkUnit;
        } else {
            $currentWorkUnit = null;
        }

        $personnelList = $query->orderBy(['position_level' => SORT_DESC, 'id' => SORT_ASC])->all();

        // 4. All main organizations (for Superadmin switcher if needed)
        $allOrgs = $isSuperAdmin 
            ? Department::find()->where(['parent_id' => null, 'status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all()
            : ($currentOrg ? [$currentOrg] : []);

        return $this->render('hierarchy', [
            'personnelList' => $personnelList,
            'departments' => $divisions, // sub-departments / divisions inside current org
            'currentDept' => $currentDept, // currently filtered division (or null for all)
            'currentOrg' => $currentOrg, // parent organization
            'allOrgs' => $allOrgs,
            'workUnits' => $workUnits, // work units / sections in current division
            'currentWorkUnit' => $currentWorkUnit, // currently filtered work unit (or null for all)
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function actionQuickAssign()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $id = $request->post('personnel_id');
        $supervisorId = $request->post('supervisor_id') ?: null;
        $divisionHeadId = $request->post('division_head_id') ?: null;
        $positionLevel = $request->post('position_level', 'staff');
        $departmentId = $request->post('department_id');
        $workUnit = $request->post('work_unit');

        $personnel = $this->findModel($id);

        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

        if (!$isSuperAdmin && $myDeptId && !in_array((int)$personnel->department_id, $scopedDeptIds, true)) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์จัดการบุคลากรนอกหน่วยงานของคุณ');
        }

        if (!empty($departmentId)) {
            $targetDeptId = (int)$departmentId;
            if (!$isSuperAdmin && $myDeptId && !in_array($targetDeptId, $scopedDeptIds, true)) {
                throw new ForbiddenHttpException('ไม่อนุญาตให้ย้ายบุคลากรไปยังสังกัดนอกความรับผิดชอบ');
            }
            $personnel->department_id = $targetDeptId;
        }
        if ($workUnit !== null) {
            $personnel->work_unit = !empty($workUnit) ? trim($workUnit) : null;
        }
        $personnel->supervisor_id = $supervisorId;
        $personnel->division_head_id = $divisionHeadId;
        $personnel->position_level = $positionLevel;
        $personnel->is_supervisor = in_array($positionLevel, ['section_head', 'division_head', 'director'], true) ? 1 : 0;

        if ($personnel->save(false)) {
            // Also update any active draft/self_assessment evaluations to reflect this hierarchy
            $db = Yii::$app->db;
            $db->createCommand("
                UPDATE {{%evaluations}}
                SET evaluator_id = :sup,
                    evaluator_l1_id = :sup,
                    evaluator_l2_id = :div
                WHERE personnel_id = :pid
                  AND status IN ('draft', 'self_assessment')
            ", [
                ':sup' => $supervisorId ?: $divisionHeadId,
                ':div' => $divisionHeadId,
                ':pid' => $personnel->id,
            ])->execute();

            AuditLog::log('assign_hierarchy', 'Personnel', $personnel->id, null, [
                'supervisor_id' => $supervisorId,
                'division_head_id' => $divisionHeadId,
                'position_level' => $positionLevel,
            ]);

            return [
                'success' => true,
                'message' => "บันทึกสายการประเมินของ {$personnel->fullName} เรียบร้อยแล้ว",
            ];
        }

        return ['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้'];
    }

    public function actionImport()
    {
        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();

        $targetDeptId = Yii::$app->request->post('target_dept_id', $myDeptId);
        if (!$isSuperAdmin && $myDeptId) {
            $targetDeptId = $myDeptId;
        }

        if (Yii::$app->request->isPost) {
            $file = UploadedFile::getInstanceByName('csv_file');
            if ($file) {
                $tempPath = $file->tempName;
                $result = UniversitySyncService::importFromCsv($tempPath, $targetDeptId ? (int)$targetDeptId : null);

                if ($result['success'] > 0) {
                    Yii::$app->session->setFlash('success', "นำเข้าข้อมูลสำเร็จ {$result['success']} รายการ (จากทั้งหมด {$result['total']} รายการ)");
                }
                if ($result['failed'] > 0) {
                    $errText = implode('<br>', array_slice($result['errors'], 0, 5));
                    Yii::$app->session->setFlash('warning', "มีข้อมูลที่ไม่สามารถนำเข้าได้ {$result['failed']} รายการ:<br>{$errText}");
                }
                return $this->redirect(['hierarchy', 'dept_id' => 'all']);
            } else {
                Yii::$app->session->setFlash('error', 'กรุณาเลือกไฟล์ CSV ที่ต้องการนำเข้า');
            }
        }

        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
        $departments = $isSuperAdmin 
            ? Department::find()->all() 
            : Department::find()->where(['id' => $scopedDeptIds, 'status' => 1])->all();

        return $this->render('import', [
            'departments' => $departments,
            'targetDeptId' => $targetDeptId,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Personnel::findOne($id)) !== null) {
            $isSuperAdmin = Yii::$app->user->can('superadmin') || Department::isCentralAdmin();
            if (!$isSuperAdmin) {
                $myDeptId = Department::getCurrentUserDeptId();
                if ($myDeptId) {
                    $scopedDeptIds = Department::getAllScopedDeptIds($myDeptId);
                    if ($model->department_id && !in_array((int)$model->department_id, $scopedDeptIds, true)) {
                        throw new ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าถึงหรือแก้ไขข้อมูลบุคลากรนอกสังกัดที่รับผิดชอบ');
                    }
                }
            }
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
