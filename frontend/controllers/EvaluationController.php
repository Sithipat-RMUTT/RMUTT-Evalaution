<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\EvaluationAnswer;
use common\models\EvaluationCompetencyAnswer;
use common\models\EvaluationResult;
use common\models\EvidenceFile;
use common\models\Personnel;
use common\models\PersonnelType;
use common\models\AuditLog;
use common\models\Notification;
use common\services\EvaluationCalculatorService;

/**
 * EvaluationController handles personnel self-assessments and supervisor evaluations.
 */
class EvaluationController extends Controller
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
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'submit-self' => ['post'],
                    'submit-supervisor' => ['post'],
                    'return' => ['post'],
                    'acknowledge' => ['post'],
                    'auto-save' => ['post'],
                    'upload-evidence' => ['post'],
                    'delete-evidence' => ['post'],
                    'reset' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Reset evaluation to self_assessment for testing
     */
    public function actionReset($id)
    {
        $evaluation = $this->findModel($id);
        $personnel = $this->getCurrentPersonnel();

        $isOwner = $personnel && ($evaluation->personnel_id === $personnel->id);
        $isL1 = $personnel && (($evaluation->evaluator_l1_id === $personnel->id) || ($evaluation->personnel->supervisor_id === $personnel->id));
        $isL2 = $personnel && (($evaluation->evaluator_l2_id === $personnel->id) || ($evaluation->personnel->division_head_id === $personnel->id) || $personnel->isDivisionHead());
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');

        if (!YII_ENV_DEV) {
            throw new ForbiddenHttpException('ฟังก์ชันนี้อนุญาตให้ใช้งานเฉพาะในโหมดพัฒนา (Development Environment) เท่านั้น');
        }

        if (!$isOwner && !$isL1 && !$isL2 && !$isAdmin) {
            throw new ForbiddenHttpException('Permission denied');
        }

        $db = Yii::$app->db;
        $db->createCommand("DELETE FROM {{%evaluation_results}} WHERE evaluation_id = :id", [':id' => $evaluation->id])->execute();
        $db->createCommand("DELETE FROM {{%evaluation_answers}} WHERE evaluation_id = :id", [':id' => $evaluation->id])->execute();
        $db->createCommand("DELETE FROM {{%evaluation_competency_answers}} WHERE evaluation_id = :id", [':id' => $evaluation->id])->execute();
        $db->createCommand("DELETE FROM {{%evidence_files}} WHERE evaluation_id = :id", [':id' => $evaluation->id])->execute();

        $evaluation->status = Evaluation::STATUS_SELF_ASSESSMENT;
        $evaluation->self_submitted_at = null;
        $evaluation->supervisor_evaluated_at = null;
        $evaluation->l1_evaluated_at = null;
        $evaluation->l2_evaluated_at = null;
        $evaluation->completed_at = null;
        $evaluation->returned_at = null;
        $evaluation->return_reason = null;
        $evaluation->returned_by_role = null;
        $evaluation->l1_comment_strength = null;
        $evaluation->l1_comment_improvement = null;
        $evaluation->l1_comment_suggestion = null;
        $evaluation->l2_comment_strength = null;
        $evaluation->l2_comment_improvement = null;
        $evaluation->l2_comment_suggestion = null;
        $evaluation->supervisor_comment_strength = null;
        $evaluation->supervisor_comment_improvement = null;
        $evaluation->supervisor_comment_suggestion = null;
        $evaluation->employment_recommendation = null;
        $evaluation->acknowledgement_at = null;
        $evaluation->save(false);

        Yii::$app->session->setFlash('info', 'รีเซ็ตแบบประเมินเรียบร้อยแล้ว');
        return $this->redirect(['index']);
    }

    /**
     * Dashboard: Shows user's evaluations and subordinates' evaluations if supervisor
     */
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;
        $personnel = Personnel::findOne(['user_id' => $userId]);

        if (!$personnel) {
            Yii::$app->session->setFlash('warning', 'ไม่พบบัญชีข้อมูลบุคลากรของคุณในระบบ กรุณาติดต่อผู้ดูแลระบบงานบุคคล');
            return $this->redirect(['/site/index']);
        }

        // Active Cycles
        $activeCycle = EvaluationCycle::find()
            ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        // 1. My Evaluations (การประเมินของฉัน)
        $myEvaluations = Evaluation::find()
            ->where(['personnel_id' => $personnel->id])
            ->with(['cycle', 'templateVersion.template', 'result', 'evaluator'])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        // If active cycle exists and user has no evaluation yet, auto-create one!
        if ($activeCycle && !Evaluation::findOne(['evaluation_cycle_id' => $activeCycle->id, 'personnel_id' => $personnel->id])) {
            $templateVersion = $activeCycle->getTemplateVersionForPersonnel($personnel);

            if ($templateVersion) {
                $newEval = new Evaluation();
                $newEval->evaluation_cycle_id = $activeCycle->id;
                $newEval->personnel_id = $personnel->id;
                $newEval->template_version_id = $templateVersion->id;
                $newEval->evaluator_id = $personnel->supervisor_id ?: $personnel->division_head_id;
                $newEval->evaluator_l1_id = $personnel->supervisor_id;
                $newEval->evaluator_l2_id = $personnel->division_head_id;
                $newEval->status = Evaluation::STATUS_SELF_ASSESSMENT;
                $newEval->save(false);

                AuditLog::log('create_evaluation_auto', 'Evaluation', $newEval->id);
                // Refresh list
                $myEvaluations = Evaluation::find()
                    ->where(['personnel_id' => $personnel->id])
                    ->with(['cycle', 'templateVersion.template', 'result', 'evaluator'])
                    ->orderBy(['id' => SORT_DESC])
                    ->all();
            }
        }

        // 2. Team Evaluations to Assess (ประเมินบุคลากรในความรับผิดชอบ)
        $teamEvaluations = [];
        $isDivisionHead = $personnel->isDivisionHead();
        $isSectionHead = $personnel->isSectionHead();
        $isSupervisor = $isDivisionHead || $isSectionHead || $personnel->is_supervisor;

        if ($isDivisionHead) {
            // หัวหน้าฝ่าย: ดูคนในฝ่ายทั้งหมด (ทั้งที่รอ L2 หรือรวบรวม)
            $subordinateIds = Personnel::find()
                ->select('id')
                ->where(['or', ['division_head_id' => $personnel->id], ['supervisor_id' => $personnel->id]])
                ->andWhere(['!=', 'id', $personnel->id])
                ->column();

            if (!empty($subordinateIds)) {
                $teamEvaluations = Evaluation::find()
                    ->where(['personnel_id' => $subordinateIds])
                    ->with(['personnel.position', 'personnel.department', 'cycle', 'templateVersion.template', 'result', 'evaluatorL1', 'evaluatorL2'])
                    ->orderBy([
                        new \yii\db\Expression("CASE 
                            WHEN status = 'submitted_l2' THEN 1 
                            WHEN status = 'submitted_l1' THEN 2 
                            WHEN status = 'submitted' THEN 3 
                            WHEN status = 'self_assessment' THEN 4 
                            WHEN status = 'completed' THEN 5 
                            ELSE 6 END"),
                        'id' => SORT_DESC
                    ])
                    ->all();
            }
        } elseif ($isSectionHead) {
            // หัวหน้างาน: ดูลูกน้องในงาน
            $subordinateIds = Personnel::find()
                ->select('id')
                ->where(['supervisor_id' => $personnel->id])
                ->andWhere(['!=', 'id', $personnel->id])
                ->column();

            if (!empty($subordinateIds)) {
                $teamEvaluations = Evaluation::find()
                    ->where(['personnel_id' => $subordinateIds])
                    ->with(['personnel.position', 'personnel.department', 'cycle', 'templateVersion.template', 'result', 'evaluatorL1', 'evaluatorL2'])
                    ->orderBy([
                        new \yii\db\Expression("CASE 
                            WHEN status = 'submitted_l1' THEN 1 
                            WHEN status = 'submitted' THEN 2 
                            WHEN status = 'submitted_l2' THEN 3 
                            WHEN status = 'self_assessment' THEN 4 
                            WHEN status = 'completed' THEN 5 
                            ELSE 6 END"),
                        'id' => SORT_DESC
                    ])
                    ->all();
            }
        }

        return $this->render('index', [
            'personnel' => $personnel,
            'activeCycle' => $activeCycle,
            'myEvaluations' => $myEvaluations,
            'teamEvaluations' => $teamEvaluations,
            'isSupervisor' => $isSupervisor,
            'isDivisionHead' => $isDivisionHead,
            'isSectionHead' => $isSectionHead,
        ]);
    }

    /**
     * Self Assessment Form
     */
    public function actionSelfAssess($id)
    {
        $evaluation = $this->findModel($id);
        $personnel = $this->getCurrentPersonnel();

        // Check ownership
        if ($evaluation->personnel_id !== $personnel->id && !Yii::$app->user->can('admin')) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าถึงแบบประเมินนี้');
        }

        // Check editable status
        $isEditable = in_array($evaluation->status, [
            Evaluation::STATUS_DRAFT,
            Evaluation::STATUS_SELF_ASSESSMENT,
            Evaluation::STATUS_RETURNED
        ]);

        $templateVersion = $evaluation->templateVersion;
        $sections = $templateVersion->sections;
        $competencies = $templateVersion->competencyDefinitions;

        // Existing answers
        $answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('evaluation_item_id')->all();
        $compAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('competency_definition_id')->all();
        $evidenceFiles = EvidenceFile::find()->where(['evaluation_id' => $evaluation->id, 'deleted_at' => null])->all();

        return $this->render('self_assess', [
            'evaluation' => $evaluation,
            'personnel' => $personnel,
            'templateVersion' => $templateVersion,
            'sections' => $sections,
            'competencies' => $competencies,
            'answers' => $answers,
            'compAnswers' => $compAnswers,
            'evidenceFiles' => $evidenceFiles,
            'isEditable' => $isEditable,
        ]);
    }

    /**
     * Supervisor Assessment Form (Supports 2-Tier: L1 Section Head & L2 Division Head)
     */
    public function actionSupervisorAssess($id)
    {
        $evaluation = $this->findModel($id);
        $currentPersonnel = $this->getCurrentPersonnel();
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');

        $isSamePersonL1L2 = ($evaluation->evaluator_l1_id && $evaluation->evaluator_l2_id && $evaluation->evaluator_l1_id === $evaluation->evaluator_l2_id)
            || empty($evaluation->evaluator_l2_id)
            || ($evaluation->evaluatorL1 && $evaluation->evaluatorL2 && $evaluation->evaluatorL1->fullName === $evaluation->evaluatorL2->fullName);

        $isL1 = $currentPersonnel && (($evaluation->evaluator_l1_id === $currentPersonnel->id) || ($evaluation->personnel && $evaluation->personnel->supervisor_id === $currentPersonnel->id));
        $isL2 = $currentPersonnel && (($evaluation->evaluator_l2_id === $currentPersonnel->id) || ($evaluation->personnel && $evaluation->personnel->division_head_id === $currentPersonnel->id) || $currentPersonnel->isDivisionHead() || ($isSamePersonL1L2 && $isL1));

        if (!$isL1 && !$isL2 && !$isAdmin) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ประเมินบุคลากรท่านนี้ (ไม่ใช่ผู้บังคับบัญชาตามสายงาน)');
        }

        // Determine evaluator tier strictly based on status
        if ($evaluation->status === Evaluation::STATUS_SUBMITTED_L2) {
            $evaluatorTier = 'l2';
            $isEditable = ($isL2 || $isAdmin);
        } else {
            $evaluatorTier = 'l1';
            $isEditable = ($isL1 || $isAdmin) && in_array($evaluation->status, [
                Evaluation::STATUS_SUBMITTED_L1,
                Evaluation::STATUS_SUBMITTED,
                Evaluation::STATUS_SUPERVISOR_REVIEW,
            ], true);
        }

        $templateVersion = $evaluation->templateVersion;
        $sections = $templateVersion->sections;
        $competencies = $templateVersion->competencyDefinitions;

        // Fetch answers for all tiers
        $selfAnswers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('evaluation_item_id')->all();
        $l1Answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l1'])->indexBy('evaluation_item_id')->all();
        $l2Answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l2'])->indexBy('evaluation_item_id')->all();
        $legacySupAnswers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'supervisor'])->indexBy('evaluation_item_id')->all();

        $selfCompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('competency_definition_id')->all();
        $l1CompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l1'])->indexBy('competency_definition_id')->all();
        $l2CompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l2'])->indexBy('competency_definition_id')->all();
        $legacySupCompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'supervisor'])->indexBy('competency_definition_id')->all();

        // For form input:
        // If L1: use l1 answers, or fallback legacy supervisor answers
        // If L2: use l2 answers; if empty, PRE-LOAD from l1 answers (or legacy supervisor)
        if ($evaluatorTier === 'l1') {
            $supAnswers = !empty($l1Answers) ? $l1Answers : $legacySupAnswers;
            $supCompAnswers = !empty($l1CompAnswers) ? $l1CompAnswers : $legacySupCompAnswers;
        } else {
            $supAnswers = !empty($l2Answers) ? $l2Answers : (!empty($l1Answers) ? $l1Answers : $legacySupAnswers);
            $supCompAnswers = !empty($l2CompAnswers) ? $l2CompAnswers : (!empty($l1CompAnswers) ? $l1CompAnswers : $legacySupCompAnswers);
        }

        $evidenceFiles = EvidenceFile::find()->where(['evaluation_id' => $evaluation->id, 'deleted_at' => null])->all();
        $result = $evaluation->result;

        return $this->render('supervisor_assess', [
            'evaluation' => $evaluation,
            'personnel' => $evaluation->personnel,
            'evaluatee' => $evaluation->personnel,
            'supervisor' => $currentPersonnel,
            'evaluatorTier' => $evaluatorTier,
            'templateVersion' => $templateVersion,
            'sections' => $sections,
            'competencies' => $competencies,
            'selfAnswers' => $selfAnswers,
            'l1Answers' => $l1Answers,
            'l2Answers' => $l2Answers,
            'supAnswers' => $supAnswers,
            'selfCompAnswers' => $selfCompAnswers,
            'l1CompAnswers' => $l1CompAnswers,
            'l2CompAnswers' => $l2CompAnswers,
            'supCompAnswers' => $supCompAnswers,
            'evidenceFiles' => $evidenceFiles,
            'result' => $result,
            'isEditable' => $isEditable,
            'isSamePersonL1L2' => $isSamePersonL1L2,
        ]);
    }

    /**
     * View completed evaluation
     */
    public function actionView($id)
    {
        $evaluation = $this->findModel($id);
        $currentPersonnel = $this->getCurrentPersonnel();

        // Check permission: Owner, L1 evaluator, L2 evaluator, direct supervisor, division head, or admin
        $isOwner = $currentPersonnel && ($evaluation->personnel_id === $currentPersonnel->id);
        $isL1 = $currentPersonnel && (($evaluation->evaluator_l1_id === $currentPersonnel->id) || ($evaluation->personnel->supervisor_id === $currentPersonnel->id));
        $isL2 = $currentPersonnel && (($evaluation->evaluator_l2_id === $currentPersonnel->id) || ($evaluation->personnel->division_head_id === $currentPersonnel->id) || $currentPersonnel->isDivisionHead());
        $isSupervisor = $currentPersonnel && (($evaluation->evaluator_id === $currentPersonnel->id) || $isL1 || $isL2);
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin') || Yii::$app->user->can('division_head') || Yii::$app->user->can('section_head');

        if (!$isOwner && !$isSupervisor && !$isAdmin) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ดูผลการประเมินนี้');
        }

        // Retrieve existing result, calculate only if missing
        $result = $evaluation->result ?: EvaluationCalculatorService::calculate($evaluation);

        $templateVersion = $evaluation->templateVersion;
        $sections = $templateVersion->sections;
        $competencies = $templateVersion->competencyDefinitions;

        $selfAnswers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('evaluation_item_id')->all();
        $l1Answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l1'])->indexBy('evaluation_item_id')->all();
        $l2Answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l2'])->indexBy('evaluation_item_id')->all();
        $legacySupAnswers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'supervisor'])->indexBy('evaluation_item_id')->all();
        $supAnswers = !empty($l2Answers) ? $l2Answers : (!empty($l1Answers) ? $l1Answers : $legacySupAnswers);

        $selfCompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'self'])->indexBy('competency_definition_id')->all();
        $l1CompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l1'])->indexBy('competency_definition_id')->all();
        $l2CompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'l2'])->indexBy('competency_definition_id')->all();
        $legacySupCompAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id, 'answered_by' => 'supervisor'])->indexBy('competency_definition_id')->all();
        $supCompAnswers = !empty($l2CompAnswers) ? $l2CompAnswers : (!empty($l1CompAnswers) ? $l1CompAnswers : $legacySupCompAnswers);

        $evidenceFiles = EvidenceFile::find()->where(['evaluation_id' => $evaluation->id, 'deleted_at' => null])->all();

        return $this->render('view', [
            'evaluation' => $evaluation,
            'personnel' => $evaluation->personnel,
            'templateVersion' => $templateVersion,
            'sections' => $sections,
            'competencies' => $competencies,
            'selfAnswers' => $selfAnswers,
            'supAnswers' => $supAnswers,
            'l1Answers' => $l1Answers,
            'l2Answers' => $l2Answers,
            'selfCompAnswers' => $selfCompAnswers,
            'supCompAnswers' => $supCompAnswers,
            'l1CompAnswers' => $l1CompAnswers,
            'l2CompAnswers' => $l2CompAnswers,
            'evidenceFiles' => $evidenceFiles,
            'result' => $result,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * AJAX Auto-save endpoint for forms
     */
    public function actionAutoSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $evaluationId = (int)$request->post('evaluation_id');
        $answersData = $request->post('answers', []);
        $compAnswersData = $request->post('competencies', []);

        $evaluation = Evaluation::findOne($evaluationId);
        if (!$evaluation) {
            return ['success' => false, 'message' => 'ไม่พบแบบประเมิน'];
        }

        $personnel = $this->getCurrentPersonnel();
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');
        $isOwner = $personnel && ((int)$evaluation->personnel_id === (int)$personnel->id);
        $isEvaluator = $personnel && \common\services\EvaluationAccessService::isEvaluator($evaluation, $personnel);

        if (!$isAdmin && !$isOwner && !$isEvaluator) {
            return ['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขแบบประเมินนี้'];
        }

        // Editable status check
        $editableStatuses = [
            Evaluation::STATUS_DRAFT,
            Evaluation::STATUS_SELF_ASSESSMENT,
            Evaluation::STATUS_RETURNED,
            Evaluation::STATUS_SUBMITTED,
            Evaluation::STATUS_SUBMITTED_L1,
            Evaluation::STATUS_SUBMITTED_L2,
            Evaluation::STATUS_SUPERVISOR_REVIEW,
        ];
        if (!in_array($evaluation->status, $editableStatuses, true)) {
            return ['success' => false, 'message' => 'แบบประเมินอยู่ในสถานะที่ไม่สามารถแก้ไขได้'];
        }

        // Server-dictated answered_by (Never trust browser)
        if ($isOwner && !$isEvaluator && !$isAdmin) {
            $answeredBy = 'self';
        } elseif ($isEvaluator || $isAdmin) {
            if ($evaluation->status === Evaluation::STATUS_SUBMITTED_L2) {
                $answeredBy = 'l2';
            } else {
                $answeredBy = 'l1';
            }
        } else {
            $answeredBy = 'self';
        }

        // Save Item Answers
        if (is_array($answersData)) {
            foreach ($answersData as $itemId => $val) {
                $itemIdInt = (int)$itemId;
                if ($itemIdInt <= 0) continue;

                // Validate Item Ownership (ข้อ 14)
                try {
                    $item = \common\services\EvaluationValidationService::validateAnswerOwnership($evaluation, $itemIdInt);
                } catch (\Throwable $e) {
                    continue;
                }

                $ans = EvaluationAnswer::findOne([
                    'evaluation_id' => $evaluation->id,
                    'evaluation_item_id' => $itemIdInt,
                    'answered_by' => $answeredBy,
                ]) ?: new EvaluationAnswer([
                    'evaluation_id' => $evaluation->id,
                    'evaluation_item_id' => $itemIdInt,
                    'answered_by' => $answeredBy,
                ]);

                // Validate and clamp range according to input_type (ข้อ 15 & 16)
                if (is_array($val)) {
                    $ans->json_value = $val;
                    $ans->numeric_value = null;
                } elseif (is_numeric($val)) {
                    $numVal = floatval($val);
                    if ($item->input_type === 'pdca_level' || $item->input_type === 'scale_1_5') {
                        $numVal = max(1.0, min(5.0, $numVal));
                    } elseif ($item->input_type === 'percentage') {
                        $numVal = max(0.0, min(100.0, $numVal));
                    } else {
                        $maxScore = floatval($item->max_score ?: 100);
                        $numVal = max(0.0, min($maxScore, $numVal));
                    }
                    $ans->numeric_value = $numVal;
                    $ans->text_value = strval($numVal);
                } else {
                    $ans->text_value = mb_substr(strval($val), 0, 5000);
                }
                $ans->save(false);
            }
        }

        // Save Competency Answers
        if (is_array($compAnswersData)) {
            $validCompIds = $evaluation->templateVersion ? $evaluation->templateVersion->getCompetencyDefinitions()->select('id')->column() : [];
            foreach ($compAnswersData as $compDefId => $cData) {
                $compDefIdInt = (int)$compDefId;
                if ($compDefIdInt <= 0 || !in_array($compDefIdInt, $validCompIds)) continue;

                $cAns = EvaluationCompetencyAnswer::findOne([
                    'evaluation_id' => $evaluation->id,
                    'competency_definition_id' => $compDefIdInt,
                    'answered_by' => $answeredBy,
                ]) ?: new EvaluationCompetencyAnswer([
                    'evaluation_id' => $evaluation->id,
                    'competency_definition_id' => $compDefIdInt,
                    'answered_by' => $answeredBy,
                ]);

                $level = intval($cData['level'] ?? 3);
                if ($level < 1) $level = 1;
                if ($level > 5) $level = 5;

                $cAns->level_value = $level;
                $cAns->gap_summary = isset($cData['gap']) ? mb_substr(strval($cData['gap']), 0, 255) : null;
                $cAns->importance = isset($cData['importance']) ? mb_substr(strval($cData['importance']), 0, 50) : null;
                $cAns->idp_plan = isset($cData['idp']) ? mb_substr(strval($cData['idp']), 0, 1000) : null;
                $cAns->save(false);
            }
        }

        // Save IDP Rows (Form 1 Section 3 - Individual Development Plan)
        $idpRows = Yii::$app->request->post('idp_rows');
        if ($idpRows !== null) {
            $sanitizedIdp = [];
            if (is_array($idpRows)) {
                foreach ($idpRows as $row) {
                    if (!is_array($row)) continue;
                    $topic = trim((string)($row['topic'] ?? ''));
                    $method = trim((string)($row['method'] ?? ''));
                    $timeline = trim((string)($row['timeline'] ?? ''));
                    if ($topic !== '' || $method !== '' || $timeline !== '') {
                        $sanitizedIdp[] = [
                            'topic' => mb_substr($topic, 0, 500),
                            'method' => mb_substr($method, 0, 500),
                            'timeline' => mb_substr($timeline, 0, 255),
                        ];
                    }
                }
            }
            $evaluation->idp_data = $sanitizedIdp;
            $evaluation->save(false);
        }

        // Save Supervisor comments if sent
        $cStrength = $request->post('supervisor_comment_strength');
        $cImprovement = $request->post('supervisor_comment_improvement');
        $cSuggestion = $request->post('supervisor_comment_suggestion');
        $empRec = $request->post('employment_recommendation');

        if ($cStrength !== null || $cImprovement !== null || $cSuggestion !== null || $empRec !== null) {
            if ($answeredBy === 'l2') {
                if ($cStrength !== null) $evaluation->l2_comment_strength = mb_substr((string)$cStrength, 0, 2000);
                if ($cImprovement !== null) $evaluation->l2_comment_improvement = mb_substr((string)$cImprovement, 0, 2000);
                if ($cSuggestion !== null) $evaluation->l2_comment_suggestion = mb_substr((string)$cSuggestion, 0, 2000);
                $evaluation->supervisor_comment_strength = $evaluation->l2_comment_strength ?: $evaluation->l1_comment_strength;
                $evaluation->supervisor_comment_improvement = $evaluation->l2_comment_improvement ?: $evaluation->l1_comment_improvement;
                $evaluation->supervisor_comment_suggestion = $evaluation->l2_comment_suggestion ?: $evaluation->l1_comment_suggestion;
            } else {
                if ($cStrength !== null) $evaluation->l1_comment_strength = mb_substr((string)$cStrength, 0, 2000);
                if ($cImprovement !== null) $evaluation->l1_comment_improvement = mb_substr((string)$cImprovement, 0, 2000);
                if ($cSuggestion !== null) $evaluation->l1_comment_suggestion = mb_substr((string)$cSuggestion, 0, 2000);
                $evaluation->supervisor_comment_strength = $evaluation->l1_comment_strength;
                $evaluation->supervisor_comment_improvement = $evaluation->l1_comment_improvement;
                $evaluation->supervisor_comment_suggestion = $evaluation->l1_comment_suggestion;
            }
            if ($empRec !== null) {
                $evaluation->employment_recommendation = mb_substr((string)$empRec, 0, 50);
            }
            $evaluation->save(false);
        }

        // Run Score Engine calculation preview
        $result = EvaluationCalculatorService::calculate($evaluation, null, true);

        return [
            'success' => true,
            'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
            'saved_at' => date('H:i:s'),
            'score_preview' => [
                'final_percentage' => $result ? $result->final_percentage : 0,
                'performance_level' => $result ? $result->performance_level : '-',
                'self_total' => $result ? $result->self_total_score : 0,
                'supervisor_total' => $result ? $result->supervisor_total_score : 0,
            ]
        ];
    }

    /**
     * Submit Self Assessment
     */
    public function actionSubmitSelf($id)
    {
        $evaluation = $this->findModel($id);
        $personnel = $this->getCurrentPersonnel();

        if ($evaluation->personnel_id !== $personnel->id) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ส่งแบบประเมินนี้');
        }

        // 1. Timeline Enforcement
        if ($evaluation->cycle) {
            $nowTime = time();
            $start = !empty($evaluation->cycle->self_assessment_start) ? strtotime((string)$evaluation->cycle->self_assessment_start) : null;
            $end = !empty($evaluation->cycle->self_assessment_end) ? strtotime((string)$evaluation->cycle->self_assessment_end) : null;

            if ($start && $nowTime < $start) {
                Yii::$app->session->setFlash('warning', 'ยังไม่ถึงกำหนดเวลาเปิดให้ประเมินตนเอง (เปิด: ' . Yii::$app->formatter->asDatetime($start, 'php:d/m/Y H:i') . ')');
                return $this->redirect(['self-assess', 'id' => $id]);
            }
            if ($end && $nowTime > $end) {
                Yii::$app->session->setFlash('error', 'สิ้นสุดระยะเวลาการประเมินตนเองแล้ว (ปิดเมื่อ: ' . Yii::$app->formatter->asDatetime($end, 'php:d/m/Y H:i') . ')');
                return $this->redirect(['self-assess', 'id' => $id]);
            }
        }

        // 2. Validate Main work total weight = 80 for Civil/Univ
        $pType = $evaluation->personnel?->personnelType?->code;
        if (in_array($pType, ['CIVIL', 'UNIVERSITY'], true)) {
            $secMain = $evaluation->templateVersion->getSections()->where(['section_code' => 'MAIN_WORK'])->one();
            if ($secMain && !empty($secMain->items)) {
                $mainItem = $secMain->items[0];
                $mainAns = EvaluationAnswer::findOne([
                    'evaluation_id' => $evaluation->id,
                    'evaluation_item_id' => $mainItem->id,
                    'answered_by' => 'self',
                ]);
                $mainRows = ($mainAns && is_array($mainAns->json_value)) ? $mainAns->json_value : [];
                $totalWeight = 0;
                foreach ($mainRows as $r) {
                    $totalWeight += floatval($r['weight'] ?? 0);
                }
                if (abs($totalWeight - 80.0) > 0.05) {
                    Yii::$app->session->setFlash('danger', "ไม่สามารถส่งแบบประเมินได้ เนื่องจากค่าน้ำหนักภาระงานหลักรวมกันได้ {$totalWeight}% (ต้องรวมกันได้ 80.0% พอดี)");
                    return $this->redirect(['self-assess', 'id' => $id]);
                }
            }
        }

        // Determine target status based on personnel hierarchy
        $isSectionHead = $personnel->isSectionHead();
        $isDivisionHead = $personnel->isDivisionHead();

        $tx = Yii::$app->db->beginTransaction();
        try {
            if ($isSectionHead || $isDivisionHead || empty($personnel->supervisor_id) || $personnel->supervisor_id === $personnel->division_head_id) {
                // หัวหน้างาน หรือผู้ที่ขึ้นตรงกับหัวหน้าฝ่าย -> ส่งตรงหาหัวหน้าฝ่าย (L2)
                $evaluation->status = Evaluation::STATUS_SUBMITTED_L2;
                $evaluation->evaluator_id = $personnel->division_head_id ?: $personnel->supervisor_id;
                $evaluation->evaluator_l2_id = $evaluation->evaluator_id;
            } else {
                // พนักงานทั่วไป -> ส่งหาหัวหน้างาน (L1)
                $evaluation->status = Evaluation::STATUS_SUBMITTED_L1;
                $evaluation->evaluator_id = $personnel->supervisor_id;
                $evaluation->evaluator_l1_id = $personnel->supervisor_id;
                $evaluation->evaluator_l2_id = $personnel->division_head_id;
            }

            $evaluation->self_submitted_at = date('Y-m-d H:i:s');
            $evaluation->save(false);

            // Run score calculation
            EvaluationCalculatorService::calculate($evaluation);

            AuditLog::log('submit_self_assessment', 'Evaluation', $evaluation->id);

            // Notify Supervisor
            $targetEvaluator = ($evaluation->status === Evaluation::STATUS_SUBMITTED_L2) ? $evaluation->evaluatorL2 : $evaluation->evaluatorL1;
            if ($targetEvaluator && $targetEvaluator->user_id) {
                Notification::send(
                    $targetEvaluator->user_id,
                    'evaluation_submitted',
                    "มีแบบประเมินใหม่รอการตรวจประเมิน",
                    "{$personnel->fullName} ได้ส่งแบบประเมินตนเองแล้ว กรุณาดำเนินการประเมิน",
                    $evaluation->id,
                    'Evaluation'
                );
            }

            $tx->commit();
            Yii::$app->session->setFlash('success', 'ส่งแบบประเมินตนเองเรียบร้อยแล้ว ระบบได้แจ้งผู้บังคับบัญชาตามสายงานแล้ว');
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการส่งแบบประเมิน: ' . $e->getMessage());
            return $this->redirect(['self-assess', 'id' => $id]);
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Submit Supervisor Evaluation (Handles L1 Section Head & L2 Division Head)
     */
    public function actionSubmitSupervisor($id)
    {
        $evaluation = $this->findModel($id);
        $request = Yii::$app->request;
        $currPersonnel = $this->getCurrentPersonnel();
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');

        $isSamePersonL1L2 = ($evaluation->evaluator_l1_id && $evaluation->evaluator_l2_id && $evaluation->evaluator_l1_id === $evaluation->evaluator_l2_id)
            || empty($evaluation->evaluator_l2_id)
            || ($evaluation->evaluatorL1 && $evaluation->evaluatorL2 && $evaluation->evaluatorL1->fullName === $evaluation->evaluatorL2->fullName);

        $isL1 = $currPersonnel && (($evaluation->evaluator_l1_id === $currPersonnel->id) || ($evaluation->personnel && $evaluation->personnel->supervisor_id === $currPersonnel->id));
        $isL2 = $currPersonnel && (($evaluation->evaluator_l2_id === $currPersonnel->id) || ($evaluation->personnel && $evaluation->personnel->division_head_id === $currPersonnel->id) || $currPersonnel->isDivisionHead() || ($isSamePersonL1L2 && $isL1) || $isAdmin);

        if (!$isL1 && !$isL2 && !$isAdmin) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ประเมินบุคลากรท่านนี้ (ไม่ใช่ผู้บังคับบัญชาตามสายงาน)');
        }

        // Timeline check
        if ($evaluation->cycle) {
            $nowTime = time();
            $start = !empty($evaluation->cycle->supervisor_eval_start) ? strtotime((string)$evaluation->cycle->supervisor_eval_start) : null;
            $end = !empty($evaluation->cycle->supervisor_eval_end) ? strtotime((string)$evaluation->cycle->supervisor_eval_end) : null;

            if ($start && $nowTime < $start) {
                Yii::$app->session->setFlash('warning', 'ยังไม่ถึงกำหนดเวลาเปิดให้หัวหน้าประเมิน (เปิด: ' . Yii::$app->formatter->asDatetime($start, 'php:d/m/Y H:i') . ')');
                return $this->redirect(['supervisor-assess', 'id' => $id]);
            }
            if ($end && $nowTime > $end) {
                Yii::$app->session->setFlash('error', 'สิ้นสุดระยะเวลาการประเมินโดยหัวหน้าแล้ว (ปิดเมื่อ: ' . Yii::$app->formatter->asDatetime($end, 'php:d/m/Y H:i') . ')');
                return $this->redirect(['supervisor-assess', 'id' => $id]);
            }
        }

        // Server strictly dictates tier and enforces state machine (Never trust browser input)
        if ($evaluation->status === Evaluation::STATUS_SUBMITTED_L1 || $evaluation->status === Evaluation::STATUS_SUBMITTED) {
            if (!$isL1 && !$isAdmin) {
                throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ประเมินในขั้นตอนนี้ (ไม่ใช่ผู้ประเมินชั้นต้น L1)');
            }
            $evaluatorTier = 'l1';
        } elseif ($evaluation->status === Evaluation::STATUS_SUBMITTED_L2) {
            if (!$isL2 && !$isAdmin) {
                throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ประเมินในขั้นตอนนี้ (ไม่ใช่ผู้ประเมินชั้นที่ 2 L2)');
            }
            $evaluatorTier = 'l2';
        } else {
            throw new BadRequestHttpException('แบบประเมินไม่ได้อยู่ในสถานะที่เปิดให้ประเมิน (สถานะปัจจุบัน: ' . $evaluation->getStatusLabel() . ')');
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            // 1. Save Main Work Items from Evaluator (CIVIL/UNIV)
            $mainWork = $request->post('main_work');
            if ($mainWork && is_array($mainWork)) {
                $secMain = $evaluation->templateVersion->getSections()->where(['section_code' => 'MAIN_WORK'])->one();
                if ($secMain && !empty($secMain->items)) {
                    $mainItem = $secMain->items[0];
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $mainItem->id, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $mainItem->id, 'answered_by' => $evaluatorTier]);
                    $ans->json_value = array_values($mainWork);
                    $ans->save(false);
                }
            }

            // 1.1 Save GOVT Main Work Items
            $govtMain = $request->post('govt_main');
            if ($govtMain && is_array($govtMain)) {
                $secGovtMain = $evaluation->templateVersion->getSections()->where(['section_code' => 'GOVT_MAIN_WORK'])->one();
                if ($secGovtMain && !empty($secGovtMain->items)) {
                    $govtMainItem = $secGovtMain->items[0];
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $govtMainItem->id, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $govtMainItem->id, 'answered_by' => $evaluatorTier]);
                    $ans->json_value = array_values($govtMain);
                    $ans->save(false);
                }
            }

            // 1.2 Save GOVT Secondary Checklist
            $govtSecCheck = $request->post('govt_sec_check');
            $govtSecDetail = $request->post('govt_sec_detail', []);
            if ($govtSecCheck && is_array($govtSecCheck)) {
                $secGovtSec = $evaluation->templateVersion->getSections()->where(['section_code' => 'GOVT_SECONDARY_WORK'])->one();
                if ($secGovtSec && !empty($secGovtSec->items)) {
                    $govtSecItem = $secGovtSec->items[0];
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $govtSecItem->id, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $govtSecItem->id, 'answered_by' => $evaluatorTier]);
                    
                    if (empty($govtSecDetail)) {
                        $selfAns = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $govtSecItem->id, 'answered_by' => 'self']);
                        $selfVal = $selfAns ? (is_string($selfAns->json_value) ? json_decode($selfAns->json_value, true) : $selfAns->json_value) : [];
                        $govtSecDetail = $selfVal['details'] ?? [];
                    }

                    $ans->json_value = [
                        'selected' => array_values($govtSecCheck),
                        'details' => $govtSecDetail
                    ];
                    $ans->save(false);
                }
            }

            // 1.3 Save SPECIAL Secondary Checklist
            $specSecCheck = $request->post('spec_sec_check');
            $specSecDetail = $request->post('spec_sec_detail', []);
            if ($specSecCheck && is_array($specSecCheck)) {
                $secSpecSec = $evaluation->templateVersion->getSections()->where(['section_code' => 'SPEC_SECTION_1'])->one();
                if ($secSpecSec && count($secSpecSec->items) >= 6) {
                    $specSecItem = $secSpecSec->items[5];
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $specSecItem->id, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $specSecItem->id, 'answered_by' => $evaluatorTier]);
                    
                    if (empty($specSecDetail)) {
                        $selfAns = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $specSecItem->id, 'answered_by' => 'self']);
                        $selfVal = $selfAns ? (is_string($selfAns->json_value) ? json_decode($selfAns->json_value, true) : $selfAns->json_value) : [];
                        $specSecDetail = $selfVal['details'] ?? [];
                    }

                    $ans->json_value = [
                        'selected' => array_values($specSecCheck),
                        'details' => $specSecDetail
                    ];
                    $ans->save(false);
                }
            }

            // 2. Save Competencies from Evaluator
            $comps = $request->post('comp');
            if ($comps && is_array($comps)) {
                foreach ($comps as $compId => $cData) {
                    $ca = EvaluationCompetencyAnswer::findOne(['evaluation_id' => $evaluation->id, 'competency_definition_id' => $compId, 'answered_by' => $evaluatorTier]) ?: new EvaluationCompetencyAnswer(['evaluation_id' => $evaluation->id, 'competency_definition_id' => $compId, 'answered_by' => $evaluatorTier]);
                    $ca->level_value = intval($cData['level'] ?? 3);
                    $ca->idp_plan = $cData['idp'] ?? null;
                    $ca->save(false);
                }
            }

            // 3. Save Checkboxes and Radios
            $checkboxes = $request->post('item_checkbox');
            if ($checkboxes && is_array($checkboxes)) {
                foreach ($checkboxes as $itemId => $arr) {
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $itemId, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $itemId, 'answered_by' => $evaluatorTier]);
                    $ans->json_value = $arr;
                    $ans->save(false);
                }
            }
            $radios = $request->post('item_radio');
            if ($radios && is_array($radios)) {
                foreach ($radios as $itemId => $val) {
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $itemId, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $itemId, 'answered_by' => $evaluatorTier]);
                    $ans->numeric_value = floatval($val);
                    $ans->text_value = strval($val);
                    $ans->save(false);
                }
            }
            
            $itemScores = $request->post('item_score');
            if ($itemScores && is_array($itemScores)) {
                foreach ($itemScores as $itemId => $val) {
                    $ans = EvaluationAnswer::findOne(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $itemId, 'answered_by' => $evaluatorTier]) ?: new EvaluationAnswer(['evaluation_id' => $evaluation->id, 'evaluation_item_id' => $itemId, 'answered_by' => $evaluatorTier]);
                    $ans->numeric_value = floatval($val);
                    $ans->text_value = strval($val);
                    $ans->save(false);
                }
            }

            if ($evaluatorTier === 'l1') {
                $submitAction = $request->post('submit_action');
                if ($isSamePersonL1L2 && ($submitAction === 'complete' || empty($evaluation->evaluator_l2_id))) {
                    // One-step Completion when L1 and L2 are the same evaluator
                    $evaluation->l1_comment_strength = $request->post('l1_comment_strength', $request->post('supervisor_comment_strength', $request->post('comment_strength')));
                    $evaluation->l1_comment_improvement = $request->post('l1_comment_improvement', $request->post('supervisor_comment_improvement', $request->post('comment_improvement')));
                    $evaluation->l1_comment_suggestion = $request->post('l1_comment_suggestion', $request->post('supervisor_comment_suggestion', $request->post('comment_suggestion')));
                    $evaluation->l1_evaluated_at = date('Y-m-d H:i:s');
                    $evaluation->evaluator_l1_id = $currPersonnel ? $currPersonnel->id : null;

                    $evaluation->l2_comment_strength = $evaluation->l1_comment_strength;
                    $evaluation->l2_comment_improvement = $evaluation->l1_comment_improvement;
                    $evaluation->l2_comment_suggestion = $evaluation->l1_comment_suggestion;
                    $evaluation->supervisor_comment_strength = $evaluation->l1_comment_strength;
                    $evaluation->supervisor_comment_improvement = $evaluation->l1_comment_improvement;
                    $evaluation->supervisor_comment_suggestion = $evaluation->l1_comment_suggestion;
                    $evaluation->employment_recommendation = $request->post('employment_recommendation');

                    $evaluation->status = Evaluation::STATUS_COMPLETED;
                    $evaluation->l2_evaluated_at = date('Y-m-d H:i:s');
                    $evaluation->supervisor_evaluated_at = date('Y-m-d H:i:s');
                    $evaluation->completed_at = date('Y-m-d H:i:s');
                    if ($currPersonnel) {
                        $evaluation->evaluator_l2_id = $evaluation->evaluator_l2_id ?: $currPersonnel->id;
                    }
                    $evaluation->save(false);

                    $result = EvaluationCalculatorService::calculate($evaluation, $currPersonnel ? $currPersonnel->user_id : Yii::$app->user->id, true);
                    AuditLog::log('submit_supervisor_evaluation', 'Evaluation', $evaluation->id, null, ['final_score' => $result ? $result->final_score : 0]);

                    if ($evaluation->personnel && $evaluation->personnel->user_id) {
                        Notification::send(
                            $evaluation->personnel->user_id,
                            'evaluation_completed',
                            "การประเมินผลการปฏิบัติราชการเสร็จสมบูรณ์",
                            "ผู้บังคับบัญชาได้ประเมินผลการปฏิบัติราชการของคุณเรียบร้อยแล้ว สามารถดูผลคะแนนและข้อเสนอแนะได้แล้ว",
                            $evaluation->id,
                            'Evaluation'
                        );
                    }

                    $tx->commit();
                    $finalPct = $result ? $result->final_percentage : 0;
                    $perfLevel = $result ? $result->performance_level : '-';
                    Yii::$app->session->setFlash('success', "บันทึกและตัดสินผลการประเมินเรียบร้อยแล้ว (ดำเนินการ L1 & L2 พร้อมกัน) คะแนนรวม: {$finalPct}% (ระดับ: {$perfLevel})");
                    return $this->redirect(['view', 'id' => $id]);
                }

                // L1 (Section Head) Submit -> ส่งต่อให้หัวหน้าฝ่าย (L2)
                $evaluation->l1_comment_strength = $request->post('l1_comment_strength', $request->post('supervisor_comment_strength', $request->post('comment_strength')));
                $evaluation->l1_comment_improvement = $request->post('l1_comment_improvement', $request->post('supervisor_comment_improvement', $request->post('comment_improvement')));
                $evaluation->l1_comment_suggestion = $request->post('l1_comment_suggestion', $request->post('supervisor_comment_suggestion', $request->post('comment_suggestion')));
                $evaluation->l1_evaluated_at = date('Y-m-d H:i:s');
                $evaluation->evaluator_l1_id = $currPersonnel ? $currPersonnel->id : null;
                $evaluation->status = Evaluation::STATUS_SUBMITTED_L2;
                $evaluation->save(false);

                $result = EvaluationCalculatorService::calculate($evaluation, $currPersonnel ? $currPersonnel->user_id : Yii::$app->user->id, true);

                AuditLog::log('submit_l1_evaluation', 'Evaluation', $evaluation->id, null, ['l1_score' => $result->l1_total_score ?: $result->supervisor_total_score]);

                // Notify Division Head (L2)
                if ($evaluation->evaluatorL2 && $evaluation->evaluatorL2->user_id) {
                    Notification::send(
                        $evaluation->evaluatorL2->user_id,
                        'evaluation_submitted_l2',
                        "มีแบบประเมินผ่านการตรวจจากหัวหน้างานแล้ว (L1 -> L2)",
                        "แบบประเมินของ {$evaluation->personnel->fullName} ได้รับการประเมินขั้นต้นแล้ว รอการพิจารณาตัดสินขั้นสุดท้าย",
                        $evaluation->id,
                        'Evaluation'
                    );
                }

                $tx->commit();
                Yii::$app->session->setFlash('success', "บันทึกการประเมินขั้นต้น (หัวหน้างาน L1) เรียบร้อยแล้ว ระบบได้ส่งต่อให้หัวหน้าฝ่าย (L2) ดำเนินการต่อไป");
                return $this->redirect(['view', 'id' => $id]);
            } else {
                // L2 (Division Head) Submit -> เสร็จสมบูรณ์ (Workflow จบที่ L2)
                $evaluation->l2_comment_strength = $request->post('l2_comment_strength', $request->post('supervisor_comment_strength', $request->post('comment_strength')));
                $evaluation->l2_comment_improvement = $request->post('l2_comment_improvement', $request->post('supervisor_comment_improvement', $request->post('comment_improvement')));
                $evaluation->l2_comment_suggestion = $request->post('l2_comment_suggestion', $request->post('supervisor_comment_suggestion', $request->post('comment_suggestion')));
                $evaluation->supervisor_comment_strength = $evaluation->l2_comment_strength ?: $evaluation->l1_comment_strength;
                $evaluation->supervisor_comment_improvement = $evaluation->l2_comment_improvement ?: $evaluation->l1_comment_improvement;
                $evaluation->supervisor_comment_suggestion = $evaluation->l2_comment_suggestion ?: $evaluation->l1_comment_suggestion;
                $evaluation->employment_recommendation = $request->post('employment_recommendation');

                $evaluation->status = Evaluation::STATUS_COMPLETED;
                $evaluation->l2_evaluated_at = date('Y-m-d H:i:s');
                $evaluation->supervisor_evaluated_at = date('Y-m-d H:i:s');
                $evaluation->completed_at = date('Y-m-d H:i:s');
                $evaluation->evaluator_l2_id = $currPersonnel ? $currPersonnel->id : null;
                $evaluation->save(false);

                // Final score calculation with forced recalculation
                $result = EvaluationCalculatorService::calculate($evaluation, $currPersonnel ? $currPersonnel->user_id : Yii::$app->user->id, true);

                AuditLog::log('submit_supervisor_evaluation', 'Evaluation', $evaluation->id, null, ['final_score' => $result ? $result->final_score : 0]);

                // Notify Evaluatee
                if ($evaluation->personnel && $evaluation->personnel->user_id) {
                    Notification::send(
                        $evaluation->personnel->user_id,
                        'evaluation_completed',
                        "การประเมินผลการปฏิบัติราชการเสร็จสมบูรณ์",
                        "ผู้บังคับบัญชาได้ประเมินผลการปฏิบัติราชการของคุณเรียบร้อยแล้ว สามารถดูผลคะแนนและข้อเสนอแนะได้แล้ว",
                        $evaluation->id,
                        'Evaluation'
                    );
                }

                $tx->commit();
                $finalPct = $result ? $result->final_percentage : 0;
                $perfLevel = $result ? $result->performance_level : '-';
                Yii::$app->session->setFlash('success', "บันทึกผลการประเมินขั้นสุดท้าย (หัวหน้าฝ่าย L2) และสรุปผลเรียบร้อยแล้ว คะแนนรวม: {$finalPct}% (ระดับ: {$perfLevel})");
                return $this->redirect(['view', 'id' => $id]);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการบันทึกผลการประเมิน: ' . $e->getMessage());
            return $this->redirect(['supervisor-assess', 'id' => $id]);
        }
    }

    /**
     * Return evaluation to subordinate for revision
     */
    public function actionReturn($id)
    {
        $evaluation = $this->findModel($id);
        $currPersonnel = $this->getCurrentPersonnel();
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');
        $reason = trim((string)Yii::$app->request->post('return_reason', ''));
        if ($reason === '' || mb_strlen($reason) > 5000) {
            Yii::$app->session->setFlash('error', 'กรุณาระบุเหตุผลในการส่งกลับแบบประเมินให้ชัดเจน');
            return $this->redirect(['supervisor-assess', 'id' => $id]);
        }
        $returnTo = Yii::$app->request->post('return_to', 'staff'); // 'staff' or 'l1'

        $isL1 = $currPersonnel && (($evaluation->evaluator_l1_id === $currPersonnel->id) || ($evaluation->personnel->supervisor_id === $currPersonnel->id));
        $isL2 = $currPersonnel && (($evaluation->evaluator_l2_id === $currPersonnel->id) || ($evaluation->personnel->division_head_id === $currPersonnel->id) || $currPersonnel->isDivisionHead() || $isAdmin);

        if (!$isL1 && !$isL2 && !$isAdmin) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ส่งแบบประเมินกลับเพื่อแก้ไข (ไม่ใช่ผู้บังคับบัญชาตามสายงาน)');
        }

        $returnableStatuses = [
            Evaluation::STATUS_SUBMITTED,
            Evaluation::STATUS_SUBMITTED_L1,
            Evaluation::STATUS_SUBMITTED_L2,
            Evaluation::STATUS_SUPERVISOR_REVIEW,
        ];
        if (!in_array($evaluation->status, $returnableStatuses, true)) {
            throw new BadRequestHttpException('ไม่สามารถส่งกลับแบบประเมินในสถานะปัจจุบันได้');
        }

        if ($isL2 && $returnTo === 'l1') {
            $evaluation->status = Evaluation::STATUS_SUBMITTED_L1;
            $evaluation->returned_by_role = 'l2';
            $msg = 'ส่งแบบประเมินกลับให้หัวหน้างาน (L1) ทบทวนเรียบร้อยแล้ว';
        } else {
            $evaluation->status = Evaluation::STATUS_RETURNED;
            $evaluation->returned_by_role = $isL2 ? 'l2' : 'l1';
            $msg = 'ส่งแบบประเมินกลับให้ผู้รับการประเมินแก้ไขเรียบร้อยแล้ว';
        }

        $evaluation->returned_at = date('Y-m-d H:i:s');
        $evaluation->return_reason = $reason;
        $evaluation->save(false);

        AuditLog::log('return_evaluation', 'Evaluation', $evaluation->id, null, ['reason' => $reason, 'returned_by_role' => $evaluation->returned_by_role]);

        if ($isL2 && $returnTo === 'l1') {
            $l1UserId = ($evaluation->evaluatorL1 && $evaluation->evaluatorL1->user_id) 
                ? $evaluation->evaluatorL1->user_id 
                : (($evaluation->personnel && $evaluation->personnel->supervisor && $evaluation->personnel->supervisor->user_id) ? $evaluation->personnel->supervisor->user_id : null);

            if ($l1UserId) {
                Notification::send(
                    $l1UserId,
                    'evaluation_returned_l1',
                    "แบบประเมินถูกส่งกลับจากหัวหน้าฝ่าย (L2) เพื่อให้ทบทวน",
                    "ผู้ประเมินระดับ ๒ ส่งแบบประเมินของ " . ($evaluation->personnel ? $evaluation->personnel->fullName : '') . " กลับให้ท่านทบทวน: {$reason}",
                    $evaluation->id,
                    'Evaluation'
                );
            }
        } else {
            if ($evaluation->personnel && $evaluation->personnel->user_id) {
                Notification::send(
                    $evaluation->personnel->user_id,
                    'evaluation_returned',
                    "แบบประเมินถูกส่งกลับเพื่อแก้ไข",
                    "ผู้บังคับบัญชาส่งแบบประเมินกลับ: {$reason}",
                    $evaluation->id,
                    'Evaluation'
                );
            }
        }

        Yii::$app->session->setFlash('info', $msg);
        return $this->redirect(['index']);
    }

    /**
     * Acknowledge evaluation result by evaluatee
     */
    public function actionAcknowledge($id)
    {
        $evaluation = $this->findModel($id);
        $personnel = $this->getCurrentPersonnel();

        if ($evaluation->personnel_id !== $personnel->id) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์รับทราบผลการประเมินนี้');
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $evaluation->status = Evaluation::STATUS_ACKNOWLEDGED;
            $evaluation->acknowledgement_at = date('Y-m-d H:i:s');
            if (!$evaluation->save(false)) {
                throw new \Exception('บันทึกสถานะการรับทราบผลไม่สำเร็จ');
            }

            AuditLog::log('acknowledge_evaluation_result', 'Evaluation', $evaluation->id, null, [
                'status' => Evaluation::STATUS_ACKNOWLEDGED,
                'acknowledged_at' => $evaluation->acknowledgement_at,
            ]);

            $tx->commit();
        } catch (\Throwable $ex) {
            if ($tx->getIsActive()) $tx->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการรับทราบผล: ' . $ex->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }

        Yii::$app->session->setFlash('success', 'บันทึกการรับทราบผลการประเมินเรียบร้อยแล้ว');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Download evidence file securely with access control
     */
    public function actionDownloadEvidence($id)
    {
        $evidence = EvidenceFile::findOne((int)$id);
        if (!$evidence || $evidence->deleted_at !== null) {
            throw new NotFoundHttpException('ไม่พบไฟล์หลักฐาน');
        }

        $evaluation = $evidence->evaluation;
        if (!$evaluation) {
            throw new NotFoundHttpException('ไม่พบแบบประเมินที่เกี่ยวข้อง');
        }

        $personnel = $this->getCurrentPersonnel();
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');
        $isOwner = $personnel && ((int)$evaluation->personnel_id === (int)$personnel->id);
        $isEvaluator = $personnel && \common\services\EvaluationAccessService::isEvaluator($evaluation, $personnel);

        if (!$isAdmin && !$isOwner && !$isEvaluator) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าถึงไฟล์หลักฐานนี้');
        }

        // Support both private storage and legacy uploads
        $fullPath = Yii::getAlias('@frontend/runtime/' . $evidence->file_path);
        if (!file_exists($fullPath)) {
            $fullPath = Yii::getAlias('@frontend/web/' . $evidence->file_path);
        }
        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('ไฟล์หลักฐานสูญหายหรือไม่พบในระบบ');
        }

        return Yii::$app->response->sendFile($fullPath, $evidence->original_name, [
            'inline' => in_array(strtolower(pathinfo($evidence->original_name, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp']),
        ]);
    }

    /**
     * Upload evidence file via AJAX (Stored in private directory)
     */
    public function actionUploadEvidence()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $evaluationId = (int)Yii::$app->request->post('evaluation_id');
        $itemId = Yii::$app->request->post('evaluation_item_id');

        $evaluation = Evaluation::findOne($evaluationId);
        if (!$evaluation) {
            return ['success' => false, 'message' => 'ไม่พบแบบประเมิน'];
        }

        $personnel = $this->getCurrentPersonnel();
        $isAdmin = Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');
        $isOwner = $personnel && ((int)$evaluation->personnel_id === (int)$personnel->id);
        $isEvaluator = $personnel && \common\services\EvaluationAccessService::isEvaluator($evaluation, $personnel);

        if (!$isAdmin && !$isOwner && !$isEvaluator) {
            return ['success' => false, 'message' => 'คุณไม่มีสิทธิ์อัปโหลดหลักฐานสำหรับแบบประเมินนี้'];
        }

        // Check evaluation status permits upload
        $allowUploadStatuses = [
            Evaluation::STATUS_DRAFT,
            Evaluation::STATUS_SELF_ASSESSMENT,
            Evaluation::STATUS_RETURNED,
            Evaluation::STATUS_SUBMITTED,
            Evaluation::STATUS_SUBMITTED_L1,
            Evaluation::STATUS_SUBMITTED_L2,
            Evaluation::STATUS_SUPERVISOR_REVIEW,
        ];
        if (!in_array($evaluation->status, $allowUploadStatuses, true)) {
            return ['success' => false, 'message' => 'ไม่สามารถอัปโหลดหลักฐานในสถานะปัจจุบันของแบบประเมินได้'];
        }

        // Validate itemId belongs to this evaluation template version
        if (!empty($itemId)) {
            $itemValid = \common\models\EvaluationItem::find()
                ->alias('ei')
                ->innerJoin('{{%evaluation_sections}} sec', 'sec.id = ei.evaluation_section_id')
                ->where(['ei.id' => (int)$itemId, 'sec.template_version_id' => $evaluation->template_version_id])
                ->exists();
            if (!$itemValid) {
                return ['success' => false, 'message' => 'ตัวชี้วัดที่ระบุไม่ตรงกับแบบประเมินนี้'];
            }
        }

        $uploadedFile = UploadedFile::getInstanceByName('evidence_file');
        if (!$uploadedFile) {
            return ['success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด'];
        }

        $maxBytes = 20 * 1024 * 1024;
        if ($uploadedFile->size > $maxBytes) {
            return ['success' => false, 'message' => 'ไฟล์ต้องมีขนาดไม่เกิน 20 MB'];
        }

        // Quota check: max 20 files per evaluation (ข้อ 13)
        $fileCount = EvidenceFile::find()->where(['evaluation_id' => $evaluation->id, 'deleted_at' => null])->count();
        if ($fileCount >= 20) {
            return ['success' => false, 'message' => 'จำนวนไฟล์หลักฐานเต็มแล้ว (สูงสุด 20 ไฟล์ต่อแบบประเมิน)'];
        }

        // Quota check: max 200 MB total size per evaluation (ข้อ 13)
        $totalSize = EvidenceFile::find()->where(['evaluation_id' => $evaluation->id, 'deleted_at' => null])->sum('file_size') ?: 0;
        if (($totalSize + $uploadedFile->size) > 200 * 1024 * 1024) {
            return ['success' => false, 'message' => 'พื้นที่จัดเก็บหลักฐานของแบบประเมินนี้เกินกำหนด (สูงสุด 200 MB)'];
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip'];
        $ext = strtolower($uploadedFile->extension);
        if (!in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'message' => 'ประเภทไฟล์ไม่ได้รับอนุญาต อนุญาตเฉพาะ (PDF, JPG, PNG, Word, Excel, ZIP)'];
        }

        // Strict MIME validation mapped to extension (disallow broad application/octet-stream)
        $expectedMimes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'doc' => ['application/msword', 'application/vnd.ms-office'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
        ];

        if (!empty($uploadedFile->tempName) && file_exists($uploadedFile->tempName)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $uploadedFile->tempName);
            finfo_close($finfo);

            $validMimesForExt = $expectedMimes[$ext] ?? [];
            if (!$realMime || !in_array($realMime, $validMimesForExt, true)) {
                return ['success' => false, 'message' => 'เนื้อหาไฟล์ไม่ปลอดภัยหรือไม่ตรงกับนามสกุลไฟล์ที่ระบุ'];
            }

            // If ZIP archive, inspect inner contents against Zip Bomb, Traversal, and Executables
            if ($ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($uploadedFile->tempName) === true) {
                    $uncompressedTotal = 0;
                    $dangerousExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'bat', 'cmd', 'sh', 'vbs', 'js', 'jar', 'dll', 'scr', 'msi', 'com'];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        if (!$stat) continue;
                        $entryName = $stat['name'];
                        // Check path traversal
                        if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                            $zip->close();
                            return ['success' => false, 'message' => 'ไฟล์ ZIP มีโครงสร้างเส้นทางที่ไม่ปลอดภัย (Path Traversal)'];
                        }
                        // Check dangerous extensions
                        $innerExt = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                        if (in_array($innerExt, $dangerousExts, true)) {
                            $zip->close();
                            return ['success' => false, 'message' => "ไฟล์ ZIP บรรจุไฟล์ประเภทอันตรายที่ไม่ได้รับอนุญาต (.{$innerExt})"];
                        }
                        $uncompressedTotal += $stat['size'];
                    }
                    $zip->close();

                    // Zip Bomb prevention: max 100 MB uncompressed
                    if ($uncompressedTotal > 100 * 1024 * 1024) {
                        return ['success' => false, 'message' => 'ไฟล์ ZIP มีขนาดเมื่อคลายไฟล์ใหญ่เกินกำหนด (ความเสี่ยง Zip Bomb)'];
                    }
                } else {
                    return ['success' => false, 'message' => 'ไม่สามารถเปิดตรวจสอบไฟล์ ZIP ได้ ไฟล์อาจชำรุดหรือไม่สมบูรณ์'];
                }
            }
        }

        // Store inside runtime/private/evidence/ (outside web root)
        $base = Yii::getAlias('@frontend/runtime/private/evidence/' . date('Ym'));
        if (!is_dir($base) && !mkdir($base, 0750, true) && !is_dir($base)) {
            return ['success' => false, 'message' => 'ไม่สามารถสร้างพื้นที่จัดเก็บไฟล์ได้'];
        }

        $storedName = Yii::$app->security->generateRandomString(32) . '.' . $ext;
        $filePath = $base . DIRECTORY_SEPARATOR . $storedName;

        if (!$uploadedFile->saveAs($filePath)) {
            return ['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์ได้'];
        }

        // Antivirus scanning: Mandatory in Production with ClamAV enabled (fail-closed)
        $isProd = defined('YII_ENV_PROD') && YII_ENV_PROD && (getenv('ENABLE_CLAMAV') === '1');
        exec('which clamscan 2>&1', $clamCheck, $clamCheckCode);
        if ($clamCheckCode === 0) {
            $cmd = 'clamscan --no-summary --infected ' . escapeshellarg($filePath) . ' 2>&1';
            exec($cmd, $out, $code);
            if ($code !== 0) {
                @unlink($filePath);
                return ['success' => false, 'message' => 'ไฟล์ไม่ผ่านการตรวจสอบความปลอดภัยของระบบ (ตรวจพบความเสี่ยงหรือมัลแวร์)'];
            }
        } elseif ($isProd) {
            @unlink($filePath);
            return ['success' => false, 'message' => 'ระบบตรวจจับมัลแวร์ (ClamAV) ไม่พร้อมทำงานในระบบจริง เพื่อความปลอดภัยจึงปฏิเสธการอัปโหลด'];
        }

        $evidence = new EvidenceFile();
        $evidence->evaluation_id = $evaluation->id;
        $evidence->evaluation_item_id = $itemId ?: null;
        $evidence->original_name = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', ' ', basename($uploadedFile->name)), 0, 255);
        $evidence->stored_name = $storedName;
        $evidence->file_path = 'private/evidence/' . date('Ym') . '/' . $storedName;
        $evidence->file_size = $uploadedFile->size;
        $evidence->file_type = $realMime ?? ($uploadedFile->type ?: 'application/pdf');
        $evidence->file_hash = hash_file('sha256', $filePath);
        $evidence->uploaded_by = Yii::$app->user->id;
        $evidence->save(false);

        AuditLog::log('upload_evidence', 'EvidenceFile', $evidence->id, null, ['name' => $evidence->original_name]);

        return [
            'success' => true,
            'file' => [
                'id' => $evidence->id,
                'name' => $evidence->original_name,
                'size' => $evidence->getFormattedSize(),
                'url' => $evidence->getFileUrl(),
            ]
        ];
    }

    /**
     * Delete evidence file
     */
    public function actionDeleteEvidence($file_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $file_id ?: Yii::$app->request->post('file_id', Yii::$app->request->post('id'));
        $file = EvidenceFile::findOne($id);
        if (!$file) {
            return ['success' => false, 'message' => 'ไม่พบไฟล์หลักฐาน'];
        }

        // Lock deletion if evaluation is completed or acknowledged (ข้อ 13)
        $evaluation = $file->evaluation;
        if ($evaluation && in_array($evaluation->status, [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED], true)) {
            return ['success' => false, 'message' => 'ไม่สามารถลบหลักฐานได้เนื่องจากแบบประเมินเสร็จสมบูรณ์แล้ว'];
        }

        if ($file->uploaded_by === Yii::$app->user->id || Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin')) {
            $file->deleted_at = date('Y-m-d H:i:s');
            $file->save(false);
            AuditLog::log('delete_evidence', 'EvidenceFile', $file->id);
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Permission denied'];
    }

    /**
     * Find Evaluation Model
     */
    protected function findModel($id)
    {
        if (($model = Evaluation::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('ไม่พบแบบประเมินที่ระบุ');
    }

    /**
     * Helper to get currently logged in Personnel record
     */
    protected function getCurrentPersonnel()
    {
        return Personnel::findOne(['user_id' => Yii::$app->user->id]);
    }
}
