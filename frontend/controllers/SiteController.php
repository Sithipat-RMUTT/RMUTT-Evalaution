<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\LoginForm;
use common\models\Personnel;
use frontend\models\ContactForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\mail\MailerInterface;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
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
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
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

    /**
     * Displays contact page.
     *
     * @return string|Response
     */
    public function actionContact(): string|Response
    {
        $model = new ContactForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $sent = $model->sendEmail(
                $this->mailer,
                Yii::$app->params['adminEmail'],
                Yii::$app->params['senderEmail'],
                Yii::$app->params['senderName'],
            );

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return string|Response
     */
    public function actionSignup(): string|Response
    {
        Yii::$app->session->setFlash('warning', 'ระบบไม่อนุญาตให้ลงทะเบียนด้วยตนเอง กรุณาติดต่อผู้ดูแลระบบงานบุคคลเพื่อสร้างบัญชีผู้ใช้งาน');
        return $this->redirect(['site/login']);
    }

    /**
     * Requests password reset.
     *
     * @return string|Response
     */
    public function actionRequestPasswordReset(): string|Response
    {
        $model = new PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $sent = $model->sendEmail(
                $this->mailer,
                Yii::$app->params['supportEmail'],
                Yii::$app->name,
            );

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return string|Response
     * @throws BadRequestHttpException
     */
    public function actionResetPassword(string $token): string|Response
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionVerifyEmail(string $token): Response
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->verifyEmail()) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return string|Response
     */
    public function actionResendVerificationEmail(): string|Response
    {
        $model = new ResendVerificationEmailForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $sent = $model->sendEmail(
                $this->mailer,
                Yii::$app->params['supportEmail'],
                Yii::$app->name,
            );

            if ($sent) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model,
        ]);
    }
}
