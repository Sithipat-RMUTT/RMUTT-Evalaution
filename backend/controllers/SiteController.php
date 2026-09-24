<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use common\models\Personnel;
use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\EvaluationResult;
use common\models\Department;
use common\models\PersonnelType;
use common\models\EvaluationCompetencyAnswer;
use common\models\CompetencyDefinition;

/**
 * SiteController for HR Admin backend.
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    $host = Yii::$app->request->getServerName();
                    $frontendPort = getenv('APP_HTTP_PORT') ?: '8000';
                    $protocol = Yii::$app->request->isSecureConnection ? 'https' : 'http';

                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->response->redirect("{$protocol}://{$host}:{$frontendPort}/index.php?r=site/login&portal=admin");
                    }
                    Yii::$app->session->setFlash('danger', 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานในระบบผู้ดูแลระบบ (HR Admin)');
                    return Yii::$app->response->redirect("{$protocol}://{$host}:{$frontendPort}/index.php?r=site/index");
                },
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['admin', 'superadmin', 'central_hr', 'division_head'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays HR Admin dashboard.
     *
     * @return string
     */
    public function actionIndex()
    {
        $isSuperAdmin = Department::isCentralAdmin();
        $myDeptId = Department::getCurrentUserDeptId();
        $myDepartment = $myDeptId ? Department::findOne($myDeptId) : null;
        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

        // 1. Executive Filters
        $cycleId = Yii::$app->request->get('cycle_id');
        $typeId = Yii::$app->request->get('type_id');
        $filterDeptId = Yii::$app->request->get('dept_id');

        $cycles = EvaluationCycle::find()->orderBy(['fiscal_year' => SORT_DESC, 'cycle_number' => SORT_DESC])->all();
        $activeCycle = EvaluationCycle::find()
            ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$cycleId) {
            $cycleId = $activeCycle ? $activeCycle->id : (!empty($cycles) ? $cycles[0]->id : null);
        }
        $selectedCycle = $cycleId ? EvaluationCycle::findOne($cycleId) : $activeCycle;

        // Personnel Types
        $personnelTypes = PersonnelType::find()->all();

        // Departments for filter and benchmarking
        $departments = $isSuperAdmin 
            ? Department::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all() 
            : ($myDeptId ? Department::getScopedDepartments($myDeptId) : []);

        // Effective department scoping for active user
        $effectiveDeptIds = [];
        if (!$isSuperAdmin && !empty($scopedDeptIds)) {
            if ($filterDeptId && in_array((int)$filterDeptId, $scopedDeptIds, true)) {
                $effectiveDeptIds = Department::getAllScopedDeptIds((int)$filterDeptId);
            } else {
                $effectiveDeptIds = $scopedDeptIds;
            }
        } elseif ($filterDeptId) {
            $effectiveDeptIds = Department::getAllScopedDeptIds((int)$filterDeptId);
        }

        // 2. Personnel Query in Scope
        $personnelQuery = Personnel::find()->where(['status' => 10]);
        if (!empty($effectiveDeptIds)) {
            $personnelQuery->andWhere(['in', 'department_id', $effectiveDeptIds]);
        }
        if ($typeId) {
            $personnelQuery->andWhere(['personnel_type_id' => $typeId]);
        }
        $totalPersonnel = (int)$personnelQuery->count();

        $supervisorQuery = Personnel::find()->where(['status' => 10, 'is_supervisor' => 1]);
        if (!empty($effectiveDeptIds)) {
            $supervisorQuery->andWhere(['in', 'department_id', $effectiveDeptIds]);
        }
        $totalSupervisors = (int)$supervisorQuery->count();

        // 3. Evaluations in Cycle
        $evalBaseQuery = Evaluation::find()
            ->innerJoinWith('personnel')
            ->where(['{{%evaluations}}.evaluation_cycle_id' => $selectedCycle ? $selectedCycle->id : 0]);

        if (!empty($effectiveDeptIds)) {
            $evalBaseQuery->andWhere(['in', '{{%personnel}}.department_id', $effectiveDeptIds]);
        }
        if ($typeId) {
            $evalBaseQuery->andWhere(['{{%personnel}}.personnel_type_id' => $typeId]);
        }

        $allEvalsInCycle = (clone $evalBaseQuery)->all();
        $totalEvalsCount = count($allEvalsInCycle);

        // Status counts for operational reference
        $statusCounts = [
            'total' => $totalEvalsCount,
            'self_assessment' => 0,
            'submitted' => 0,
            'submitted_l1' => 0,
            'submitted_l2' => 0,
            'supervisor_review' => 0,
            'completed' => 0,
            'returned' => 0,
            'draft' => 0,
        ];
        foreach ($allEvalsInCycle as $e) {
            if (isset($statusCounts[$e->status])) {
                $statusCounts[$e->status]++;
            }
        }

        // 4. Evaluated Items with Results
        $evalWithResultQuery = (clone $evalBaseQuery)
            ->innerJoinWith('result')
            ->with([
                'personnel.department',
                'personnel.position',
                'personnel.personnelType',
                'personnel.supervisor',
                'personnel.divisionHead',
                'result'
            ])
            ->orderBy(['{{%evaluation_results}}.final_percentage' => SORT_DESC]);

        $evaluatedItems = $evalWithResultQuery->all();
        $evaluatedCount = count($evaluatedItems);
        $evaluationRate = $totalPersonnel > 0 ? round(($evaluatedCount / $totalPersonnel) * 100, 1) : ($totalEvalsCount > 0 ? round(($evaluatedCount / $totalEvalsCount) * 100, 1) : 0);

        // 5. Score Averages (Org Health & Outcomes)
        $avgScore = 0;
        $avgKpi = 0;
        $avgComp = 0;
        if ($evaluatedCount > 0) {
            $sumFinal = 0;
            $sumKpi = 0;
            $sumComp = 0;
            foreach ($evaluatedItems as $item) {
                $r = $item->result;
                $sumFinal += (float)$r->final_percentage;
                $kpi = (float)($r->supervisor_performance_score ?: $r->l1_performance_score ?: $r->self_performance_score ?: 0);
                $sumKpi += $kpi;
                $comp = (float)($r->supervisor_competency_score ?: $r->l1_competency_score ?: $r->self_competency_score ?: 0);
                $sumComp += $comp;
            }
            $avgScore = round($sumFinal / $evaluatedCount, 2);
            $avgKpi = round($sumKpi / $evaluatedCount, 2);
            $avgComp = round($sumComp / $evaluatedCount, 2);
        }

        // Org Performance Tier
        if ($avgScore >= 95.0) {
            $orgTier = 'ดีเด่น';
            $orgTierBadge = 'bg-success text-white';
        } elseif ($avgScore >= 85.0) {
            $orgTier = 'ดีมาก';
            $orgTierBadge = 'bg-primary text-white';
        } elseif ($avgScore >= 75.0) {
            $orgTier = 'ดี';
            $orgTierBadge = 'bg-info text-dark';
        } elseif ($avgScore >= 65.0) {
            $orgTier = 'พอใช้';
            $orgTierBadge = 'bg-warning text-dark';
        } else {
            $orgTier = 'ต้องปรับปรุง';
            $orgTierBadge = 'bg-danger text-white';
        }

        // 6. Grade Distribution & Merit Quota Check
        $gradeCounts = [
            'ดีเด่น' => 0,
            'ดีมาก' => 0,
            'ดี' => 0,
            'พอใช้' => 0,
            'ต้องปรับปรุง' => 0,
            'ไม่ผ่าน' => 0,
        ];
        foreach ($evaluatedItems as $item) {
            $pct = (float)$item->result->final_percentage;
            if ($pct >= 95.0) {
                $gradeCounts['ดีเด่น']++;
            } elseif ($pct >= 85.0) {
                $gradeCounts['ดีมาก']++;
            } elseif ($pct >= 75.0) {
                $gradeCounts['ดี']++;
            } elseif ($pct >= 65.0) {
                $gradeCounts['พอใช้']++;
            } else {
                $gradeCounts['ต้องปรับปรุง']++;
            }
        }

        $gradePcts = [];
        foreach ($gradeCounts as $k => $cnt) {
            $gradePcts[$k] = $evaluatedCount > 0 ? round(($cnt / $evaluatedCount) * 100, 1) : 0;
        }

        $quotaCaps = [
            'ดีเด่น' => 15.0,
            'ดีมาก' => 35.0,
            'ดี' => 35.0,
            'พอใช้' => 10.0,
            'ต้องปรับปรุง' => 5.0,
        ];

        $excellentCount = $gradeCounts['ดีเด่น'];
        $excellentPct = $gradePcts['ดีเด่น'];
        $veryGoodCount = $gradeCounts['ดีมาก'];
        $veryGoodPct = $gradePcts['ดีมาก'];
        $topTalentCount = $excellentCount + $veryGoodCount;
        $topTalentPct = round($excellentPct + $veryGoodPct, 1);

        $isOverQuota = ($excellentPct > 15.0 && $evaluatedCount >= 3);
        $quotaStatus = [
            'is_over_quota' => $isOverQuota,
            'excellent_count' => $excellentCount,
            'excellent_pct' => $excellentPct,
            'ceiling_pct' => 15.0,
            'message' => $isOverQuota
                ? 'สัดส่วนกลุ่มดีเด่น (' . $excellentPct . '%) เกินกรอบวงเงินงบประมาณเลื่อนเงินเดือนปกติ (≤15%) สุ่มเสี่ยงเกรดเฟ้อ แนะนำให้คณะกรรมการกลั่นกรองทบทวน'
                : 'สัดส่วนกลุ่มผลงานดีเด่นสอดคล้องกับกรอบวงเงินงบประมาณเลื่อนเงินเดือน (≤15%)',
        ];

        // 7. At-Risk / Low Performers (< 70% or พอใช้/ต้องปรับปรุง)
        $atRiskPersonnel = [];
        foreach ($evaluatedItems as $item) {
            $pct = (float)$item->result->final_percentage;
            if ($pct < 70.0 || in_array($item->result->performance_level, ['พอใช้', 'ต้องปรับปรุง', 'ไม่ผ่าน'], true)) {
                $atRiskPersonnel[] = $item;
            }
        }
        $atRiskCount = count($atRiskPersonnel);
        $atRiskPct = $evaluatedCount > 0 ? round(($atRiskCount / $evaluatedCount) * 100, 1) : 0;

        // 8. Top Performers (>= 85%, top 10)
        $topPerformers = [];
        foreach ($evaluatedItems as $item) {
            if ((float)$item->result->final_percentage >= 85.0) {
                $topPerformers[] = $item;
            }
        }
        $topPerformers = array_slice($topPerformers, 0, 10);

        // 9. Cross-Department Benchmark & Quota Matrix
        $deptBenchmark = [];
        $deptProgress = [];
        foreach ($departments as $dept) {
            $pQuery = Personnel::find()->where(['department_id' => $dept->id, 'status' => 10]);
            if ($typeId) {
                $pQuery->andWhere(['personnel_type_id' => $typeId]);
            }
            $pCount = (int)$pQuery->count();

            $deptEvals = array_filter($evaluatedItems, function($item) use ($dept) {
                return $item->personnel && $item->personnel->department_id == $dept->id;
            });
            $deptEvalCount = count($deptEvals);

            if ($pCount > 0 || $deptEvalCount > 0) {
                $deptScores = array_map(fn($e) => (float)$e->result->final_percentage, $deptEvals);
                $deptAvg = $deptEvalCount > 0 ? round(array_sum($deptScores) / $deptEvalCount, 2) : 0;

                $deptKpiScores = array_map(fn($e) => (float)($e->result->supervisor_performance_score ?: $e->result->l1_performance_score ?: 0), $deptEvals);
                $deptCompScores = array_map(fn($e) => (float)($e->result->supervisor_competency_score ?: $e->result->l1_competency_score ?: 0), $deptEvals);
                $deptKpiAvg = $deptEvalCount > 0 ? round(array_sum($deptKpiScores) / $deptEvalCount, 2) : 0;
                $deptCompAvg = $deptEvalCount > 0 ? round(array_sum($deptCompScores) / $deptEvalCount, 2) : 0;

                $deptExcCount = count(array_filter($deptEvals, fn($e) => (float)$e->result->final_percentage >= 95.0));
                $deptExcPct = $deptEvalCount > 0 ? round(($deptExcCount / $deptEvalCount) * 100, 1) : 0;

                $deptRiskCount = count(array_filter($deptEvals, fn($e) => (float)$e->result->final_percentage < 70.0));
                $deptRiskPct = $deptEvalCount > 0 ? round(($deptRiskCount / $deptEvalCount) * 100, 1) : 0;

                $deptBenchmark[] = [
                    'department' => $dept,
                    'total_staff' => $pCount,
                    'evaluated_count' => $deptEvalCount,
                    'avg_score' => $deptAvg,
                    'avg_kpi' => $deptKpiAvg,
                    'avg_comp' => $deptCompAvg,
                    'excellent_count' => $deptExcCount,
                    'excellent_pct' => $deptExcPct,
                    'at_risk_count' => $deptRiskCount,
                    'at_risk_pct' => $deptRiskPct,
                    'is_over_quota' => ($deptExcPct > 15.0 && $deptEvalCount >= 3),
                ];

                $deptProgress[] = [
                    'department' => $dept,
                    'total' => $pCount,
                    'completed' => $deptEvalCount,
                    'pending' => max(0, $pCount - $deptEvalCount),
                    'percent' => $pCount > 0 ? round(($deptEvalCount / $pCount) * 100, 1) : 0,
                ];
            }
        }
        // Sort benchmark by avg_score DESC
        usort($deptBenchmark, fn($a, $b) => $b['avg_score'] <=> $a['avg_score']);

        // 10. Competency Gaps for HRD Budget Planning
        $competencyGaps = [];
        $evalIds = array_map(fn($e) => $e->id, $evaluatedItems);
        if (!empty($evalIds)) {
            $answers = (new \yii\db\Query())
                ->select([
                    'cd.id',
                    'cd.name_th',
                    'cd.competency_type',
                    'cd.expected_level',
                    'avg_level' => 'AVG(eca.level_value)',
                    'count_answers' => 'COUNT(eca.id)',
                ])
                ->from('{{%evaluation_competency_answers}} eca')
                ->innerJoin('{{%competency_definitions}} cd', 'cd.id = eca.competency_definition_id')
                ->where(['in', 'eca.evaluation_id', $evalIds])
                ->groupBy(['cd.id', 'cd.name_th', 'cd.competency_type', 'cd.expected_level'])
                ->orderBy(['avg_level' => SORT_ASC])
                ->limit(5)
                ->all();

            foreach ($answers as $ans) {
                $avgVal = round((float)$ans['avg_level'], 2);
                $expected = (int)$ans['expected_level'];
                $gap = round($avgVal - $expected, 2);
                $competencyGaps[] = [
                    'id' => $ans['id'],
                    'name_th' => $ans['name_th'],
                    'type' => $ans['competency_type'],
                    'expected' => $expected,
                    'actual' => $avgVal,
                    'gap' => $gap,
                ];
            }
        }

        // 11. Recent evaluations
        $recentEvaluations = array_slice($evaluatedItems, 0, 8);

        return $this->render('index', [
            'isSuperAdmin' => $isSuperAdmin,
            'myDepartment' => $myDepartment,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'activeCycle' => $activeCycle,
            'cycleId' => $cycleId,
            'personnelTypes' => $personnelTypes,
            'typeId' => $typeId,
            'departments' => $departments,
            'filterDeptId' => $filterDeptId,
            'totalPersonnel' => $totalPersonnel,
            'totalSupervisors' => $totalSupervisors,
            'evaluatedCount' => $evaluatedCount,
            'evaluationRate' => $evaluationRate,
            'statusCounts' => $statusCounts,
            'gradeCounts' => $gradeCounts,
            'gradePcts' => $gradePcts,
            'quotaCaps' => $quotaCaps,
            'avgScore' => $avgScore,
            'avgKpi' => $avgKpi,
            'avgComp' => $avgComp,
            'orgTier' => $orgTier,
            'orgTierBadge' => $orgTierBadge,
            'quotaStatus' => $quotaStatus,
            'excellentCount' => $excellentCount,
            'excellentPct' => $excellentPct,
            'veryGoodCount' => $veryGoodCount,
            'veryGoodPct' => $veryGoodPct,
            'topTalentCount' => $topTalentCount,
            'topTalentPct' => $topTalentPct,
            'atRiskPersonnel' => $atRiskPersonnel,
            'atRiskCount' => $atRiskCount,
            'atRiskPct' => $atRiskPct,
            'topPerformers' => $topPerformers,
            'deptBenchmark' => $deptBenchmark,
            'deptProgress' => $deptProgress,
            'competencyGaps' => $competencyGaps,
            'recentEvaluations' => $recentEvaluations,
        ]);
    }

    /**
     * Login action - redirects to the unified login portal on frontend.
     *
     * @return \yii\web\Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $host = Yii::$app->request->getServerName();
        $frontendPort = getenv('APP_HTTP_PORT') ?: '8000';
        $protocol = Yii::$app->request->isSecureConnection ? 'https' : 'http';
        return $this->redirect("{$protocol}://{$host}:{$frontendPort}/index.php?r=site/login&portal=admin");
    }

    /**
     * Logout action.
     *
     * @return \yii\web\Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        $host = Yii::$app->request->getServerName();
        $frontendPort = getenv('APP_HTTP_PORT') ?: '8000';
        $protocol = Yii::$app->request->isSecureConnection ? 'https' : 'http';
        return $this->redirect("{$protocol}://{$host}:{$frontendPort}/index.php?r=site/login&portal=admin");
    }
}
