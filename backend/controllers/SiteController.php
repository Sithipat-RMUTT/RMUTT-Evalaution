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
                        'roles' => ['admin', 'superadmin', 'division_head'],
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
        $currPersonnel = Personnel::findOne(['user_id' => Yii::$app->user->id]);
        $myDeptId = $currPersonnel ? $currPersonnel->department_id : null;
        $myDepartment = $myDeptId ? Department::findOne($myDeptId) : null;

        $activeCycle = EvaluationCycle::find()
            ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $personnelQuery = Personnel::find()->where(['status' => 10]);
        $supervisorQuery = Personnel::find()->where(['status' => 10, 'is_supervisor' => 1]);

        $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

        if (!$isSuperAdmin && $myDeptId) {
            $personnelQuery->andWhere(['in', 'department_id', $scopedDeptIds]);
            $supervisorQuery->andWhere(['in', 'department_id', $scopedDeptIds]);
        }

        $totalPersonnel = $personnelQuery->count();
        $totalSupervisors = $supervisorQuery->count();

        // Status counts for active cycle
        $statusCounts = [
            'total' => 0,
            'self_assessment' => 0,
            'submitted' => 0,
            'submitted_l1' => 0,
            'submitted_l2' => 0,
            'supervisor_review' => 0,
            'completed' => 0,
            'returned' => 0,
        ];

        $gradeCounts = [
            'ดีเด่น' => 0,
            'ดีมาก' => 0,
            'ดี' => 0,
            'พอใช้' => 0,
            'ต้องปรับปรุง' => 0,
            'ไม่ผ่าน' => 0,
        ];

        $recentEvaluations = [];

        if ($activeCycle) {
            $evalQuery = Evaluation::find()->where(['evaluation_cycle_id' => $activeCycle->id]);
            if (!$isSuperAdmin && $myDeptId) {
                $evalQuery->innerJoinWith('personnel')->andWhere(['in', '{{%personnel}}.department_id', $scopedDeptIds]);
            }
            $evaluations = $evalQuery->all();
            $statusCounts['total'] = count($evaluations);

            foreach ($evaluations as $e) {
                if (isset($statusCounts[$e->status])) {
                    $statusCounts[$e->status]++;
                }
            }

            // Results count
            $resQuery = EvaluationResult::find()
                ->innerJoin('{{%evaluations}}', '{{%evaluations}}.id = {{%evaluation_results}}.evaluation_id')
                ->where(['{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id]);

            if (!$isSuperAdmin && $myDeptId) {
                $resQuery->innerJoin('{{%personnel}}', '{{%personnel}}.id = {{%evaluations}}.personnel_id')
                         ->andWhere(['in', '{{%personnel}}.department_id', $scopedDeptIds]);
            }

            $results = $resQuery->all();

            foreach ($results as $r) {
                if (isset($gradeCounts[$r->performance_level])) {
                    $gradeCounts[$r->performance_level]++;
                }
            }

            $recQuery = Evaluation::find()
                ->where(['evaluation_cycle_id' => $activeCycle->id])
                ->with(['personnel.department', 'personnel.position', 'result'])
                ->orderBy(['updated_at' => SORT_DESC])
                ->limit(8);

            if (!$isSuperAdmin && $myDeptId) {
                $recQuery->innerJoinWith('personnel')->andWhere(['in', '{{%personnel}}.department_id', $scopedDeptIds]);
            }

            $recentEvaluations = $recQuery->all();
        }

        // Departments overview & completion progress
        $departments = $isSuperAdmin 
            ? Department::find()->where(['status' => 1])->all() 
            : ($myDeptId ? Department::getScopedDepartments($myDeptId) : []);

        $deptProgress = [];
        if ($activeCycle && !empty($departments)) {
            foreach ($departments as $dept) {
                $pCount = Personnel::find()->where(['department_id' => $dept->id, 'status' => 10])->count();
                if ($pCount > 0) {
                    $cCount = Evaluation::find()
                        ->innerJoinWith('personnel')
                        ->where([
                            '{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id,
                            '{{%personnel}}.department_id' => $dept->id,
                            '{{%evaluations}}.status' => Evaluation::STATUS_COMPLETED,
                        ])
                        ->count();
                    $sCount = Evaluation::find()
                        ->innerJoinWith('personnel')
                        ->where([
                            '{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id,
                            '{{%personnel}}.department_id' => $dept->id,
                        ])
                        ->andWhere(['in', '{{%evaluations}}.status', [
                            Evaluation::STATUS_SUBMITTED,
                            Evaluation::STATUS_SUPERVISOR_REVIEW,
                            Evaluation::STATUS_SUBMITTED_L1,
                            Evaluation::STATUS_SUBMITTED_L2,
                        ]])
                        ->count();

                    $pct = round(($cCount / $pCount) * 100, 1);
                    $deptProgress[] = [
                        'department' => $dept,
                        'total' => (int)$pCount,
                        'completed' => (int)$cCount,
                        'pending' => (int)$sCount,
                        'percent' => $pct,
                    ];
                }
            }
        }

        return $this->render('index', [
            'activeCycle' => $activeCycle,
            'totalPersonnel' => $totalPersonnel,
            'totalSupervisors' => $totalSupervisors,
            'statusCounts' => $statusCounts,
            'gradeCounts' => $gradeCounts,
            'recentEvaluations' => $recentEvaluations,
            'departments' => $departments,
            'deptProgress' => $deptProgress,
            'isSuperAdmin' => $isSuperAdmin,
            'myDepartment' => $myDepartment,
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
