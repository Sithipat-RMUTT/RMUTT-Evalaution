<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\EvaluationCycle;
use common\models\DepartmentEvaluationCycle;
use common\models\CycleTemplateMapping;
use common\models\PersonnelType;
use common\models\EvaluationTemplate;
use common\models\Department;
use common\models\Personnel;
use common\models\Evaluation;
use common\models\AuditLog;

/**
 * CycleController manages evaluation cycles, department-level cycle activation, and template mappings.
 */
class CycleController extends Controller
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
                    'set-active' => ['post'],
                    'close' => ['post'],
                    'department-close' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $isCentral = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $myRootDeptId = DepartmentEvaluationCycle::getRootDeptId($myDeptId);
        $myDepartment = $myRootDeptId ? Department::findOne($myRootDeptId) : null;

        $cycles = EvaluationCycle::find()->orderBy(['fiscal_year' => SORT_DESC, 'cycle_number' => SORT_DESC])->all();
        $activeCycle = EvaluationCycle::find()
            ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        // 1. For Central Admin: query all root departments in the university
        // 2. For Agency Admin: STRICTLY their own department only!
        $rootDepartments = $isCentral
            ? Department::find()->where(['parent_id' => null, 'status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all()
            : ($myDepartment ? [$myDepartment] : []);

        $deptStatuses = [];
        if ($activeCycle) {
            foreach ($rootDepartments as $rDept) {
                $scopedIds = Department::getAllScopedDeptIds($rDept->id);
                $pCount = Personnel::find()->where(['in', 'department_id', $scopedIds])->andWhere(['status' => 10])->count();
                $evalCount = Evaluation::find()
                    ->innerJoinWith('personnel')
                    ->where(['{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id])
                    ->andWhere(['in', '{{%personnel}}.department_id', $scopedIds])
                    ->andWhere(['in', '{{%evaluations}}.status', [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED]])
                    ->count();

                $totalEvals = Evaluation::find()
                    ->innerJoinWith('personnel')
                    ->where(['{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id])
                    ->andWhere(['in', '{{%personnel}}.department_id', $scopedIds])
                    ->count();

                $dCycle = DepartmentEvaluationCycle::getOrCreateRecord($activeCycle->id, $rDept->id);

                // Auto-update to COMPLETED if active and 100% of staff completed
                if ($dCycle->status === DepartmentEvaluationCycle::STATUS_ACTIVE && $pCount > 0 && $evalCount >= $pCount) {
                    $dCycle->status = DepartmentEvaluationCycle::STATUS_COMPLETED;
                    $dCycle->save(false);
                }

                $deptStatuses[$rDept->id] = [
                    'department' => $rDept,
                    'deptCycle' => $dCycle,
                    'totalStaff' => (int)$pCount,
                    'evalCount' => (int)$evalCount,
                    'totalEvals' => (int)$totalEvals,
                    'progressPct' => $pCount > 0 ? round(($evalCount / $pCount) * 100, 1) : ($totalEvals > 0 ? round(($evalCount / $totalEvals) * 100, 1) : 0),
                ];
            }
        }

        // Sub-divisions for Agency Admin (breakdown within their own agency, e.g. IT and Library under ARIT)
        $subDivisionStatuses = [];
        if (!$isCentral && $myDepartment && $activeCycle) {
            $subDepts = Department::find()
                ->where(['parent_id' => $myDepartment->id, 'status' => 1])
                ->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])
                ->all();

            foreach ($subDepts as $sDept) {
                $subStaff = (int)Personnel::find()->where(['department_id' => $sDept->id, 'status' => 10])->count();
                $subEvalDone = (int)Evaluation::find()
                    ->innerJoinWith('personnel')
                    ->where(['{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id])
                    ->andWhere(['{{%personnel}}.department_id' => $sDept->id])
                    ->andWhere(['in', '{{%evaluations}}.status', [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED]])
                    ->count();

                $subDivisionStatuses[] = [
                    'department' => $sDept,
                    'staffCount' => $subStaff,
                    'evalDone' => $subEvalDone,
                    'progressPct' => $subStaff > 0 ? round(($subEvalDone / $subStaff) * 100, 1) : 0,
                ];
            }
        }

        return $this->render('index', [
            'isCentral' => $isCentral,
            'myDepartment' => $myDepartment,
            'myRootDeptId' => $myRootDeptId,
            'cycles' => $cycles,
            'activeCycle' => $activeCycle,
            'rootDepartments' => $rootDepartments,
            'deptStatuses' => $deptStatuses,
            'subDivisionStatuses' => $subDivisionStatuses,
        ]);
    }

    /**
     * Agency Admin configures schedule, name, and opens evaluation cycle for their department.
     * Renders a form similar to the master cycle form where dates and name can be specified.
     * Central Admin is strictly forbidden (Monitor only).
     */
    public function actionDepartmentOpen($cycle_id, $department_id = null)
    {
        $cycle = $this->findModel($cycle_id);
        $isCentral = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();

        if ($isCentral) {
            throw new ForbiddenHttpException('ผู้ดูแลส่วนกลาง (Central Admin / Superadmin) มีหน้าที่ติดตามผล (Monitor) เท่านั้น การเปิดรอบการประเมินจะต้องดำเนินการโดยผู้ดูแลของแต่ละหน่วยงานเอง (Agency Admin)');
        }

        $targetDeptId = (int)$myDeptId;
        if (!$targetDeptId) {
            Yii::$app->session->setFlash('danger', 'ไม่พบข้อมูลหน่วยงานประจำตัวผู้ดูแล');
            return $this->redirect(['index']);
        }

        $rootDeptId = DepartmentEvaluationCycle::getRootDeptId($targetDeptId);
        $targetDept = Department::findOne($rootDeptId);
        if (!$targetDept) {
            throw new NotFoundHttpException('ไม่พบหน่วยงาน');
        }

        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
        if (!in_array($rootDeptId, $scopedDeptIds, true)) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์จัดการรอบการประเมินของหน่วยงานอื่น');
        }

        $deptCycle = DepartmentEvaluationCycle::getOrCreateRecord($cycle->id, $rootDeptId);

        if ($deptCycle->status === DepartmentEvaluationCycle::STATUS_ACTIVE) {
            Yii::$app->session->setFlash('info', "รอบการประเมินสำหรับ '{$targetDept->name_th}' เปิดใช้งานอยู่แล้ว ท่านสามารถปรับปรุงกำหนดการได้ที่หน้านี้");
            return $this->redirect(['department-update', 'cycle_id' => $cycle->id]);
        }

        // Pre-fill defaults from Master Cycle if empty
        if (empty($deptCycle->name_th)) {
            $deptCycle->name_th = "{$cycle->name_th} ({$targetDept->name_th})";
        }
        $deptCycle->period_start = $deptCycle->period_start ?: $cycle->period_start;
        $deptCycle->period_end = $deptCycle->period_end ?: $cycle->period_end;
        $deptCycle->self_assessment_start = $deptCycle->self_assessment_start ?: $cycle->self_assessment_start;
        $deptCycle->self_assessment_end = $deptCycle->self_assessment_end ?: $cycle->self_assessment_end;
        $deptCycle->supervisor_eval_start = $deptCycle->supervisor_eval_start ?: $cycle->supervisor_eval_start;
        $deptCycle->supervisor_eval_end = $deptCycle->supervisor_eval_end ?: $cycle->supervisor_eval_end;

        if (Yii::$app->request->isPost && $deptCycle->load(Yii::$app->request->post())) {
            $deptCycle->status = DepartmentEvaluationCycle::STATUS_ACTIVE;
            $deptCycle->opened_at = time();
            $deptCycle->opened_by = Yii::$app->user->id;

            if ($deptCycle->save()) {
                AuditLog::log('open_department_evaluation_cycle', 'DepartmentEvaluationCycle', $deptCycle->id);
                Yii::$app->session->setFlash('success', "เปิดรอบการประเมิน '{$deptCycle->getEffectiveName()}' เรียบร้อยแล้ว! ระบบได้ทำการล็อกโครงสร้างแบบประเมินถาวร และเปิดให้บุคลากรเข้าทำแบบประเมินตนเองตามกำหนดการแล้ว");
                return $this->redirect(['index']);
            }
        }

        // Format datetime-local fields for HTML input
        foreach (['self_assessment_start', 'self_assessment_end', 'supervisor_eval_start', 'supervisor_eval_end'] as $field) {
            if (!empty($deptCycle->$field)) {
                $deptCycle->$field = date('Y-m-d\TH:i', strtotime((string)$deptCycle->$field));
            }
        }

        return $this->render('department_open', [
            'cycle' => $cycle,
            'deptCycle' => $deptCycle,
            'targetDept' => $targetDept,
        ]);
    }

    /**
     * Agency Admin updates schedule dates and notes for their department's active evaluation cycle.
     */
    public function actionDepartmentUpdate($cycle_id, $department_id = null)
    {
        $cycle = $this->findModel($cycle_id);
        $isCentral = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();

        if ($isCentral) {
            throw new ForbiddenHttpException('ผู้ดูแลส่วนกลาง (Central Admin / Superadmin) มีหน้าที่ติดตามผล (Monitor) เท่านั้น การแก้ไขกำหนดการจะต้องดำเนินการโดยผู้ดูแลของแต่ละหน่วยงานเอง (Agency Admin)');
        }

        $targetDeptId = (int)$myDeptId;
        if (!$targetDeptId) {
            Yii::$app->session->setFlash('danger', 'ไม่พบข้อมูลหน่วยงานประจำตัวผู้ดูแล');
            return $this->redirect(['index']);
        }

        $rootDeptId = DepartmentEvaluationCycle::getRootDeptId($targetDeptId);
        $targetDept = Department::findOne($rootDeptId);
        if (!$targetDept) {
            throw new NotFoundHttpException('ไม่พบหน่วยงาน');
        }

        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
        if (!in_array($rootDeptId, $scopedDeptIds, true)) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์จัดการรอบการประเมินของหน่วยงานอื่น');
        }

        $deptCycle = DepartmentEvaluationCycle::getOrCreateRecord($cycle->id, $rootDeptId);

        if (Yii::$app->request->isPost && $deptCycle->load(Yii::$app->request->post())) {
            if ($deptCycle->save()) {
                AuditLog::log('update_department_evaluation_cycle', 'DepartmentEvaluationCycle', $deptCycle->id);
                Yii::$app->session->setFlash('success', "แก้ไขกำหนดการรอบการประเมินสำหรับ '{$targetDept->name_th}' เรียบร้อยแล้ว");
                return $this->redirect(['index']);
            }
        }

        foreach (['self_assessment_start', 'self_assessment_end', 'supervisor_eval_start', 'supervisor_eval_end'] as $field) {
            if (!empty($deptCycle->$field)) {
                $deptCycle->$field = date('Y-m-d\TH:i', strtotime((string)$deptCycle->$field));
            }
        }

        return $this->render('department_update', [
            'cycle' => $cycle,
            'deptCycle' => $deptCycle,
            'targetDept' => $targetDept,
        ]);
    }

    /**
     * Agency Admin closes evaluation cycle for a specific department.
     * Central Admin is strictly forbidden (Monitor only).
     */
    public function actionDepartmentClose($cycle_id, $department_id = null)
    {
        $cycle = $this->findModel($cycle_id);
        $isCentral = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();

        if ($isCentral) {
            throw new ForbiddenHttpException('ผู้ดูแลส่วนกลาง (Central Admin / Superadmin) มีหน้าที่ติดตามผล (Monitor) เท่านั้น การปิดรอบการประเมินจะต้องดำเนินการโดยผู้ดูแลของแต่ละหน่วยงานเอง (Agency Admin)');
        }

        $targetDeptId = (int)$myDeptId;
        if (!$targetDeptId) {
            Yii::$app->session->setFlash('danger', 'ไม่พบข้อมูลหน่วยงานประจำตัวผู้ดูแล');
            return $this->redirect(['index']);
        }

        $rootDeptId = DepartmentEvaluationCycle::getRootDeptId($targetDeptId);
        $targetDept = Department::findOne($rootDeptId);
        if (!$targetDept) {
            throw new NotFoundHttpException('ไม่พบหน่วยงาน');
        }

        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
        if (!in_array($rootDeptId, $scopedDeptIds, true)) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์จัดการรอบการประเมินของหน่วยงานอื่น');
        }

        DepartmentEvaluationCycle::closeDepartmentCycle($cycle->id, $rootDeptId, Yii::$app->user->id);

        Yii::$app->session->setFlash('info', "ปิดรอบการประเมินสำหรับ '{$targetDept->name_th}' เรียบร้อยแล้ว");

        $returnUrl = Yii::$app->request->referrer ?: ['index'];
        return $this->redirect($returnUrl);
    }

    public function actionCreate()
    {
        $model = new EvaluationCycle();
        $model->fiscal_year = date('Y') + 543;
        $model->cycle_number = 1;
        $model->period_start = date('Y') . '-10-01';
        $model->period_end = (date('Y') + 1) . '-03-31';
        $model->self_assessment_start = date('Y-m-d 08:30:00');
        $model->self_assessment_end = date('Y-m-d 16:30:00', strtotime('+30 days'));
        $model->supervisor_eval_start = date('Y-m-d 08:30:00', strtotime('+15 days'));
        $model->supervisor_eval_end = date('Y-m-d 16:30:00', strtotime('+45 days'));
        $model->status = EvaluationCycle::STATUS_DRAFT;

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if ($model->save()) {
                AuditLog::log('create_evaluation_cycle', 'EvaluationCycle', $model->id);

                // Auto-map latest templates for all types and departments
                $templates = EvaluationTemplate::find()->where(['status' => 1])->all();
                foreach ($templates as $tmpl) {
                    if ($tmpl->activeVersion) {
                        $m = new CycleTemplateMapping();
                        $m->evaluation_cycle_id = $model->id;
                        $m->department_id = $tmpl->department_id;
                        $m->personnel_type_id = $tmpl->personnel_type_id;
                        $m->template_version_id = $tmpl->activeVersion->id;
                        $m->created_at = time();
                        $m->save(false);
                    }
                }

                // Initialize department cycles as pending
                $rootDepts = Department::find()->where(['parent_id' => null, 'status' => 1])->all();
                foreach ($rootDepts as $rd) {
                    DepartmentEvaluationCycle::getOrCreateRecord($model->id, $rd->id);
                }

                Yii::$app->session->setFlash('success', 'สร้างรอบการประเมินใหม่เรียบร้อยแล้ว');
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

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            AuditLog::log('update_evaluation_cycle', 'EvaluationCycle', $model->id);
            Yii::$app->session->setFlash('success', 'แก้ไขข้อมูลรอบการประเมินเรียบร้อยแล้ว');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionSetActive($id)
    {
        $model = $this->findModel($id);
        $tx = Yii::$app->db->beginTransaction();
        try {
            // Deactivate all other active cycles
            EvaluationCycle::updateAll(
                ['status' => EvaluationCycle::STATUS_CLOSED],
                ['and', ['status' => EvaluationCycle::STATUS_ACTIVE], ['!=', 'id', $model->id]]
            );

            $model->status = EvaluationCycle::STATUS_ACTIVE;
            $model->save(false);

            AuditLog::log('activate_evaluation_cycle', 'EvaluationCycle', $model->id);
            $tx->commit();
            Yii::$app->session->setFlash('success', "เปิดใช้งานรอบการประเมิน {$model->name_th} เรียบร้อยแล้ว (ปิดรอบ active เดิมโดยอัตโนมัติ)");
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการเปิดใช้งานรอบการประเมิน: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    public function actionClose($id)
    {
        $model = $this->findModel($id);
        $model->status = EvaluationCycle::STATUS_CLOSED;
        $model->save(false);

        AuditLog::log('close_evaluation_cycle', 'EvaluationCycle', $model->id);
        Yii::$app->session->setFlash('info', "ปิดรอบการประเมิน {$model->name_th} เรียบร้อยแล้ว");
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = EvaluationCycle::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
