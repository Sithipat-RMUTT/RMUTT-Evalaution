<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\EvaluationResult;
use common\models\Department;
use common\models\PersonnelType;

/**
 * ReportController generates summary reports and export tables.
 */
class ReportController extends Controller
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
        ];
    }

    public function actionIndex()
    {
        $cycleId = Yii::$app->request->get('cycle_id');
        $deptId = Yii::$app->request->get('dept_id');
        $activeCycle = EvaluationCycle::findOne(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]]);
        if (!$cycleId && $activeCycle) {
            $cycleId = $activeCycle->id;
        }

        $cycles = EvaluationCycle::find()->orderBy(['fiscal_year' => SORT_DESC])->all();

        $isSuperadmin = Department::isCentralAdmin();
        $currentPersonnel = \common\models\Personnel::findOne(['user_id' => Yii::$app->user->id]);
        $userDeptId = $currentPersonnel ? $currentPersonnel->department_id : null;
        $scopedDeptIds = $userDeptId ? Department::getAllScopedDeptIds($userDeptId) : [];
        $isScoped = !$isSuperadmin && !empty($scopedDeptIds);

        // Department list for filter dropdown
        if ($isSuperadmin) {
            $departments = Department::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
        } else {
            $departments = Department::find()->where(['status' => 1, 'id' => $scopedDeptIds])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
        }
        $types = PersonnelType::find()->all();

        // Determine effective department filter IDs
        $filterDeptIds = [];
        if ($isScoped) {
            if ($deptId && in_array((int)$deptId, $scopedDeptIds, true)) {
                $filterDeptIds = Department::getAllScopedDeptIds((int)$deptId);
            } else {
                $filterDeptIds = $scopedDeptIds;
            }
        } elseif ($deptId) {
            $filterDeptIds = Department::getAllScopedDeptIds((int)$deptId);
        }

        // 1. Department Summary Stats
        $deptStats = [];
        $deptListForStats = $departments;
        if (!empty($filterDeptIds) && ($deptId || $isScoped)) {
            $deptListForStats = Department::find()->where(['id' => $filterDeptIds, 'status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
        }

        foreach ($deptListForStats as $dept) {
            $totalInDept = \common\models\Personnel::find()->where(['department_id' => $dept->id, 'status' => 10])->count();
            
            $completedInDept = Evaluation::find()
                ->innerJoinWith('personnel')
                ->where([
                    '{{%evaluations}}.evaluation_cycle_id' => $cycleId,
                    '{{%personnel}}.department_id' => $dept->id,
                    '{{%evaluations}}.status' => [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED],
                ])
                ->count();

            $avgScore = EvaluationResult::find()
                ->innerJoin('{{%evaluations}}', '{{%evaluations}}.id = {{%evaluation_results}}.evaluation_id')
                ->innerJoin('{{%personnel}}', '{{%personnel}}.id = {{%evaluations}}.personnel_id')
                ->where([
                    '{{%evaluations}}.evaluation_cycle_id' => $cycleId,
                    '{{%personnel}}.department_id' => $dept->id,
                    '{{%evaluations}}.status' => [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED],
                ])
                ->average('final_percentage');

            $deptStats[] = [
                'department' => $dept,
                'total' => $totalInDept,
                'completed' => $completedInDept,
                'avg_score' => $avgScore ? round($avgScore, 2) : 0,
            ];
        }

        // 2. Personnel Type Summary Stats
        $typeStats = [];
        foreach ($types as $t) {
            $typePersonnelQuery = \common\models\Personnel::find()->where(['personnel_type_id' => $t->id, 'status' => 10]);
            if (!empty($filterDeptIds)) {
                $typePersonnelQuery->andWhere(['in', 'department_id', $filterDeptIds]);
            }
            $totalInType = $typePersonnelQuery->count();

            $evalTypeQuery = Evaluation::find()
                ->innerJoinWith('personnel')
                ->where([
                    '{{%evaluations}}.evaluation_cycle_id' => $cycleId,
                    '{{%personnel}}.personnel_type_id' => $t->id,
                    '{{%evaluations}}.status' => [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED],
                ]);
            if (!empty($filterDeptIds)) {
                $evalTypeQuery->andWhere(['in', '{{%personnel}}.department_id', $filterDeptIds]);
            }
            $completedInType = $evalTypeQuery->count();

            $avgTypeScoreQuery = EvaluationResult::find()
                ->innerJoin('{{%evaluations}}', '{{%evaluations}}.id = {{%evaluation_results}}.evaluation_id')
                ->innerJoin('{{%personnel}}', '{{%personnel}}.id = {{%evaluations}}.personnel_id')
                ->where([
                    '{{%evaluations}}.evaluation_cycle_id' => $cycleId,
                    '{{%personnel}}.personnel_type_id' => $t->id,
                    '{{%evaluations}}.status' => [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED],
                ]);
            if (!empty($filterDeptIds)) {
                $avgTypeScoreQuery->andWhere(['in', '{{%personnel}}.department_id', $filterDeptIds]);
            }
            $avgScore = $avgTypeScoreQuery->average('final_percentage');

            $typeStats[] = [
                'type' => $t,
                'total' => $totalInType,
                'completed' => $completedInType,
                'avg_score' => $avgScore ? round($avgScore, 2) : 0,
            ];
        }

        // 3. Individual Personnel Scores List
        $individualQuery = Evaluation::find()
            ->innerJoinWith('personnel')
            ->with(['personnel.department', 'personnel.position', 'personnel.personnelType', 'result', 'evaluator'])
            ->where(['{{%evaluations}}.evaluation_cycle_id' => $cycleId]);

        if (!empty($filterDeptIds)) {
            $individualQuery->andWhere(['in', '{{%personnel}}.department_id', $filterDeptIds]);
        }

        $individualEvaluations = $individualQuery->orderBy([
            '{{%personnel}}.department_id' => SORT_ASC,
            '{{%personnel}}.first_name_th' => SORT_ASC,
        ])->all();

        return $this->render('index', [
            'cycles' => $cycles,
            'cycleId' => $cycleId,
            'deptId' => $deptId,
            'departments' => $departments,
            'deptStats' => $deptStats,
            'typeStats' => $typeStats,
            'individualEvaluations' => $individualEvaluations,
            'isSuperadmin' => $isSuperadmin,
        ]);
    }

    public function actionExportCsv($cycle_id, $dept_id = null)
    {
        $cycle = EvaluationCycle::findOne($cycle_id);
        if (!$cycle) return $this->redirect(['index']);

        $isSuperadmin = Department::isCentralAdmin();
        $currentPersonnel = \common\models\Personnel::findOne(['user_id' => Yii::$app->user->id]);
        $userDeptId = $currentPersonnel ? $currentPersonnel->department_id : null;
        $scopedDeptIds = $userDeptId ? Department::getAllScopedDeptIds($userDeptId) : [];
        $isScoped = !$isSuperadmin && !empty($scopedDeptIds);

        $evalQuery = Evaluation::find()
            ->innerJoinWith('personnel')
            ->where(['{{%evaluations}}.evaluation_cycle_id' => $cycle->id])
            ->with(['personnel.department', 'personnel.position', 'personnel.personnelType', 'result']);

        if ($isScoped) {
            if ($dept_id && in_array((int)$dept_id, $scopedDeptIds, true)) {
                $targetDepts = Department::getAllScopedDeptIds((int)$dept_id);
                $evalQuery->andWhere(['in', '{{%personnel}}.department_id', $targetDepts]);
            } else {
                $evalQuery->andWhere(['in', '{{%personnel}}.department_id', $scopedDeptIds]);
            }
        } elseif ($dept_id) {
            $targetDepts = Department::getAllScopedDeptIds((int)$dept_id);
            $evalQuery->andWhere(['in', '{{%personnel}}.department_id', $targetDepts]);
        }

        $evaluations = $evalQuery->orderBy([
            '{{%personnel}}.department_id' => SORT_ASC,
            '{{%personnel}}.first_name_th' => SORT_ASC,
        ])->all();

        $filename = 'RMUTT_Evaluation_Summary_' . $cycle->fiscal_year . '_Cycle' . $cycle->cycle_number . '.csv';

        $handle = fopen('php://temp', 'w+');
        // UTF-8 BOM for Excel in Thai
        fputs($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'ลำดับ',
            'รหัสบุคลากร',
            'ชื่อ-นามสกุล',
            'ตำแหน่ง',
            'ฝ่าย/สังกัด',
            'ประเภทบุคลากร',
            'สถานะ',
            'คะแนนผลสัมฤทธิ์ / ภาระงาน',
            'คะแนนสมรรถนะ / พฤติกรรม',
            'คะแนนรวมสุทธิ (100%)',
            'ระดับผลการประเมิน',
        ]);

        foreach ($evaluations as $idx => $e) {
            $isCompleted = $e->isCompleted();
            $r = $isCompleted ? $e->result : null;
            fputcsv($handle, [
                $idx + 1,
                $e->personnel?->employee_code ?: '',
                $e->personnel?->fullName ?: '',
                $e->personnel?->position?->name_th ?: '',
                $e->personnel?->department?->name_th ?: '',
                $e->personnel?->personnelType?->name_th ?: '',
                $e->status,
                $r ? number_format($r->supervisor_performance_score, 2) : '',
                $r ? number_format($r->supervisor_competency_score, 2) : '',
                $r ? number_format($r->final_percentage, 2) : '',
                $r ? $r->performance_level : ($isCompleted ? '' : 'รอประเมินเสร็จสิ้น'),
            ]);
        }

        rewind($handle);
        return Yii::$app->response->sendStreamAsFile($handle, $filename, [
            'mimeType' => 'text/csv; charset=UTF-8',
        ]);
    }
}
