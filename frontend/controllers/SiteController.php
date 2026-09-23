<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\LoginForm;
use common\models\Personnel;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller for staff portal.
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
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
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays homepage / staff dashboard.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $activeCycle = EvaluationCycle::find()
            ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (Yii::$app->user->isGuest) {
            return $this->render('index', [
                'isGuest' => true,
                'personnel' => null,
                'activeCycle' => $activeCycle,
                'myEvaluation' => null,
                'myEvaluations' => [],
                'teamEvaluations' => [],
                'pendingReviews' => [],
                'isSupervisor' => false,
            ]);
        }

        $userId = Yii::$app->user->id;
        $personnel = Personnel::findOne(['user_id' => $userId]);

        $myEvaluation = null;
        $myEvaluations = [];
        $teamEvaluations = [];
        $pendingReviews = [];
        $isSupervisor = false;

        if ($personnel) {
            $isSupervisor = (bool)$personnel->is_supervisor;

            // Auto-create evaluation if active cycle exists and user has none
            if ($activeCycle && !Evaluation::findOne(['evaluation_cycle_id' => $activeCycle->id, 'personnel_id' => $personnel->id])) {
                $templateVersion = $activeCycle->getTemplateVersionForPersonnel($personnel);
                if ($templateVersion) {
                    $newEval = new Evaluation();
                    $newEval->evaluation_cycle_id = $activeCycle->id;
                    $newEval->personnel_id = $personnel->id;
                    $newEval->template_version_id = $templateVersion->id;
                    $newEval->evaluator_id = $personnel->supervisor_id ?: $personnel->division_head_id;
                    $newEval->status = Evaluation::STATUS_SELF_ASSESSMENT;
                    $newEval->save(false);
                }
            }

            // My Evaluations
            $myEvaluations = Evaluation::find()
                ->where(['personnel_id' => $personnel->id])
                ->with(['cycle', 'templateVersion.template', 'result', 'evaluator'])
                ->orderBy(['id' => SORT_DESC])
                ->all();

            if ($activeCycle) {
                foreach ($myEvaluations as $e) {
                    if ($e->evaluation_cycle_id == $activeCycle->id) {
                        $myEvaluation = $e;
                        break;
                    }
                }
            }

            // Team evaluations for supervisor
            if ($isSupervisor) {
                $isDivisionHead = $personnel->isDivisionHead();
                $teamQuery = Evaluation::find()
                    ->innerJoinWith('personnel')
                    ->with(['cycle', 'personnel.position', 'personnel.department', 'result'])
                    ->orderBy(['{{%evaluations}}.id' => SORT_DESC]);

                if ($activeCycle) {
                    $teamQuery->andWhere(['{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id]);
                }

                if ($isDivisionHead) {
                    $teamQuery->andWhere([
                        'or',
                        ['evaluator_id' => $personnel->id],
                        ['evaluator_l2_id' => $personnel->id],
                        ['{{%personnel}}.supervisor_id' => $personnel->id],
                        ['{{%personnel}}.division_head_id' => $personnel->id],
                        ['{{%personnel}}.department_id' => $personnel->department_id],
                    ]);
                } else {
                    $teamQuery->andWhere([
                        'or',
                        ['evaluator_id' => $personnel->id],
                        ['evaluator_l2_id' => $personnel->id],
                        ['{{%personnel}}.supervisor_id' => $personnel->id],
                    ]);
                }
                $teamEvaluations = $teamQuery->all();

                $pendingReviews = array_filter($teamEvaluations, function ($e) use ($personnel) {
                    if (in_array($e->status, [Evaluation::STATUS_SUBMITTED, Evaluation::STATUS_SUPERVISOR_REVIEW])) {
                        return $e->evaluator_id == $personnel->id || (!$e->evaluator_id && $e->personnel->supervisor_id == $personnel->id);
                    }
                    if ($e->status === Evaluation::STATUS_SUBMITTED_L1) {
                        return $e->evaluator_l2_id == $personnel->id;
                    }
                    return false;
                });
            }
        }

        return $this->render('index', [
            'isGuest' => false,
            'personnel' => $personnel,
            'activeCycle' => $activeCycle,
            'myEvaluation' => $myEvaluation,
            'myEvaluations' => $myEvaluations,
            'teamEvaluations' => $teamEvaluations,
            'pendingReviews' => $pendingReviews,
            'isSupervisor' => $isSupervisor,
        ]);
    }

    /**
     * Logs in a user (supports both staff and admin portal selection).
     *
     * @return string|Response
     */
    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        $portalType = Yii::$app->request->post('portal_type', Yii::$app->request->get('portal', 'staff'));
        if (!in_array($portalType, ['staff', 'admin'], true)) {
            $portalType = 'staff';
        }

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $user = Yii::$app->user->identity;

            if ($portalType === 'admin') {
                $auth = Yii::$app->authManager;
                $userRoles = $auth ? array_keys($auth->getRolesByUser($user->id)) : [];
                $hasAdminRole = Yii::$app->user->can('admin') 
                    || Yii::$app->user->can('superadmin') 
                    || Yii::$app->user->can('division_head')
                    || !empty(array_intersect($userRoles, ['admin', 'superadmin', 'division_head']));

                if (!$hasAdminRole) {
                    Yii::$app->user->logout();
                    Yii::$app->session->setFlash('danger', 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานในฐานะผู้ดูแลระบบ (สำหรับบุคลากรทั่วไป กรุณาเลือกแท็บ "บุคลากร")');
                    $model->password = '';
                    return $this->render('login', [
                        'model' => $model,
                        'portalType' => $portalType,
                    ]);
                }

                $host = Yii::$app->request->getServerName();
                $backendPort = getenv('APP_BACKEND_PORT') ?: '8001';
                $protocol = Yii::$app->request->isSecureConnection ? 'https' : 'http';
                $backendUrl = "{$protocol}://{$host}:{$backendPort}/index.php?r=site/index";
                return $this->redirect($backendUrl);
            }

            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
            'portalType' => $portalType,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
