<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use common\models\EvaluationTemplate;
use common\models\TemplateVersion;
use common\models\EvaluationSection;
use common\models\EvaluationItem;
use common\models\EvaluationCriteria;
use common\models\CompetencyDefinition;
use common\models\CompetencyLevel;
use common\models\Department;
use common\models\Personnel;
use common\models\PersonnelType;
use common\models\Evaluation;
use common\models\EvaluationCycle;
use common\models\CycleTemplateMapping;
use common\models\AuditLog;

/**
 * TemplateBuilderController manages Department-based Dynamic Evaluation Templates and KPI Builder.
 */
class TemplateBuilderController extends Controller
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
                        'roles' => ['admin', 'superadmin', 'central_hr', 'division_head'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'clone' => ['POST'],
                    'assign-template' => ['POST'],
                    'reset-to-central' => ['POST'],
                    'save-section' => ['POST'],
                    'delete-section' => ['POST'],
                    'save-item' => ['POST'],
                    'delete-item' => ['POST'],
                    'save-competency' => ['POST'],
                    'delete-competency' => ['POST'],
                    'save-all' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Helper to get current admin context and department
     */
    protected function getAdminContext()
    {
        $isSuperAdmin = Department::isCentralAdmin();
        $departmentId = Department::getCurrentUserDeptId();
        $department = $departmentId ? Department::findOne($departmentId) : null;
        $personnel = Personnel::findOne(['user_id' => Yii::$app->user->id]);

        return [
            'isSuperAdmin' => $isSuperAdmin,
            'personnel' => $personnel,
            'departmentId' => $departmentId,
            'department' => $department,
        ];
    }

    protected function assertCanReadTemplate(EvaluationTemplate $template)
    {
        $adminCtx = $this->getAdminContext();
        if ($adminCtx['isSuperAdmin'] || $template->department_id === null) {
            return true;
        }
        $scopedDeptIds = $adminCtx['departmentId'] ? Department::getAllScopedDeptIds($adminCtx['departmentId']) : [];
        if (!in_array((int)$template->department_id, $scopedDeptIds, true)) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าถึงแบบประเมินของหน่วยงานอื่น');
        }
        return true;
    }

    protected function assertCanEditTemplate(EvaluationTemplate $template)
    {
        $adminCtx = $this->getAdminContext();
        if ($adminCtx['isSuperAdmin']) {
            return true;
        }
        if ($template->department_id === null) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์แก้ไขหรือลบแบบประเมินส่วนกลาง (Global Template) ซึ่งสงวนสิทธิ์สำหรับ Superadmin เท่านั้น');
        }
        $scopedDeptIds = $adminCtx['departmentId'] ? Department::getAllScopedDeptIds($adminCtx['departmentId']) : [];
        if (!in_array((int)$template->department_id, $scopedDeptIds, true)) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์จัดการแบบประเมินของหน่วยงานอื่น');
        }
        return true;
    }

    protected function assertCanEditVersion(TemplateVersion $version)
    {
        $template = $version->evaluationTemplate;
        if (!$template) {
            throw new NotFoundHttpException('ไม่พบแบบประเมินหลัก');
        }
        $this->assertCanEditTemplate($template);

        // Lock version if already referenced by any evaluations
        $evalCount = \common\models\Evaluation::find()->where(['template_version_id' => $version->id])->count();
        if ($evalCount > 0) {
            throw new ForbiddenHttpException("เวอร์ชันแบบประเมินนี้ถูกนำไปใช้ประเมินแล้ว ({$evalCount} รายการ) ระบบได้ทำการล็อกโครงสร้างเพื่อความถูกต้องของประวัติการประเมิน");
        }
        return true;
    }

    protected function assertCanEditSection(EvaluationSection $section)
    {
        $version = $section->templateVersion;
        if (!$version) {
            throw new NotFoundHttpException('ไม่พบเวอร์ชันแบบประเมิน');
        }
        return $this->assertCanEditVersion($version);
    }

    protected function assertCanEditItem(EvaluationItem $item)
    {
        $section = $item->section;
        if (!$section) {
            throw new NotFoundHttpException('ไม่พบหมวดแบบประเมิน');
        }
        return $this->assertCanEditSection($section);
    }

    protected function assertCanEditCompetency(CompetencyDefinition $comp)
    {
        $version = $comp->templateVersion;
        if (!$version) {
            throw new NotFoundHttpException('ไม่พบเวอร์ชันแบบประเมิน');
        }
        return $this->assertCanEditVersion($version);
    }

    /**
     * List templates scoped strictly to department (or central master templates for superadmin)
     */
    public function actionIndex($department_id = null)
    {
        $adminCtx = $this->getAdminContext();
        $isSuperAdmin = $adminCtx['isSuperAdmin'];
        $myDeptId = $adminCtx['departmentId'];

        if ($isSuperAdmin) {
            $targetDeptId = ($department_id !== null && $department_id !== '' && $department_id !== '0' && $department_id !== 'central')
                ? (int)$department_id
                : null; // null = central master templates
            $targetDepartment = $targetDeptId ? Department::findOne($targetDeptId) : null;
            $departments = Department::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
        } else {
            // Agency Admin: STRICTLY THEIR OWN DEPARTMENT ONLY!
            $targetDeptId = (int)$myDeptId;
            $targetDepartment = $targetDeptId ? Department::findOne($targetDeptId) : null;
            $departments = []; // No cross-department switching for agency admin!
        }

        $activeCycle = EvaluationCycle::find()
            ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])
            ->orderBy(['id' => SORT_DESC])
            ->one() ?: EvaluationCycle::find()->orderBy(['id' => SORT_DESC])->one();

        $personnelTypes = PersonnelType::find()->orderBy(['id' => SORT_ASC])->all();

        // Build 4 Personnel Types Matrix
        $templateMatrix = [];
        foreach ($personnelTypes as $pt) {
            $customTemplate = null;
            if ($targetDepartment) {
                $customTemplate = EvaluationTemplate::find()
                    ->where(['department_id' => $targetDepartment->id, 'personnel_type_id' => $pt->id, 'status' => 1])
                    ->with(['activeVersion.sections.items', 'activeVersion.competencyDefinitions'])
                    ->one();
            }

            $centralTemplate = EvaluationTemplate::find()
                ->where(['personnel_type_id' => $pt->id])
                ->andWhere(['or', ['department_id' => null], ['is_default' => 1]])
                ->with(['activeVersion.sections.items', 'activeVersion.competencyDefinitions'])
                ->orderBy(['id' => SORT_ASC])
                ->one();

            $activeTemplate = $customTemplate ?: $centralTemplate;
            $version = $activeTemplate ? ($activeTemplate->activeVersion ?: ($activeTemplate->versions ? $activeTemplate->versions[0] : null)) : null;

            $itemCount = 0;
            $weightStr = [];
            if ($version) {
                foreach ($version->sections as $s) {
                    $itemCount += count($s->items);
                    $weightStr[] = number_format($s->weight, 0) . '%';
                }
            }
            $compCount = $version ? count($version->competencyDefinitions) : 0;

            $templateMatrix[$pt->id] = [
                'personnelType' => $pt,
                'customTemplate' => $customTemplate,
                'centralTemplate' => $centralTemplate,
                'activeTemplate' => $activeTemplate,
                'isCustom' => ($customTemplate !== null),
                'version' => $version,
                'itemCount' => $itemCount,
                'compCount' => $compCount,
                'weightStr' => $weightStr,
            ];
        }

        return $this->render('index', [
            'adminCtx' => $adminCtx,
            'isSuperAdmin' => $isSuperAdmin,
            'targetDepartment' => $targetDepartment,
            'targetDeptId' => $targetDeptId,
            'departments' => $departments,
            'activeCycle' => $activeCycle,
            'personnelTypes' => $personnelTypes,
            'templateMatrix' => $templateMatrix,
        ]);
    }

    /**
     * Agency Admin customizes criteria for their department by taking the central master template.
     */
    public function actionCustomize($personnel_type_id, $department_id = null)
    {
        $adminCtx = $this->getAdminContext();
        $isSuperAdmin = $adminCtx['isSuperAdmin'];
        $myDeptId = $adminCtx['departmentId'];

        $targetDeptId = ($isSuperAdmin && $department_id) ? (int)$department_id : (int)$myDeptId;

        if (!$targetDeptId) {
            Yii::$app->session->setFlash('danger', 'ไม่พบข้อมูลหน่วยงานของคุณ');
            return $this->redirect(['index']);
        }

        if (!$isSuperAdmin) {
            $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
            if (!in_array($targetDeptId, $scopedDeptIds, true)) {
                throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ปรับแต่งแบบประเมินของหน่วยงานอื่น');
            }
        }

        $targetDept = Department::findOne($targetDeptId);
        $pt = PersonnelType::findOne($personnel_type_id);
        if (!$targetDept || !$pt) {
            throw new NotFoundHttpException('ไม่พบหน่วยงานหรือประเภทบุคลากรที่ระบุ');
        }

        // If already customized and active, go straight to builder
        $existing = EvaluationTemplate::find()
            ->where(['department_id' => $targetDeptId, 'personnel_type_id' => $pt->id, 'status' => 1])
            ->one();

        if ($existing) {
            return $this->redirect(['builder', 'id' => $existing->id]);
        }

        // Find central master template
        $centralTemplate = EvaluationTemplate::find()
            ->where(['personnel_type_id' => $pt->id])
            ->andWhere(['or', ['department_id' => null], ['is_default' => 1]])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if (!$centralTemplate) {
            Yii::$app->session->setFlash('danger', 'ไม่พบแบบฟอร์มมาตรฐานกลางของมหาวิทยาลัย');
            return $this->redirect(['index']);
        }

        $centralVersion = $centralTemplate->activeVersion ?: ($centralTemplate->versions ? $centralTemplate->versions[0] : null);
        if (!$centralVersion) {
            Yii::$app->session->setFlash('danger', 'แบบฟอร์มมาตรฐานกลางยังไม่มีเวอร์ชัน');
            return $this->redirect(['index']);
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            // 1. Create Department Template
            $newTemplate = new EvaluationTemplate();
            $newTemplate->personnel_type_id = $pt->id;
            $newTemplate->department_id = $targetDeptId;
            $deptCode = strtoupper($targetDept->code ?: 'DEPT');
            $newTemplate->code = "TPL_{$deptCode}_{$pt->code}_" . date('Y');
            $newTemplate->name_th = "แบบประเมินผลการปฏิบัติงาน {$pt->name_th} ({$targetDept->name_th})";
            $newTemplate->description = "แบบประเมินเฉพาะของ {$targetDept->name_th} ปรับปรุงจากแบบฟอร์มมาตรฐานกลาง มหาวิทยาลัย";
            $newTemplate->status = 1;
            $newTemplate->is_default = 0;
            $newTemplate->save(false);

            // 2. Clone Version
            $newVersion = new TemplateVersion();
            $newVersion->evaluation_template_id = $newTemplate->id;
            $newVersion->version_number = 1;
            $newVersion->version_label = 'v1.0';
            $newVersion->is_active = 1;
            $newVersion->effective_from = date('Y-m-d');
            $newVersion->total_weight = $centralVersion->total_weight;
            $newVersion->score_formula_config = $centralVersion->score_formula_config;
            $newVersion->created_by = Yii::$app->user->id;
            $newVersion->save(false);

            // 3. Clone Sections & Items & Criteria
            foreach ($centralVersion->sections as $sec) {
                $newSec = new EvaluationSection();
                $newSec->template_version_id = $newVersion->id;
                $newSec->section_code = $sec->section_code;
                $newSec->name_th = $sec->name_th;
                $newSec->description = $sec->description;
                $newSec->weight = $sec->weight;
                $newSec->sort_order = $sec->sort_order;
                $newSec->section_type = $sec->section_type;
                $newSec->is_evaluator_fill = $sec->is_evaluator_fill;
                $newSec->save(false);

                foreach ($sec->items as $item) {
                    $newItem = new EvaluationItem();
                    $newItem->evaluation_section_id = $newSec->id;
                    $newItem->item_code = $item->item_code;
                    $newItem->name_th = $item->name_th;
                    $newItem->description = $item->description;
                    $newItem->input_type = $item->input_type;
                    $newItem->max_score = $item->max_score;
                    $newItem->max_weight = $item->max_weight;
                    $newItem->default_weight = $item->default_weight;
                    $newItem->expected_level = $item->expected_level;
                    $newItem->sort_order = $item->sort_order;
                    $newItem->is_required = $item->is_required;
                    $newItem->requires_evidence = $item->requires_evidence;
                    $newItem->evidence_instruction = $item->evidence_instruction;
                    $newItem->options_data = $item->options_data;
                    $newItem->save(false);

                    foreach ($item->criteria as $crit) {
                        $newCrit = new EvaluationCriteria();
                        $newCrit->evaluation_item_id = $newItem->id;
                        $newCrit->level_value = $crit->level_value;
                        $newCrit->level_label = $crit->level_label;
                        $newCrit->score_value = $crit->score_value;
                        $newCrit->description = $crit->description;
                        $newCrit->condition_rule = $crit->condition_rule;
                        $newCrit->sort_order = $crit->sort_order;
                        $newCrit->save(false);
                    }
                }
            }

            // 4. Clone Competencies
            foreach ($centralVersion->competencyDefinitions as $comp) {
                $newComp = new CompetencyDefinition();
                $newComp->template_version_id = $newVersion->id;
                $newComp->competency_code = $comp->competency_code;
                $newComp->competency_type = $comp->competency_type;
                $newComp->name_th = $comp->name_th;
                $newComp->description = $comp->description;
                $newComp->weight = $comp->weight;
                $newComp->target_score = $comp->target_score;
                $newComp->sort_order = $comp->sort_order;
                $newComp->save(false);

                foreach ($comp->levels as $lvl) {
                    $newLvl = new CompetencyLevel();
                    $newLvl->competency_id = $newComp->id;
                    $newLvl->level_number = $lvl->level_number;
                    $newLvl->behavioral_indicator = $lvl->behavioral_indicator;
                    $newLvl->score_value = $lvl->score_value;
                    $newLvl->save(false);
                }
            }

            // 5. Map in CycleTemplateMapping for active cycle
            $activeCycle = EvaluationCycle::find()
                ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION, EvaluationCycle::STATUS_DRAFT]])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($activeCycle) {
                $mapping = CycleTemplateMapping::findOne([
                    'evaluation_cycle_id' => $activeCycle->id,
                    'department_id' => $targetDeptId,
                    'personnel_type_id' => $pt->id,
                ]);
                if (!$mapping) {
                    $mapping = new CycleTemplateMapping();
                    $mapping->evaluation_cycle_id = $activeCycle->id;
                    $mapping->department_id = $targetDeptId;
                    $mapping->personnel_type_id = $pt->id;
                    $mapping->created_at = time();
                }
                $mapping->template_version_id = $newVersion->id;
                $mapping->save(false);

                // Update unfinalized draft evaluations
                $scopedTargetDeptIds = Department::getAllScopedDeptIds($targetDeptId);
                $evalsToUpdate = Evaluation::find()
                    ->innerJoinWith('personnel')
                    ->where([
                        '{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id,
                        '{{%personnel}}.personnel_type_id' => $pt->id,
                        '{{%evaluations}}.status' => [Evaluation::STATUS_SELF_ASSESSMENT, Evaluation::STATUS_DRAFT],
                    ])
                    ->andWhere(['in', '{{%personnel}}.department_id', $scopedTargetDeptIds])
                    ->all();

                foreach ($evalsToUpdate as $ev) {
                    $ev->template_version_id = $newVersion->id;
                    $ev->save(false);
                }
            }

            AuditLog::log('customize_template_for_dept', 'EvaluationTemplate', $newTemplate->id, null, [
                'department_id' => $targetDeptId,
                'personnel_type_id' => $pt->id,
            ]);

            $tx->commit();

            Yii::$app->session->setFlash('success', "นำแบบประเมินส่วนกลางมาสร้างเป็นแบบเฉพาะของ {$targetDept->name_th} เรียบร้อยแล้ว ท่านสามารถปรับแต่งตัวชี้วัด (KPI) ค่าน้ำหนัก และเกณฑ์คะแนนตามภาระงานจริงของหน่วยงานได้ทันที");
            return $this->redirect(['builder', 'id' => $newTemplate->id]);
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('danger', 'เกิดข้อผิดพลาดในการสร้างแบบประเมินเฉพาะหน่วยงาน: ' . $e->getMessage());
            return $this->redirect(['index']);
        }
    }

    /**
     * Revert department evaluation template back to Central Master
     */
    public function actionResetToCentral($personnel_type_id, $department_id = null)
    {
        $adminCtx = $this->getAdminContext();
        $isSuperAdmin = $adminCtx['isSuperAdmin'];
        $myDeptId = $adminCtx['departmentId'];

        $targetDeptId = ($isSuperAdmin && $department_id) ? (int)$department_id : (int)$myDeptId;

        if (!$targetDeptId) {
            Yii::$app->session->setFlash('danger', 'ไม่พบข้อมูลหน่วยงานของคุณ');
            return $this->redirect(['index']);
        }

        if (!$isSuperAdmin) {
            $scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];
            if (!in_array($targetDeptId, $scopedDeptIds, true)) {
                throw new ForbiddenHttpException('คุณไม่มีสิทธิ์จัดการแบบประเมินของหน่วยงานอื่น');
            }
        }

        $targetDept = Department::findOne($targetDeptId);
        $pt = PersonnelType::findOne($personnel_type_id);

        $tx = Yii::$app->db->beginTransaction();
        try {
            // Deactivate custom templates
            EvaluationTemplate::updateAll(
                ['status' => 0],
                ['department_id' => $targetDeptId, 'personnel_type_id' => $personnel_type_id]
            );

            // Delete CycleTemplateMapping for this department & personnel type
            $activeCycle = EvaluationCycle::find()
                ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION, EvaluationCycle::STATUS_DRAFT]])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($activeCycle) {
                CycleTemplateMapping::deleteAll([
                    'evaluation_cycle_id' => $activeCycle->id,
                    'department_id' => $targetDeptId,
                    'personnel_type_id' => $personnel_type_id,
                ]);

                // Find Central Version
                $centralVersion = null;
                $masterMapping = CycleTemplateMapping::find()
                    ->where(['evaluation_cycle_id' => $activeCycle->id, 'personnel_type_id' => $personnel_type_id])
                    ->andWhere(['or', ['department_id' => null], ['department_id' => 0]])
                    ->one();

                if ($masterMapping) {
                    $centralVersion = $masterMapping->templateVersion;
                } else {
                    $centralTpl = EvaluationTemplate::find()
                        ->where(['personnel_type_id' => $personnel_type_id])
                        ->andWhere(['or', ['department_id' => null], ['is_default' => 1]])
                        ->one();
                    if ($centralTpl) {
                        $centralVersion = $centralTpl->activeVersion ?: ($centralTpl->versions ? $centralTpl->versions[0] : null);
                    }
                }

                // Update unfinalized draft evaluations to Central Version
                if ($centralVersion) {
                    $scopedTargetDeptIds = Department::getAllScopedDeptIds($targetDeptId);
                    $evalsToUpdate = Evaluation::find()
                        ->innerJoinWith('personnel')
                        ->where([
                            '{{%evaluations}}.evaluation_cycle_id' => $activeCycle->id,
                            '{{%personnel}}.personnel_type_id' => $personnel_type_id,
                            '{{%evaluations}}.status' => [Evaluation::STATUS_SELF_ASSESSMENT, Evaluation::STATUS_DRAFT],
                        ])
                        ->andWhere(['in', '{{%personnel}}.department_id', $scopedTargetDeptIds])
                        ->all();

                    foreach ($evalsToUpdate as $ev) {
                        $ev->template_version_id = $centralVersion->id;
                        $ev->save(false);
                    }
                }
            }

            AuditLog::log('reset_template_to_central', 'EvaluationTemplate', null, null, [
                'department_id' => $targetDeptId,
                'personnel_type_id' => $personnel_type_id,
            ]);

            $tx->commit();

            Yii::$app->session->setFlash('info', "คืนค่าแบบประเมินกลุ่ม '{$pt->name_th}' กลับไปใช้แบบฟอร์มมาตรฐานกลางของมหาวิทยาลัยเรียบร้อยแล้ว");
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('danger', 'เกิดข้อผิดพลาดในการคืนค่า: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Create a new template for a Department
     */
    public function actionCreate()
    {
        $adminCtx = $this->getAdminContext();
        $model = new EvaluationTemplate();
        $model->status = 1;

        if (!$adminCtx['isSuperAdmin'] && $adminCtx['departmentId']) {
            $model->department_id = $adminCtx['departmentId'];
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if (!$adminCtx['isSuperAdmin'] && $adminCtx['departmentId']) {
                $model->department_id = $adminCtx['departmentId'];
            } elseif ($model->department_id === 0 || $model->department_id === '') {
                $model->department_id = null;
            }

            // Generate unique code if empty
            if (empty($model->code)) {
                $deptCode = $model->department ? $model->department->code : 'MASTER';
                $typeCode = $model->personnelType ? $model->personnelType->code : 'ALL';
                $model->code = strtoupper("TPL_{$deptCode}_{$typeCode}_" . date('YmdHis'));
            }

            if ($model->save()) {
                // Create initial TemplateVersion (v1.0)
                $version = new TemplateVersion();
                $version->evaluation_template_id = $model->id;
                $version->version_number = 1;
                $version->version_label = 'v1.0';
                $version->is_active = 1;
                $version->effective_from = date('Y-m-d');
                $version->total_weight = 100.00;
                $version->created_by = Yii::$app->user->id;
                $version->save(false);

                // Initialize 2 standard default sections (70% / 30%)
                $sec1 = new EvaluationSection();
                $sec1->template_version_id = $version->id;
                $sec1->section_code = 'MAIN_WORK';
                $sec1->name_th = 'ส่วนที่ ๑ : ผลสัมฤทธิ์ของงานประจำหน่วยงาน';
                $sec1->description = 'การประเมินผลการปฏิบัติงานตามหน้าที่ความรับผิดชอบและตัวชี้วัดของหน่วยงาน';
                $sec1->weight = 70.00;
                $sec1->sort_order = 1;
                $sec1->section_type = 'main_work';
                $sec1->save(false);

                $sec2 = new EvaluationSection();
                $sec2->template_version_id = $version->id;
                $sec2->section_code = 'COMPETENCY';
                $sec2->name_th = 'ส่วนที่ ๒ : ขีดความสามารถและสมรรถนะที่หน่วยงานต้องการ';
                $sec2->description = 'การประเมินสมรรถนะหลักและสมรรถนะประจำสายงาน';
                $sec2->weight = 30.00;
                $sec2->sort_order = 2;
                $sec2->section_type = 'competency';
                $sec2->save(false);

                AuditLog::log('create_evaluation_template', 'EvaluationTemplate', $model->id, null, ['name' => $model->name_th]);

                Yii::$app->session->setFlash('success', "สร้างแบบประเมิน '{$model->name_th}' เรียบร้อยแล้ว ท่านสามารถปรับแต่งตัวชี้วัดและสมรรถนะได้ทันที");
                return $this->redirect(['builder', 'id' => $model->id]);
            }
        }

        $departments = Department::find()->where(['status' => 10])->all();
        $personnelTypes = PersonnelType::find()->all();

        return $this->render('create', [
            'model' => $model,
            'departments' => $departments,
            'personnelTypes' => $personnelTypes,
        ]);
    }

    /**
     * 1-Click Clone Template for another Department
     */
    public function actionClone($id)
    {
        $sourceTemplate = $this->findModel($id);
        $targetDepartmentId = Yii::$app->request->post('target_department_id');
        $newName = Yii::$app->request->post('new_name');

        if (!$targetDepartmentId) {
            Yii::$app->session->setFlash('danger', 'กรุณาเลือกหน่วยงานเป้าหมายที่ต้องการคัดลอกไปใช้');
            return $this->redirect(['index']);
        }

        $adminCtx = $this->getAdminContext();
        if (!$adminCtx['isSuperAdmin']) {
            $scopedDeptIds = $adminCtx['departmentId'] ? Department::getAllScopedDeptIds($adminCtx['departmentId']) : [];
            if (!in_array((int)$targetDepartmentId, $scopedDeptIds, true)) {
                throw new ForbiddenHttpException('คุณสามารถคัดลอกแบบประเมินไปยังหน่วยงานที่คุณรับผิดชอบเท่านั้น');
            }
        }

        $targetDept = Department::findOne($targetDepartmentId);
        if (!$targetDept) {
            throw new NotFoundHttpException('ไม่พบหน่วยงานที่ระบุ');
        }

        $sourceVersion = $sourceTemplate->activeVersion ?: ($sourceTemplate->versions ? $sourceTemplate->versions[0] : null);
        if (!$sourceVersion) {
            Yii::$app->session->setFlash('danger', 'แบบประเมินต้นทางไม่มีโครงสร้างเวอร์ชัน');
            return $this->redirect(['index']);
        }

        // 1. Create New EvaluationTemplate
        $newTemplate = new EvaluationTemplate();
        $newTemplate->personnel_type_id = $sourceTemplate->personnel_type_id;
        $newTemplate->department_id = $targetDept->id;
        $newTemplate->code = strtoupper("TPL_{$targetDept->code}_{$sourceTemplate->personnelType->code}_" . date('YmdHis'));
        $newTemplate->name_th = $newName ?: "{$sourceTemplate->name_th} ({$targetDept->name_th})";
        $newTemplate->description = "คัดลอกจากต้นแบบ: {$sourceTemplate->name_th} เพื่อใช้สำหรับ {$targetDept->name_th}";
        $newTemplate->status = 1;
        $newTemplate->is_default = 0;
        $newTemplate->save(false);

        // 2. Clone Version
        $newVersion = new TemplateVersion();
        $newVersion->evaluation_template_id = $newTemplate->id;
        $newVersion->version_number = 1;
        $newVersion->version_label = 'v1.0';
        $newVersion->is_active = 1;
        $newVersion->effective_from = date('Y-m-d');
        $newVersion->total_weight = $sourceVersion->total_weight;
        $newVersion->score_formula_config = $sourceVersion->score_formula_config;
        $newVersion->created_by = Yii::$app->user->id;
        $newVersion->save(false);

        // 3. Clone Sections, Items, and Criteria
        foreach ($sourceVersion->sections as $sec) {
            $newSec = new EvaluationSection();
            $newSec->template_version_id = $newVersion->id;
            $newSec->section_code = $sec->section_code;
            $newSec->name_th = $sec->name_th;
            $newSec->description = $sec->description;
            $newSec->weight = $sec->weight;
            $newSec->sort_order = $sec->sort_order;
            $newSec->section_type = $sec->section_type;
            $newSec->is_evaluator_fill = $sec->is_evaluator_fill;
            $newSec->save(false);

            foreach ($sec->items as $item) {
                $newItem = new EvaluationItem();
                $newItem->evaluation_section_id = $newSec->id;
                $newItem->item_code = $item->item_code;
                $newItem->name_th = $item->name_th;
                $newItem->description = $item->description;
                $newItem->input_type = $item->input_type;
                $newItem->max_score = $item->max_score;
                $newItem->max_weight = $item->max_weight;
                $newItem->default_weight = $item->default_weight;
                $newItem->expected_level = $item->expected_level;
                $newItem->sort_order = $item->sort_order;
                $newItem->is_required = $item->is_required;
                $newItem->requires_evidence = $item->requires_evidence;
                $newItem->evidence_instruction = $item->evidence_instruction;
                $newItem->options_data = $item->options_data;
                $newItem->save(false);

                foreach ($item->criteria as $crit) {
                    $newCrit = new EvaluationCriteria();
                    $newCrit->evaluation_item_id = $newItem->id;
                    $newCrit->level_value = $crit->level_value;
                    $newCrit->level_label = $crit->level_label;
                    $newCrit->score_value = $crit->score_value;
                    $newCrit->description = $crit->description;
                    $newCrit->condition_rule = $crit->condition_rule;
                    $newCrit->sort_order = $crit->sort_order;
                    $newCrit->save(false);
                }
            }
        }

        // 4. Clone Competencies
        foreach ($sourceVersion->competencyDefinitions as $comp) {
            $newComp = new CompetencyDefinition();
            $newComp->template_version_id = $newVersion->id;
            $newComp->competency_code = $comp->competency_code;
            $newComp->competency_type = $comp->competency_type;
            $newComp->name_th = $comp->name_th;
            $newComp->name_en = $comp->name_en;
            $newComp->definition = $comp->definition;
            $newComp->expected_level = $comp->expected_level;
            $newComp->sort_order = $comp->sort_order;
            $newComp->save(false);

            foreach ($comp->levels as $lvl) {
                $newLvl = new CompetencyLevel();
                $newLvl->competency_definition_id = $newComp->id;
                $newLvl->level_value = $lvl->level_value;
                $newLvl->level_label = $lvl->level_label;
                $newLvl->behavior_description = $lvl->behavior_description;
                $newLvl->save(false);
            }
        }

        AuditLog::log('clone_evaluation_template', 'EvaluationTemplate', $newTemplate->id, null, [
            'source_id' => $sourceTemplate->id,
            'target_department' => $targetDept->name_th
        ]);

        if (Yii::$app->request->post('assign_now')) {
            $activeCycle = EvaluationCycle::find()
                ->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION, EvaluationCycle::STATUS_DRAFT]])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            if ($activeCycle) {
                $mapping = CycleTemplateMapping::findOne([
                    'evaluation_cycle_id' => $activeCycle->id,
                    'department_id' => $targetDept->id,
                    'personnel_type_id' => $newTemplate->personnel_type_id,
                ]);
                if (!$mapping) {
                    $mapping = new CycleTemplateMapping();
                    $mapping->evaluation_cycle_id = $activeCycle->id;
                    $mapping->department_id = $targetDept->id;
                    $mapping->personnel_type_id = $newTemplate->personnel_type_id;
                    $mapping->created_at = time();
                }
                $mapping->template_version_id = $newVersion->id;
                $mapping->save(false);

                // Update non-finalized evaluations in active cycle
                $scopedTargetDeptIds = Department::getAllScopedDeptIds((int)$targetDept->id);
                Evaluation::updateAll(
                    ['template_version_id' => $newVersion->id],
                    [
                        'and',
                        ['evaluation_cycle_id' => $activeCycle->id],
                        ['status' => [Evaluation::STATUS_SELF_ASSESSMENT, Evaluation::STATUS_DRAFT]],
                        ['in', 'personnel_id', Personnel::find()->select('id')->where(['in', 'department_id', $scopedTargetDeptIds, 'personnel_type_id' => $newTemplate->personnel_type_id])],
                    ]
                );
            }
            Yii::$app->session->setFlash('success', "คัดลอกแบบประเมินและตั้งเป็นแบบประเมินที่ใช้งานจริงสำหรับ '{$targetDept->name_th}' เรียบร้อยแล้ว! สามารถปรับแต่งตัวชี้วัดด้านล่างได้ทันที");
        } else {
            Yii::$app->session->setFlash('success', "คัดลอกแบบประเมินสำหรับ '{$targetDept->name_th}' เรียบร้อยแล้ว! สามารถปรับแต่งตัวชี้วัดได้ทันที");
        }
        return $this->redirect(['builder', 'id' => $newTemplate->id]);
    }

    /**
     * The Main Visual Form & KPI Builder UI
     */
    public function actionBuilder($id)
    {
        $template = $this->findModel($id);
        $this->assertCanEditTemplate($template);
        $version = $template->activeVersion ?: ($template->versions ? $template->versions[0] : null);

        if (!$version) {
            $version = new TemplateVersion([
                'evaluation_template_id' => $template->id,
                'version_number' => 1,
                'version_label' => 'v1.0',
                'is_active' => 1,
                'effective_from' => date('Y-m-d'),
                'total_weight' => 100.00,
                'created_by' => Yii::$app->user->id,
            ]);
            $version->save(false);
        }

        // Calculate total weight of sections
        $sections = EvaluationSection::find()
            ->where(['template_version_id' => $version->id])
            ->with(['items.criteria'])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $competencies = CompetencyDefinition::find()
            ->where(['template_version_id' => $version->id])
            ->with(['levels'])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $totalSectionWeight = 0;
        foreach ($sections as $s) {
            $totalSectionWeight += floatval($s->weight);
        }

        $departments = Department::find()->where(['status' => 10])->all();

        return $this->render('builder', [
            'template' => $template,
            'version' => $version,
            'sections' => $sections,
            'competencies' => $competencies,
            'totalSectionWeight' => $totalSectionWeight,
            'departments' => $departments,
        ]);
    }

    /**
     * AJAX: Save Section
     */
    public function actionSaveSection($version_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $version = TemplateVersion::findOne($version_id);
        if (!$version) {
            return ['success' => false, 'message' => 'Version not found'];
        }
        $this->assertCanEditVersion($version);

        $req = Yii::$app->request;
        $sectionId = $req->post('id');
        $section = $sectionId ? EvaluationSection::findOne($sectionId) : new EvaluationSection();
        if ($sectionId && $section) {
            $this->assertCanEditSection($section);
        }
        
        $section->template_version_id = $version->id;
        $section->name_th = $req->post('name_th', 'หมวดใหม่');
        $section->weight = floatval($req->post('weight', 0));
        $section->description = $req->post('description', '');
        $section->section_type = $req->post('section_type', 'main_work');
        $section->sort_order = intval($req->post('sort_order', 1));
        
        if ($section->save()) {
            return [
                'success' => true,
                'section' => [
                    'id' => $section->id,
                    'name_th' => $section->name_th,
                    'weight' => $section->weight,
                    'section_type' => $section->section_type,
                    'description' => $section->description,
                ],
            ];
        }

        return ['success' => false, 'message' => 'Failed to save section', 'errors' => $section->errors];
    }

    /**
     * AJAX: Delete Section
     */
    public function actionDeleteSection($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $section = EvaluationSection::findOne($id);
        if (!$section) {
            return ['success' => false, 'message' => 'Section not found'];
        }
        $this->assertCanEditSection($section);

        if ($section->delete()) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Cannot delete section'];
    }

    /**
     * AJAX: Save Item (KPI) & Criteria
     */
    public function actionSaveItem($section_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $section = EvaluationSection::findOne($section_id);
        if (!$section) {
            return ['success' => false, 'message' => 'Section not found'];
        }
        $this->assertCanEditSection($section);

        $req = Yii::$app->request;
        $itemId = $req->post('id');
        $item = $itemId ? EvaluationItem::findOne($itemId) : new EvaluationItem();
        if ($itemId && $item) {
            $this->assertCanEditItem($item);
        }

        $item->evaluation_section_id = $section->id;
        $item->name_th = $req->post('name_th', 'ตัวชี้วัดใหม่');
        $item->description = $req->post('description', '');
        $item->input_type = $req->post('input_type', 'pdca_level');
        $item->max_weight = floatval($req->post('max_weight', 0));
        $item->default_weight = floatval($req->post('default_weight', 0));
        $item->max_score = floatval($req->post('max_score', 5.0));
        $item->requires_evidence = intval($req->post('requires_evidence', 0));
        $item->sort_order = intval($req->post('sort_order', 1));

        if ($item->save()) {
            // Save 5 Criteria if pdca_level
            $criteriaDesc = $req->post('criteria', []);
            if (is_array($criteriaDesc) && !empty($criteriaDesc)) {
                $pdcaLabels = [
                    1 => 'ระดับ 1 (Plan)',
                    2 => 'ระดับ 2 (Do)',
                    3 => 'ระดับ 3 (Check)',
                    4 => 'ระดับ 4 (Act)',
                    5 => 'ระดับ 5 (Impact)',
                ];

                for ($lvl = 1; $lvl <= 5; $lvl++) {
                    $crit = EvaluationCriteria::findOne(['evaluation_item_id' => $item->id, 'level_value' => $lvl])
                        ?: new EvaluationCriteria(['evaluation_item_id' => $item->id, 'level_value' => $lvl]);

                    $crit->level_label = $pdcaLabels[$lvl];
                    $crit->score_value = $lvl;
                    $crit->description = $criteriaDesc[$lvl] ?? ($crit->description ?: "เกณฑ์ความสำเร็จระดับ {$lvl}");
                    $crit->sort_order = $lvl;
                    $crit->save(false);
                }
            }

            return ['success' => true, 'item_id' => $item->id];
        }

        return ['success' => false, 'message' => 'Failed to save item', 'errors' => $item->errors];
    }

    /**
     * AJAX: Delete Item (KPI)
     */
    public function actionDeleteItem($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $item = EvaluationItem::findOne($id);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }
        $this->assertCanEditItem($item);

        if ($item->delete()) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Cannot delete item'];
    }

    /**
     * AJAX: Save Competency
     */
    public function actionSaveCompetency($version_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $version = TemplateVersion::findOne($version_id);
        if (!$version) {
            return ['success' => false, 'message' => 'Version not found'];
        }
        $this->assertCanEditVersion($version);

        $req = Yii::$app->request;
        $compId = $req->post('id');
        $comp = $compId ? CompetencyDefinition::findOne($compId) : new CompetencyDefinition();
        if ($compId && $comp) {
            $this->assertCanEditCompetency($comp);
        }

        $comp->template_version_id = $version->id;
        $comp->name_th = $req->post('name_th', 'สมรรถนะใหม่');
        $comp->competency_type = $req->post('competency_type', 'core');
        $comp->definition = $req->post('definition', 'คำนิยามสมรรถนะ');
        $comp->expected_level = intval($req->post('expected_level', 3));
        $comp->sort_order = intval($req->post('sort_order', 1));

        if ($comp->save()) {
            return ['success' => true, 'competency_id' => $comp->id];
        }

        return ['success' => false, 'message' => 'Failed to save competency', 'errors' => $comp->errors];
    }

    /**
     * AJAX: Delete Competency
     */
    public function actionDeleteCompetency($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $comp = CompetencyDefinition::findOne($id);
        if (!$comp) {
            return ['success' => false, 'message' => 'Competency not found'];
        }
        $this->assertCanEditCompetency($comp);

        if ($comp->delete()) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Cannot delete competency'];
    }

    /**
     * Live Interactive Preview of Template
     */
    public function actionPreview($id)
    {
        $template = $this->findModel($id);
        $version = $template->activeVersion ?: ($template->versions ? $template->versions[0] : null);

        if (!$version) {
            throw new NotFoundHttpException('ไม่พบเวอร์ชันแบบประเมิน');
        }

        $sections = $version->sections;
        $competencies = $version->competencyDefinitions;

        return $this->render('preview', [
            'template' => $template,
            'version' => $version,
            'sections' => $sections,
            'competencies' => $competencies,
        ]);
    }

    /**
     * Delete Template
     */
    public function actionDelete($id)
    {
        $template = $this->findModel($id);
        $this->assertCanEditTemplate($template);
        $name = $template->name_th;
        $template->delete();

        Yii::$app->session->setFlash('info', "ลบแบบประเมิน '{$name}' เรียบร้อยแล้ว");
        return $this->redirect(['index']);
    }

    /**
     * Standard RMUTT Competency Pool API
     */
    public function actionCompetencyPool()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $pool = [
            'core' => [
                [
                    'name_th' => 'การมุ่งผลสัมฤทธิ์ (Achievement Motivation)',
                    'definition' => 'ความมุ่งมั่นที่จะปฏิบัติหน้าที่ให้ดีหรือให้เกินมาตรฐานที่มีอยู่ โดยมาตรฐานนี้อาจเป็นผลการปฏิบัติงานที่ผ่านมาของตนเอง หรือเกณฑ์วัดผลสัมฤทธิ์ที่มหาวิทยาลัยกำหนด',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'การบริการที่ดี (Service Mind)',
                    'definition' => 'ความตั้งใจและความมุ่งมั่นในการให้บริการแก่ผู้รับบริการ เพื่อให้เกิดความพึงพอใจและสร้างความประทับใจ',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'การสั่งสมความเชี่ยวชาญในงานอาชีพ (Expertise)',
                    'definition' => 'ความสนใจใฝ่รู้ สั่งสม พัฒนาความรู้ความสามารถของตนเองในการปฏิบัติงานอย่างต่อเนื่อง',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'การทำงานเป็นทีม (Teamwork)',
                    'definition' => 'ความตั้งใจที่จะทำงานร่วมกับผู้อื่น เป็นส่วนหนึ่งของทีมและสนับสนุนเพื่อนร่วมงาน',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'จริยธรรมและคุณธรรม (Integrity)',
                    'definition' => 'การประพฤติปฏิบัติตนอย่างถูกต้องตามหลักศีลธรรม จรรยาบรรณวิชาชีพ และระเบียบวินัย',
                    'default_level' => 3,
                ],
            ],
            'functional' => [
                [
                    'name_th' => 'การคิดวิเคราะห์และแก้ปัญหา (Analytical Thinking)',
                    'definition' => 'ความสามารถในการทำความเข้าใจสถานการณ์ แยกแยะปัญหา และค้นหาสาเหตุเพื่อแก้ไขได้อย่างมีประสิทธิภาพ',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'การสื่อสารและการประสานงาน (Communication & Coordination)',
                    'definition' => 'ความสามารถในการถ่ายทอดข้อมูล แลกเปลี่ยนความคิดเห็น และประสานงานกับหน่วยงานที่เกี่ยวข้อง',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'ความคิดริเริ่มสร้างสรรค์และพัฒนานวัตกรรม (Innovation & Creativity)',
                    'definition' => 'ความสามารถในการคิดค้นแนวทางใหม่ ๆ ปรับปรุงวิธีการทำงาน และนำเทคโนโลยีมาประยุกต์ใช้',
                    'default_level' => 3,
                ],
                [
                    'name_th' => 'การบริหารจัดการข้อมูลและเทคโนโลยีดิจิทัล (Digital & Information Literacy)',
                    'definition' => 'ความสามารถในการใช้เครื่องมือดิจิทัลและการจัดการข้อมูลสารสนเทศเพื่อสนับสนุนการตัดสินใจ',
                    'default_level' => 3,
                ],
            ],
        ];

        return ['success' => true, 'pool' => $pool];
    }

    /**
     * AJAX: Save Entire Form State in One Transaction (Excel-Grid Style)
     */
    public function actionSaveAll($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $template = $this->findModel($id);
        $this->assertCanEditTemplate($template);

        $version = $template->activeVersion ?: ($template->versions ? $template->versions[0] : null);
        if (!$version) {
            return ['success' => false, 'message' => 'Version not found'];
        }
        $this->assertCanEditVersion($version);

        $req = Yii::$app->request;
        $raw = $req->getRawBody();
        $payload = !empty($raw) ? json_decode($raw, true) : $req->post();

        if (empty($payload) || !is_array($payload)) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลที่ส่งมา'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 1. Update Template Details if sent
            if (!empty($payload['template_name'])) {
                $template->name_th = $payload['template_name'];
                $template->save(false);
            }

            // 2. Process Sections & Items
            $sectionsData = $payload['sections'] ?? [];
            $existingSecIds = [];

            foreach ($sectionsData as $sIdx => $sData) {
                $sec = !empty($sData['id']) ? EvaluationSection::findOne($sData['id']) : new EvaluationSection();
                if (!$sec || $sec->template_version_id != $version->id) {
                    $sec = new EvaluationSection();
                }
                $sec->template_version_id = $version->id;
                $sec->name_th = $sData['name_th'] ?? ('หมวดที่ ' . ($sIdx + 1));
                $sec->weight = floatval($sData['weight'] ?? 0);
                $sec->section_type = $sData['section_type'] ?? 'main_work';
                $sec->sort_order = $sIdx + 1;
                $sec->save(false);
                $existingSecIds[] = $sec->id;

                if ($sec->section_type !== 'competency') {
                    // Process Items
                    $itemsData = $sData['items'] ?? [];
                    $existingItemIds = [];

                    foreach ($itemsData as $iIdx => $iData) {
                        $item = !empty($iData['id']) ? EvaluationItem::findOne($iData['id']) : new EvaluationItem();
                        if (!$item || $item->evaluation_section_id != $sec->id) {
                            $item = new EvaluationItem();
                        }
                        $item->evaluation_section_id = $sec->id;
                        $item->name_th = $iData['name_th'] ?? ('ตัวชี้วัดที่ ' . ($iIdx + 1));
                        $item->max_weight = floatval($iData['weight'] ?? 0);
                        $item->max_score = !empty($iData['max_score']) ? floatval($iData['max_score']) : (!empty($iData['weight']) ? floatval($iData['weight']) : 5.0);
                        $item->input_type = $iData['input_type'] ?? 'pdca_level';
                        $item->requires_evidence = !empty($iData['requires_evidence']) ? 1 : 0;
                        $item->sort_order = $iIdx + 1;
                        $item->save(false);
                        $existingItemIds[] = $item->id;

                        // Save Criteria 1-5
                        $criteriaData = $iData['criteria'] ?? [];
                        $pdcaLabels = [
                            1 => 'ระดับ 1 (Plan)',
                            2 => 'ระดับ 2 (Do)',
                            3 => 'ระดับ 3 (Check)',
                            4 => 'ระดับ 4 (Act)',
                            5 => 'ระดับ 5 (Impact)',
                        ];

                        for ($lvl = 1; $lvl <= 5; $lvl++) {
                            $crit = EvaluationCriteria::findOne(['evaluation_item_id' => $item->id, 'level_value' => $lvl])
                                ?: new EvaluationCriteria(['evaluation_item_id' => $item->id, 'level_value' => $lvl]);

                            $crit->level_label = $pdcaLabels[$lvl];
                            $crit->score_value = $lvl;
                            $crit->description = $criteriaData[$lvl] ?? "เกณฑ์ความสำเร็จระดับ {$lvl}";
                            $crit->sort_order = $lvl;
                            $crit->save(false);
                        }
                    }

                    // Delete removed items
                    EvaluationItem::deleteAll(['and', ['evaluation_section_id' => $sec->id], ['not in', 'id', $existingItemIds]]);
                }
            }

            // Delete removed sections
            EvaluationSection::deleteAll(['and', ['template_version_id' => $version->id], ['not in', 'id', $existingSecIds]]);

            // 3. Process Competencies
            $compsData = $payload['competencies'] ?? [];
            $existingCompIds = [];

            foreach ($compsData as $cIdx => $cData) {
                $comp = !empty($cData['id']) ? CompetencyDefinition::findOne($cData['id']) : new CompetencyDefinition();
                if (!$comp || $comp->template_version_id != $version->id) {
                    $comp = new CompetencyDefinition();
                }
                $comp->template_version_id = $version->id;
                $comp->name_th = $cData['name_th'] ?? ('สมรรถนะที่ ' . ($cIdx + 1));
                $comp->definition = $cData['definition'] ?? '';
                $comp->competency_type = $cData['competency_type'] ?? 'core';
                $comp->expected_level = intval($cData['expected_level'] ?? 3);
                $comp->sort_order = $cIdx + 1;
                $comp->save(false);
                $existingCompIds[] = $comp->id;
            }

            // Delete removed competencies
            CompetencyDefinition::deleteAll(['and', ['template_version_id' => $version->id], ['not in', 'id', $existingCompIds]]);

            $transaction->commit();
            AuditLog::log('update_evaluation_template', 'EvaluationTemplate', $template->id, null, ['action' => 'save_all_grid']);

            return ['success' => true, 'message' => 'บันทึกการเปลี่ยนแปลงทั้งหมดเรียบร้อยแล้ว'];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()];
        }
    }

    /**
     * Find Template Model
     */
    protected function findModel($id)
    {
        if (($model = EvaluationTemplate::findOne($id)) !== null) {
            $this->assertCanReadTemplate($model);
            return $model;
        }
        throw new NotFoundHttpException('ไม่พบแบบประเมินที่ระบุ');
    }
}
