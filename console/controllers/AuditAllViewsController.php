<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\Personnel;
use common\models\Department;
use common\models\PersonnelType;
use common\models\EvaluationResult;
use common\services\EvaluationCalculatorService;

/**
 * AuditAllViewsController thoroughly tests rendering of EVERY single frontend and backend view.
 */
class AuditAllViewsController extends Controller
{
    public function actionIndex()
    {
        $this->stdout("\n=======================================================\n", Console::FG_CYAN);
        $this->stdout("   COMPREHENSIVE FULL-SYSTEM VIEW & FUNCTION AUDIT   \n", Console::FG_YELLOW, Console::BOLD);
        $this->stdout("=======================================================\n\n", Console::FG_CYAN);

        // Configure UrlManager for console context
        Yii::$app->urlManager->setScriptUrl('/index.php');
        Yii::$app->urlManager->setBaseUrl('');
        Yii::setAlias('@webroot', Yii::getAlias('@frontend/web'));
        Yii::setAlias('@web', '');
        Yii::$app->set('response', new \yii\web\Response());
        Yii::$app->set('request', new \yii\web\Request([
            'url' => '/index.php',
            'scriptUrl' => '/index.php',
            'baseUrl' => '',
            'cookieValidationKey' => 'audit_secret_key_12345678901234567890',
            'enableCsrfValidation' => false,
        ]));

        $evals = Evaluation::find()->with(['personnel.personnelType', 'personnel.department', 'personnel.position', 'templateVersion.sections.items', 'templateVersion.competencyDefinitions.levels', 'evaluator'])->all();

        $errors = 0;
        $passed = 0;

        // 1. Audit Frontend Views for All Evaluations
        $frontCtrl = new \frontend\controllers\EvaluationController('evaluation', Yii::$app);
        
        foreach ($evals as $eval) {
            $pType = $eval->personnel->personnelType->code;
            $name = $eval->personnel->fullName;
            $this->stdout("▶ Testing Evaluation ID {$eval->id} ({$name} - {$pType})...\n", Console::FG_CYAN);

            // 1.1 Self Assess View
            try {
                $out = $frontCtrl->renderPartial('@frontend/views/evaluation/self_assess', [
                    'evaluation' => $eval,
                    'personnel' => $eval->personnel,
                    'templateVersion' => $eval->templateVersion,
                    'sections' => $eval->templateVersion->sections,
                    'competencies' => $eval->templateVersion->competencyDefinitions,
                    'answers' => [],
                    'compAnswers' => [],
                    'evidenceFiles' => [],
                    'isEditable' => true,
                ]);
                $this->stdout("   ✔ [Frontend] self_assess.php: OK (" . strlen($out) . " bytes)\n", Console::FG_GREEN);
                $passed++;
            } catch (\Throwable $e) {
                $this->stdout("   ✖ [Frontend] self_assess.php ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", Console::FG_RED, Console::BOLD);
                $errors++;
            }

            // 1.2 Supervisor Assess View
            try {
                $out = $frontCtrl->renderPartial('@frontend/views/evaluation/supervisor_assess', [
                    'evaluation' => $eval,
                    'personnel' => $eval->personnel,
                    'evaluatee' => $eval->personnel,
                    'supervisor' => $eval->evaluator ?: $eval->personnel,
                    'templateVersion' => $eval->templateVersion,
                    'sections' => $eval->templateVersion->sections,
                    'competencies' => $eval->templateVersion->competencyDefinitions,
                    'selfAnswers' => [],
                    'supAnswers' => [],
                    'selfCompAnswers' => [],
                    'supCompAnswers' => [],
                    'evidenceFiles' => [],
                    'result' => $eval->result,
                    'isEditable' => true,
                ]);
                $this->stdout("   ✔ [Frontend] supervisor_assess.php: OK (" . strlen($out) . " bytes)\n", Console::FG_GREEN);
                $passed++;
            } catch (\Throwable $e) {
                $this->stdout("   ✖ [Frontend] supervisor_assess.php ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", Console::FG_RED, Console::BOLD);
                $errors++;
            }

            // 1.3 View (Report View)
            try {
                $out = $frontCtrl->renderPartial('@frontend/views/evaluation/view', [
                    'evaluation' => $eval,
                    'personnel' => $eval->personnel,
                    'templateVersion' => $eval->templateVersion,
                    'sections' => $eval->templateVersion->sections,
                    'competencies' => $eval->templateVersion->competencyDefinitions,
                    'selfAnswers' => [],
                    'supAnswers' => [],
                    'selfCompAnswers' => [],
                    'supCompAnswers' => [],
                    'evidenceFiles' => [],
                    'result' => $eval->result,
                    'isOwner' => true,
                ]);
                $this->stdout("   ✔ [Frontend] view.php: OK (" . strlen($out) . " bytes)\n", Console::FG_GREEN);
                $passed++;
            } catch (\Throwable $e) {
                $this->stdout("   ✖ [Frontend] view.php ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", Console::FG_RED, Console::BOLD);
                $errors++;
            }

            // 1.4 Backend Monitor View
            $monCtrl = new \backend\controllers\MonitorController('monitor', Yii::$app);
            try {
                $out = $monCtrl->renderPartial('@backend/views/monitor/view', [
                    'evaluation' => $eval,
                    'personnel' => $eval->personnel,
                    'templateVersion' => $eval->templateVersion,
                    'sections' => $eval->templateVersion->sections,
                    'competencies' => $eval->templateVersion->competencyDefinitions,
                    'selfAnswers' => [],
                    'supAnswers' => [],
                    'selfCompAnswers' => [],
                    'supCompAnswers' => [],
                    'evidenceFiles' => [],
                    'result' => $eval->result,
                ]);
                $this->stdout("   ✔ [Backend] monitor/view.php: OK (" . strlen($out) . " bytes)\n", Console::FG_GREEN);
                $passed++;
            } catch (\Throwable $e) {
                $this->stdout("   ✖ [Backend] monitor/view.php ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", Console::FG_RED, Console::BOLD);
                $errors++;
            }

            // 1.5 Calculation Engine Execution
            try {
                $res = EvaluationCalculatorService::calculate($eval);
                $this->stdout("   ✔ Score Calculation Engine: OK (Score: {$res->final_percentage}%, Level: {$res->performance_level})\n\n", Console::FG_GREEN);
                $passed++;
            } catch (\Throwable $e) {
                $this->stdout("   ✖ Score Engine ERROR: " . $e->getMessage() . " on line " . $e->getLine() . "\n\n", Console::FG_RED, Console::BOLD);
                $errors++;
            }
        }

        // 2. Audit Dashboard and List Views
        $this->stdout("▶ Testing List & Dashboard Views...\n", Console::FG_CYAN);

        // Evaluation Index (Frontend)
        try {
            $out = $frontCtrl->renderPartial('@frontend/views/evaluation/index', [
                'personnel' => Personnel::findOne(1),
                'myEvaluations' => $evals,
                'teamEvaluations' => $evals,
                'activeCycle' => EvaluationCycle::findOne(['status' => EvaluationCycle::STATUS_ACTIVE]),
                'isSupervisor' => true,
            ]);
            $this->stdout("   ✔ [Frontend] evaluation/index.php: OK\n", Console::FG_GREEN);
            $passed++;
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Frontend] evaluation/index.php ERROR: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        // Backend Dashboard
        $siteCtrl = new \backend\controllers\SiteController('site', Yii::$app);
        try {
            $out = $siteCtrl->renderPartial('@backend/views/site/index', [
                'activeCycle' => EvaluationCycle::findOne(['status' => EvaluationCycle::STATUS_ACTIVE]),
                'selectedCycle' => EvaluationCycle::findOne(['status' => EvaluationCycle::STATUS_ACTIVE]),
                'cycles' => EvaluationCycle::find()->all(),
                'totalPersonnel' => 10,
                'totalSupervisors' => 2,
                'evaluatedCount' => 5,
                'evaluationRate' => 50.0,
                'statusCounts' => ['total' => 10, 'self_assessment' => 2, 'submitted' => 2, 'supervisor_review' => 1, 'completed' => 5, 'returned' => 0],
                'gradeCounts' => ['ดีเด่น' => 2, 'ดีมาก' => 2, 'ดี' => 1, 'พอใช้' => 0, 'ต้องปรับปรุง' => 0, 'ไม่ผ่าน' => 0],
                'gradePcts' => ['ดีเด่น' => 40.0, 'ดีมาก' => 40.0, 'ดี' => 20.0, 'พอใช้' => 0, 'ต้องปรับปรุง' => 0],
                'quotaCaps' => ['ดีเด่น' => 15.0, 'ดีมาก' => 35.0, 'ดี' => 35.0, 'พอใช้' => 10.0, 'ต้องปรับปรุง' => 5.0],
                'avgScore' => 84.50,
                'avgKpi' => 52.00,
                'avgComp' => 32.50,
                'orgTier' => 'ดีมาก',
                'orgTierBadge' => 'bg-primary text-white',
                'quotaStatus' => [
                    'is_over_quota' => true,
                    'excellent_count' => 2,
                    'excellent_pct' => 40.0,
                    'ceiling_pct' => 15.0,
                    'message' => 'สัดส่วนกลุ่มดีเด่นเกินกรอบโควตา',
                ],
                'excellentCount' => 2,
                'excellentPct' => 40.0,
                'veryGoodCount' => 2,
                'veryGoodPct' => 40.0,
                'topTalentCount' => 4,
                'topTalentPct' => 80.0,
                'atRiskPersonnel' => $evals,
                'atRiskCount' => count($evals),
                'atRiskPct' => 20.0,
                'topPerformers' => $evals,
                'deptBenchmark' => [],
                'deptProgress' => [],
                'competencyGaps' => [
                    [
                        'id' => 1,
                        'name_th' => 'ทักษะดิจิทัลและการประยุกต์ใช้',
                        'type' => 'Core Competency',
                        'expected' => 3,
                        'actual' => 2.40,
                        'gap' => -0.60,
                    ]
                ],
                'recentEvaluations' => $evals,
                'departments' => Department::find()->all(),
            ]);
            $this->stdout("   ✔ [Backend] site/index.php (HR Executive Results Dashboard): OK\n", Console::FG_GREEN);
            $passed++;
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Backend] site/index.php ERROR: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        // Backend Monitor Index
        $monCtrl = new \backend\controllers\MonitorController('monitor', Yii::$app);
        try {
            $out = $monCtrl->renderPartial('@backend/views/monitor/index', [
                'evaluations' => $evals,
                'cycles' => EvaluationCycle::find()->all(),
                'departments' => Department::find()->all(),
                'types' => PersonnelType::find()->all(),
                'cycleId' => 1,
                'deptId' => null,
                'typeId' => null,
                'status' => null,
            ]);
            $this->stdout("   ✔ [Backend] monitor/index.php: OK\n", Console::FG_GREEN);
            $passed++;
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Backend] monitor/index.php ERROR: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        // Backend Report Index
        $repCtrl = new \backend\controllers\ReportController('report', Yii::$app);
        try {
            $out = $repCtrl->renderPartial('@backend/views/report/index', [
                'cycles' => EvaluationCycle::find()->all(),
                'cycleId' => 1,
                'deptId' => null,
                'departments' => Department::find()->all(),
                'deptStats' => [],
                'typeStats' => [],
                'individualEvaluations' => $evals,
                'isSuperadmin' => true,
            ]);
            $this->stdout("   ✔ [Backend] report/index.php: OK\n", Console::FG_GREEN);
            $passed++;
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Backend] report/index.php ERROR: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        // Backend Personnel Index & Form
        $perCtrl = new \backend\controllers\PersonnelController('personnel', Yii::$app);
        try {
            $out = $perCtrl->renderPartial('@backend/views/personnel/index', [
                'personnelList' => Personnel::find()->all(),
                'types' => PersonnelType::find()->all(),
                'organizations' => Department::find()->where(['parent_id' => null])->all(),
                'divisions' => Department::find()->where(['not', ['parent_id' => null]])->all(),
                'isSuperAdmin' => true,
                'typeId' => null,
                'orgId' => null,
                'deptId' => null,
                'search' => null,
            ]);
            $this->stdout("   ✔ [Backend] personnel/index.php: OK\n", Console::FG_GREEN);
            $passed++;

            $personnelList = Personnel::find()->with(['personnelType', 'department', 'position', 'supervisor', 'divisionHead'])->all();
            $out = $perCtrl->renderPartial('@backend/views/personnel/hierarchy', [
                'personnelList' => $personnelList,
                'departments' => Department::find()->all(),
                'currentDept' => null,
                'currentOrg' => Department::findOne(1),
                'allOrgs' => Department::find()->where(['parent_id' => null])->all(),
                'isSuperAdmin' => true,
            ]);
            $this->stdout("   ✔ [Backend] personnel/hierarchy.php: OK\n", Console::FG_GREEN);
            $passed++;
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Backend] personnel views ERROR: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        // Backend Cycle Index & Form
        $cycCtrl = new \backend\controllers\CycleController('cycle', Yii::$app);
        try {
            $out = $cycCtrl->renderPartial('@backend/views/cycle/index', [
                'cycles' => EvaluationCycle::find()->all(),
            ]);
            $this->stdout("   ✔ [Backend] cycle/index.php: OK\n", Console::FG_GREEN);
            $passed++;
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Backend] cycle/index.php ERROR: " . $e->getMessage() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        // Backend Template Builder Views
        $builderCtrl = new \backend\controllers\TemplateBuilderController('template-builder', Yii::$app);
        try {
            $templates = \common\models\EvaluationTemplate::find()->all();
            $departments = Department::find()->all();
            $personnelTypes = PersonnelType::find()->all();

            $out = $builderCtrl->renderPartial('@backend/views/template-builder/index', [
                'templates' => $templates,
                'departments' => $departments,
                'personnelTypes' => $personnelTypes,
                'selectedDepartmentId' => null,
                'selectedPersonnelTypeId' => null,
                'activeCycle' => EvaluationCycle::findOne(['status' => EvaluationCycle::STATUS_ACTIVE]),
                'targetDepartment' => $departments[0] ?? null,
                'targetDeptId' => $departments[0]->id ?? null,
                'assignedTemplates' => [],
            ]);
            $this->stdout("   ✔ [Backend] template-builder/index.php: OK\n", Console::FG_GREEN);
            $passed++;

            $out = $builderCtrl->renderPartial('@backend/views/template-builder/create', [
                'model' => new \common\models\EvaluationTemplate(),
                'departments' => $departments,
                'personnelTypes' => $personnelTypes,
            ]);
            $this->stdout("   ✔ [Backend] template-builder/create.php: OK\n", Console::FG_GREEN);
            $passed++;

            // User management views (Admin Users)
            $userCtrl = new \backend\controllers\UserController('user', Yii::$app);
            $allAdminUsers = \common\models\User::find()
                ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
                ->where(['in', 'auth_assignment.item_name', ['superadmin', 'admin']])
                ->with(['department', 'personnel.department', 'personnel.position'])
                ->all();
            $out = $userCtrl->renderPartial('@backend/views/user/index', [
                'users' => $allAdminUsers,
                'canManage' => true,
            ]);
            $this->stdout("   ✔ [Backend] user/index.php: OK\n", Console::FG_GREEN);
            $passed++;

            $adminForm = new \backend\models\AdminUserForm();
            $out = $userCtrl->renderPartial('@backend/views/user/create', [
                'model' => $adminForm,
                'departments' => $departments,
                'eligiblePersonnel' => \common\models\Personnel::find()->limit(5)->all(),
            ]);
            $this->stdout("   ✔ [Backend] user/create.php: OK\n", Console::FG_GREEN);
            $passed++;

            if (!empty($templates)) {
                $tpl = $templates[0];
                $ver = $tpl->activeVersion ?: $tpl->versions[0];
                $out = $builderCtrl->renderPartial('@backend/views/template-builder/builder', [
                    'template' => $tpl,
                    'version' => $ver,
                    'sections' => $ver->sections,
                    'competencies' => $ver->competencyDefinitions,
                    'totalSectionWeight' => 100.0,
                    'departments' => $departments,
                ]);
                $this->stdout("   ✔ [Backend] template-builder/builder.php: OK\n", Console::FG_GREEN);
                $passed++;

                $out = $builderCtrl->renderPartial('@backend/views/template-builder/preview', [
                    'template' => $tpl,
                    'version' => $ver,
                    'sections' => $ver->sections,
                    'competencies' => $ver->competencyDefinitions,
                ]);
                $this->stdout("   ✔ [Backend] template-builder/preview.php: OK\n", Console::FG_GREEN);
                $passed++;
            }
        } catch (\Throwable $e) {
            $this->stdout("   ✖ [Backend] template-builder ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", Console::FG_RED, Console::BOLD);
            $errors++;
        }

        $this->stdout("\n=======================================================\n", $errors === 0 ? Console::FG_GREEN : Console::FG_RED);
        $this->stdout(" AUDIT SUMMARY: Passed: {$passed} | Errors: {$errors}\n", $errors === 0 ? Console::FG_GREEN : Console::FG_RED, Console::BOLD);
        $this->stdout("=======================================================\n\n", $errors === 0 ? Console::FG_GREEN : Console::FG_RED);
    }

