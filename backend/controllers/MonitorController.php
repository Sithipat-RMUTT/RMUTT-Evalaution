<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\EvaluationResult;
use common\models\EvaluationAnswer;
use common\models\EvaluationCompetencyAnswer;
use common\models\EvidenceFile;
use common\models\PersonnelType;
use common\models\Department;
use common\services\EvaluationCalculatorService;
use common\models\AuditLog;

/**
 * MonitorController allows HR Admin to track, inspect, recalculate, and manage all evaluations.
 */
class MonitorController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin', 'superadmin', 'division_head'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'recalculate' => ['post'],
                    'admin-return' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $myDepartment = $myDeptId ? Department::findOne($myDeptId) : null;

        $cycleId = Yii::$app->request->get('cycle_id');
        $deptId = Yii::$app->request->get('dept_id');
        $typeId = Yii::$app->request->get('type_id');
        $status = Yii::$app->request->get('status');

        $activeCycle = EvaluationCycle::findOne(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]]);
        if (!$cycleId && $activeCycle) {
            $cycleId = $activeCycle->id;
        }

        $query = Evaluation::find()
            ->with(['personnel.department', 'personnel.position', 'personnel.personnelType', 'evaluator', 'result', 'cycle']);

        if ($cycleId) $query->andWhere(['{{%evaluations}}.evaluation_cycle_id' => $cycleId]);
        if ($status) $query->andWhere(['{{%evaluations}}.status' => $status]);
        
        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
        if (!$isSuperAdmin && $myDeptId) {
            if ($deptId && in_array((int)$deptId, $scopedDeptIds, true)) {
                $query->innerJoinWith('personnel')->andWhere(['{{%personnel}}.department_id' => (int)$deptId]);
            } else {
                $query->innerJoinWith('personnel')->andWhere(['in', '{{%personnel}}.department_id', $scopedDeptIds]);
            }
        } elseif ($deptId) {
            $selectedScoped = Department::getAllScopedDeptIds((int)$deptId);
            $query->innerJoinWith('personnel')->andWhere(['in', '{{%personnel}}.department_id', $selectedScoped]);
        }

        if ($typeId) {
            $query->innerJoinWith('personnel')->andWhere(['{{%personnel}}.personnel_type_id' => $typeId]);
        }

        $evaluations = $query->orderBy(['{{%evaluations}}.updated_at' => SORT_DESC])->all();
        $cycles = EvaluationCycle::find()->orderBy(['period_start' => SORT_DESC, 'id' => SORT_DESC])->all();
        if ($isSuperAdmin) {
            $departments = Department::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
        } elseif ($myDeptId) {
            $subDepts = Department::getScopedDepartments($myDeptId);
            $departments = !empty($subDepts) ? $subDepts : ($myDepartment ? [$myDepartment] : []);
        } else {
            $departments = Department::find()->where(['status' => 1])->all();
        }
        $types = PersonnelType::find()->all();

        return $this->render('index', [
            'evaluations' => $evaluations,
            'cycles' => $cycles,
            'departments' => $departments,
            'types' => $types,
            'cycleId' => $cycleId,
            'deptId' => $deptId,
            'typeId' => $typeId,
            'status' => $status,
        ]);
    }

    public function actionView($id)
    {
        $evaluation = $this->findModel($id);
        $result = $evaluation->result ?: EvaluationCalculatorService::calculate($evaluation);

        $templateVersion = $evaluation->templateVersion;
        $sections = $templateVersion->sections;
        $competencies = $templateVersion->competencyDefinitions;

        $selfAnswers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('evaluation_item_id')->all();
        $supAnswers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'supervisor'])->indexBy('evaluation_item_id')->all();

        $selfCompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('competency_definition_id')->all();
        $supCompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'supervisor'])->indexBy('competency_definition_id')->all();

        $evidenceFiles = EvidenceFile::find()->where(['evaluation_id' => $evaluation->id, 'deleted_at' => null])->all();

        return $this->render('view', [
            'evaluation' => $evaluation,
            'personnel' => $evaluation->personnel,
            'templateVersion' => $templateVersion,
            'sections' => $sections,
            'competencies' => $competencies,
            'selfAnswers' => $selfAnswers,
            'supAnswers' => $supAnswers,
            'selfCompAnswers' => $selfCompAnswers,
            'supCompAnswers' => $supCompAnswers,
            'evidenceFiles' => $evidenceFiles,
            'result' => $result,
        ]);
    }

    public function actionRecalculate($id)
    {
        $evaluation = $this->findModel($id);
        $result = EvaluationCalculatorService::calculate($evaluation, Yii::$app->user->id);

        AuditLog::log('admin_recalculate_evaluation', 'Evaluation', $evaluation->id, null, ['final_percentage' => $result->final_percentage]);
        Yii::$app->session->setFlash('success', "คำนวณคะแนนใหม่เรียบร้อยแล้ว: {$result->final_percentage}% (ระดับ: {$result->performance_level})");

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionAdminReturn($id)
    {
        $evaluation = $this->findModel($id);
        $reason = Yii::$app->request->post('return_reason', 'ผู้ดูแลระบบส่งกลับเพื่อแก้ไขข้อมูล');

        $evaluation->status = Evaluation::STATUS_RETURNED;
        $evaluation->returned_at = date('Y-m-d H:i:s');
        $evaluation->return_reason = $reason;
        $evaluation->save(false);

        AuditLog::log('admin_return_evaluation', 'Evaluation', $evaluation->id, null, ['reason' => $reason]);
        Yii::$app->session->setFlash('warning', "ส่งแบบประเมินของ {$evaluation->personnel->fullName} กลับแก้ไขแล้ว");

        return $this->redirect(['view', 'id' => $id]);
    }

    protected function findModel($id)
    {
        if (($model = Evaluation::findOne($id)) !== null) {
            $isSuperAdmin = Department::isCentralAdmin();
            $currPersonnel = \common\models\Personnel::findOne(['user_id' => Yii::$app->user->id]);
            $myDeptId = $currPersonnel ? $currPersonnel->department_id : null;
            if (!$isSuperAdmin && $myDeptId) {
                $scopedDeptIds = Department::getAllScopedDeptIds($myDeptId);
                if ($model->personnel && !in_array((int)$model->personnel->department_id, $scopedDeptIds, true)) {
                    throw new \yii\web\ForbiddenHttpException('ท่านไม่มีสิทธิ์เข้าถึงแบบประเมินของบุคลากรนอกหน่วยงานของท่าน');
                }
            }
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