    public function actionCheckAuth()
    {
        $testUsers = [
            'admin',
            'head1',
            'head2', 
            'staff1', 
            'staff2', 
            'staff3', 
            'staff4'
        ];
        $this->stdout("\n=== CHECKING AUTH CREDENTIALS (Password: 123456) ===\n", Console::FG_CYAN);
        foreach ($testUsers as $username) {
            $user = \common\models\User::findByUsername($username);
            if (!$user) {
                $this->stdout("✖ User '{$username}': NOT FOUND IN DB\n", Console::FG_RED);
                continue;
            }
            $isValid = $user->validatePassword('123456');
            $statusText = $user->status === \common\models\User::STATUS_ACTIVE ? 'ACTIVE(10)' : 'STATUS(' . $user->status . ')';
            if ($isValid && $user->status === \common\models\User::STATUS_ACTIVE) {
                $this->stdout("✔ User '{$username}': OK [{$statusText}] (Password 123456 MATCH)\n", Console::FG_GREEN);
            } else {
                $this->stdout("✖ User '{$username}': FAILED [{$statusText}] (Valid: " . ($isValid ? 'YES' : 'NO') . ")\n", Console::FG_RED);
            }
        }
        $this->stdout("======================================================\n\n", Console::FG_CYAN);
    }
}
