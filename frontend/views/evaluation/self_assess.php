<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;

/** @var yii\web\View $this */
/** @var common\models\Evaluation $evaluation */
/** @var common\models\Personnel $personnel */
/** @var common\models\TemplateVersion $templateVersion */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */
/** @var common\models\EvaluationAnswer[] $answers */
/** @var common\models\EvaluationCompetencyAnswer[] $compAnswers */
/** @var common\models\EvidenceFile[] $evidenceFiles */
/** @var bool $isEditable */

$this->title = 'แบบประเมินตนเอง: ' . $evaluation->cycle->name_th;
$personnelType = $personnel->personnelType->code; // CIVIL, UNIVERSITY, GOVT, SPECIAL
$isCivil = ($personnelType === 'CIVIL');
$isUniv = ($personnelType === 'UNIVERSITY');
$isGovt = ($personnelType === 'GOVT');
$isSpecial = ($personnelType === 'SPECIAL');
$perfWeight = 70.0;
$compWeight = 30.0;
$perfWeightTh = '๗๐%';
$compWeightTh = '๓๐%';
$docCode = ($personnelType === 'CIVIL') ? 'แบบสรุป ปร.' : 'แบบสรุป ปม.';
$csrfParam = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf';
$csrfToken = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '';
?>

<style>
.main-weight-input::-webkit-inner-spin-button,
.main-weight-input::-webkit-outer-spin-button,
.main-score-select::-webkit-inner-spin-button,
.main-score-select::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.main-weight-input,
.main-score-select {
    -moz-appearance: textfield;
}
/* Multi-Step Wizard Styling */
.eval-wizard-nav .nav-link {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background-color: #fff;
    color: #495057;
    transition: all 0.2s ease-in-out;
    cursor: pointer;
}
.eval-wizard-nav .nav-link:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}
.eval-wizard-nav .nav-link.active {
    background-color: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 0.125rem 0.25rem rgba(13, 110, 253, 0.15);
}
.eval-wizard-nav .step-badge {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: bold;
}
.official-doc-card {
    background: #ffffff;
    border: 1px solid #c9d2db;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.doc-section-title {
    background-color: #f1f5f9;
    border-left: 4px solid #0d6efd;
    padding: 9px 14px;
    font-weight: bold;
    color: #1e293b;
    margin-bottom: 14px;
    border-radius: 0 4px 4px 0;
}
.grade-check-box {
    transition: all 0.2s;
    border-radius: 6px;
}
.grade-check-box.active-grade {
    background-color: #e7f1ff !important;
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 2px rgba(13,110,253,0.25);
}
</style>

<div class="self-assess-view py-3">

    <!-- Top Floating Header / Status Bar -->
    <div class="card card-rmutt shadow-sm mb-4 sticky-top bg-white border-primary border-2" style="top: 75px; z-index: 1020;">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <?= Html::a('<i class="bi bi-arrow-left me-1"></i> แผงควบคุม', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    <div>
                        <span class="fw-bold text-dark fs-6"><?= Html::encode($personnel->fullName) ?></span>
                        <span class="badge bg-primary ms-1"><?= Html::encode($personnel->personnelType->name_th) ?></span>
                    </div>
                </div>

                <?php if ($personnelType === 'CIVIL' || $personnelType === 'UNIVERSITY'): ?>
                    <!-- Quick Step Navigator in Sticky Bar -->
                    <div class="btn-group btn-group-sm d-none d-lg-inline-flex eval-step-quick-nav" role="group">
                        <button type="button" class="btn btn-primary btn-nav-step py-1 px-3 active" data-target-step="1" id="float-step-1">
                            <span class="badge bg-light text-primary me-1">๑</span> <?= $docCode ?>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-nav-step py-1 px-3" data-target-step="2" id="float-step-2">
                            <span class="badge bg-secondary text-white me-1">๒</span> ผลสัมฤทธิ์ (<?= $perfWeightTh ?>)
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-nav-step py-1 px-3" data-target-step="3" id="float-step-3">
                            <span class="badge bg-secondary text-white me-1">๓</span> สมรรถนะ (<?= $compWeightTh ?>)
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Live Score Engine & Actions -->
                <div class="d-flex align-items-center gap-3">
                    <div id="save-status" class="small text-muted">
                        <i class="bi bi-cloud-check text-success me-1"></i> บันทึกอัตโนมัติแล้ว
                    </div>

                    <?php if ($isEditable): ?>
                        <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btn-manual-save">
                            <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                        </button>
                        <button type="button" class="btn btn-sm btn-success shadow-sm" id="btn-submit-self-eval">
                            <i class="bi bi-send-fill me-1"></i> ส่งแบบประเมินตนเอง
                        </button>
                    <?php else: ?>
                        <span class="text-muted small me-1">แบบประเมินอยู่ในสถานะ:</span> <?= $evaluation->statusLabel ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Reason Notification Banner -->
    <?php if ($evaluation->status === \common\models\Evaluation::STATUS_RETURNED && !empty($evaluation->return_reason)): ?>
        <div class="alert alert-danger border-danger shadow-sm mb-4 d-flex align-items-start" role="alert">
            <i class="bi bi-arrow-counterclockwise text-danger fs-2 me-3 flex-shrink-0 mt-1"></i>
            <div>
                <h5 class="alert-heading fw-bold mb-1 text-danger"><i class="bi bi-exclamation-octagon me-1"></i> แบบประเมินนี้ถูกส่งกลับเพื่อแก้ไข</h5>
                <p class="mb-1 fs-6"><strong>เหตุผลจากผู้บังคับบัญชา:</strong> <?= nl2br(Html::encode($evaluation->return_reason)) ?></p>
                <?php if (!empty($evaluation->returned_at)): ?>
                    <small class="text-muted"><i class="bi bi-clock me-1"></i> ส่งกลับเมื่อ: <?= Yii::$app->formatter->asDatetime($evaluation->returned_at) ?></small>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($personnelType !== 'CIVIL' && $personnelType !== 'UNIVERSITY'): ?>
    <!-- Official Header Banner (For Non-CIVIL/UNIVERSITY types) -->
    <div class="card card-rmutt shadow-sm mb-4 bg-light border">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">แบบข้อตกลงและประเมินผลการปฏิบัติงานบุคลากร</h5>
                <h6 class="text-muted mb-0">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</h6>
                <div class="text-primary fw-bold mt-1"><?= Html::encode($evaluation->cycle->name_th) ?> (<?= Yii::$app->formatter->asDate($evaluation->cycle->period_start, 'php:d M Y') ?> - <?= Yii::$app->formatter->asDate($evaluation->cycle->period_end, 'php:d M Y') ?>)</div>
            </div>
            <div class="row g-2 small border-top pt-3">
                <div class="col-md-4">
                    <strong>ชื่อผู้รับการประเมิน:</strong> <?= Html::encode($personnel->fullName) ?><br>
                    <strong>ผู้บังคับบัญชา (ผู้ประเมิน):</strong> <?= $evaluation->evaluator ? Html::encode($evaluation->evaluator->fullName) : '-' ?>
                </div>
                <div class="col-md-4">
                    <strong>ตำแหน่ง / ระดับ:</strong> <?= Html::encode($personnel->position->name_th) ?> (<?= Html::encode($personnel->position->level_label ?: '-') ?>)<br>
                    <strong>ตำแหน่งผู้ประเมิน:</strong> <?= ($evaluation->evaluator && $evaluation->evaluator->position) ? Html::encode($evaluation->evaluator->position->name_th) : '-' ?>
                </div>
                <div class="col-md-4">
                    <strong>ฝ่าย / สังกัด:</strong> <?= Html::encode($personnel->department->name_th) ?><br>
                    <?php if ($personnelType === 'GOVT' || !empty($personnel->contract_start_date)): ?>
                        <strong>สัญญาจ้าง:</strong> <?= $personnel->contract_start_date ? Yii::$app->formatter->asDate($personnel->contract_start_date, 'php:d M Y') : '-' ?> ถึง <?= $personnel->contract_end_date ? Yii::$app->formatter->asDate($personnel->contract_end_date, 'php:d M Y') : '-' ?><br>
                    <?php endif; ?>
                    <strong>สถานะแบบประเมิน:</strong> <?= $evaluation->statusLabel ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- TEMPLATE TYPE: CIVIL & UNIVERSITY EMPLOYEE (ข้าราชการ & พนักงานมหาวิทยาลัย) -->
    <!-- ========================================================================= -->
    <?php if ($personnelType === 'CIVIL' || $personnelType === 'UNIVERSITY'): 
        $secMain = null;
        $secPolicy = null;
        $secAcad = null;
        foreach ($sections as $s) {
            if ($s->section_code === 'MAIN_WORK') $secMain = $s;
            elseif ($s->section_code === 'SECONDARY_POLICY') $secPolicy = $s;
            elseif ($s->section_code === 'SECONDARY_ACADEMIC') $secAcad = $s;
        }

        $mainItem = ($secMain && !empty($secMain->items)) ? $secMain->items[0] : null;
        $policyItem = ($secPolicy && !empty($secPolicy->items)) ? $secPolicy->items[0] : null;
        $acadItem = ($secAcad && !empty($secAcad->items)) ? $secAcad->items[0] : null;

        $mainAns = $mainItem ? ($answers[$mainItem->id] ?? null) : null;
        $mainWorkRows = ($mainAns && !empty($mainAns->json_value)) 
            ? (is_string($mainAns->json_value) ? json_decode($mainAns->json_value, true) : $mainAns->json_value) 
            : [
                ['title' => '', 'self_score' => 5, 'weight' => ''],
            ];

        $policyAns = $policyItem ? ($answers[$policyItem->id] ?? null) : null;
        $policySelected = ($policyAns && !empty($policyAns->json_value)) 
            ? (is_string($policyAns->json_value) ? json_decode($policyAns->json_value, true) : $policyAns->json_value) 
            : [];

        $acadAns = $acadItem ? ($answers[$acadItem->id] ?? null) : null;
        $acadVal = $acadAns ? intval($acadAns->numeric_value ?? 0) : 0;

        $policy7Options = [
            '1' => 'งานบริการวิชาการหารายได้ตั้งแต่ ๑๐,๐๐๐.- บาทขึ้นไป (สะสมใน ๑ ปี)',
            '2' => 'นวัตกรรม/สร้างสรรค์ โดยเป็นผู้ดำเนินการหลักหรือผู้ร่วมซึ่งมีส่วนร่วม ร้อยละ ๓๐ ขึ้นไป โดยใช้แบบฟอร์มการแสดงการมีส่วนร่วม',
            '3' => 'การพัฒนาตนเองด้านภาษาต่างประเทศ โดยมีใบรับรองตามมาตรฐาน เช่น RT-TEP ๓.๕/ IELTS ๕.๕ /TOEFL ๔๐๐ หรือกิจกรรมด้านภาษาที่คณะกรรมการรับรอง / วิชาชีพเฉพาะทาง โดยมีใบรับรองตามมาตรฐาน เช่น ใบ Certificate จากระบบ Certiport (ภายในปีงบประมาณ หรือย้อนหลัง ๑ ปี **๑ Certificate ใช้ได้ ๒ รอบประเมิน)',
            '4' => 'การเข้าร่วมกิจกรรมของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๔ ครั้งขึ้นไป/รอบการประเมิน (ดังเอกสารแนบ)',
            '5' => 'คณะกรรมการการดำเนินงานด้านต่าง ๆ ของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๓ งาน/โครงการขึ้นไป (**สามารถสะสมได้ภายใน ๑ ปี)',
            '6' => 'ปฏิบัติหน้าที่หัวหน้าฝ่าย (เท่ากับ ๒ ข้อ)',
            '7' => 'ปฏิบัติหน้าที่หัวหน้างาน (เท่ากับ ๑ ข้อ)',
        ];

        $acad5Levels = [
            '0' => 'ระดับ ๐: ไม่มีการจัดทำ / ไม่เข้าเกณฑ์ (๐ คะแนน)',
            '1' => 'ระดับ ๑: ยื่นผลงานให้ผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญพิจารณา (แนบเอกสารขอความอนุเคราะห์/ คำสั่งแต่งตั้ง)',
            '2' => 'ระดับ ๒: ผ่านการพิจารณาจากผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญในงานที่เกี่ยวข้อง ตรวจเบื้องต้น (แนบแบบประเมินผลงาน)',
            '3' => 'ระดับ ๓: ผ่านการพิจารณาผู้บังคับบัญชาภายในหน่วยงาน ส่งไปยัง กบค. (แนบบันทึกข้อความ)',
            '4' => 'ระดับ ๔: อยู่ระหว่างการพิจารณาจาก กบค. (ใช้หลักฐานสถานะการดำเนินการจาก กบค.)',
            '5' => 'ระดับ ๕: เผยแพร่ผลงานทางวิชาการ เป็นตำรา หนังสือบทความ และหรือ ได้ตำแหน่งที่สูงขึ้น (แนบคำสั่งแต่งตั้ง หรือหลักฐาน)',
        ];
    ?>

        <?php 
        $policyCount = count($policySelected);
        $policyScore = 0;
        if ($policyCount >= 3) $policyScore = 5;
        elseif ($policyCount === 2) $policyScore = 3;
        elseif ($policyCount === 1) $policyScore = 1;
        $policyWeighted = ($policyScore / 5.0) * 15.0;

        $acadScore = floatval($acadVal);
        $acadWeighted = ($acadScore / 5.0) * 5.0;
        ?>

        <?php
        $toTh = function($str) {
            return str_replace(['0','1','2','3','4','5','6','7','8','9'], ['๐','๑','๒','๓','๔','๕','๖','๗','๘','๙'], (string)$str);
        };
        $cycleNum = $evaluation->cycle ? intval($evaluation->cycle->cycle_number) : 1;
        $fiscalYear = $evaluation->cycle ? intval($evaluation->cycle->fiscal_year) : (intval(date('Y')) + 543);
        $prevYear = $fiscalYear - 1;
        $docTitle = ($personnelType === 'CIVIL') 
            ? 'แบบสรุปการประเมินผลการปฏิบัติราชการของข้าราชการพลเรือนในสถาบันอุดมศึกษา' 
            : 'แบบสรุปการประเมินผลการปฏิบัติราชการของพนักงานมหาวิทยาลัย';
        $docCode = ($personnelType === 'CIVIL') ? 'แบบสรุป ปร.' : 'แบบสรุป ปม.';
        $evaluatorName = $evaluation->evaluator ? $evaluation->evaluator->fullName : ($evaluation->evaluatorL1 ? $evaluation->evaluatorL1->fullName : '-');
        $evaluatorPos = ($evaluation->evaluator && $evaluation->evaluator->position) ? $evaluation->evaluator->position->name_th : (($evaluation->evaluatorL1 && $evaluation->evaluatorL1->position) ? $evaluation->evaluatorL1->position->name_th : '-');
        $evaluatorL2Name = ($evaluation->evaluatorL2) ? $evaluation->evaluatorL2->fullName : '...................................................';
        $evaluatorL2Pos = ($evaluation->evaluatorL2 && $evaluation->evaluatorL2->position) ? $evaluation->evaluatorL2->position->name_th : '...................................................';
        ?>

        <!-- Wizard Step Indicator Tabs -->
        <div class="card card-rmutt shadow-sm mb-4 border-0" id="evalWizardNavCard">
            <div class="card-body p-2 bg-light rounded-3 border">
                <ul class="nav nav-pills nav-fill gap-2 eval-wizard-nav" id="evalWizardTabs">
                    <li class="nav-item">
                        <button type="button" class="nav-link active eval-step-btn text-start p-3 border shadow-sm w-100" id="step-tab-1" data-step="1">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary text-white rounded-circle me-3 step-badge">๑</span>
                                <div>
                                    <div class="fw-bold fs-6 step-title">๑. แบบสรุปการประเมิน (<?= $docCode ?>)</div>
                                    <small class="text-muted d-block">ข้อมูลทั่วไป &bull; สรุปผลคะแนน &bull; แผนพัฒนา (IDP)</small>
                                </div>
                            </div>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link eval-step-btn text-start p-3 border shadow-sm w-100" id="step-tab-2" data-step="2">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary text-white rounded-circle me-3 step-badge">๒</span>
                                <div>
                                    <div class="fw-bold fs-6 step-title">๒. แบบผลสัมฤทธิ์ของงาน (แบบ ป.ผ.)</div>
                                    <small class="text-muted d-block">ภาระงานหลัก (๘๐%) &bull; งานนโยบาย (๑๕%) &bull; คู่มือ (๕%)</small>
                                </div>
                            </div>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link eval-step-btn text-start p-3 border shadow-sm w-100" id="step-tab-3" data-step="3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary text-white rounded-circle me-3 step-badge">๓</span>
                                <div>
                                    <div class="fw-bold fs-6 step-title">๓. แบบประเมินสมรรถนะ (พม.)</div>
                                    <small class="text-muted d-block">สมรรถนะหลัก &bull; ประจำสายงาน (<?= $compWeightTh ?>) &bull; เอกสารแนบ</small>
                                </div>
                            </div>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- STEP 1: แบบสรุป ปม. (ตรงตามแบบฟอร์มทางการ 1. แบบสรุป...doc)              -->
        <!-- ========================================================================= -->
        <div class="eval-step-pane" id="step-pane-1">
            <div class="card card-rmutt shadow-sm mb-4 official-doc-card">
                <div class="card-body p-4 p-md-5">

                    <!-- Document Header -->
                    <div class="d-flex justify-content-end mb-2">
                        <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm"><?= $docCode ?></span>
                    </div>
                    <div class="text-center mb-4 pb-2 border-bottom">
                        <h4 class="fw-bold text-dark mb-2"><?= $docTitle ?></h4>
                        <div class="text-muted fs-6">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</div>
                    </div>

                    <!-- ส่วนที่ ๑ : ข้อมูลของผู้รับการประเมิน -->
                    <div class="mb-4">
                        <div class="doc-section-title">
                            <i class="bi bi-person-lines-fill me-1 text-primary"></i> ส่วนที่ ๑ : ข้อมูลของผู้รับการประเมิน
                        </div>

                        <!-- รอบการประเมิน -->
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="fw-bold text-dark mb-2">รอบการประเมิน :</div>
                            <div class="ps-2">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="cycle_display_radio" id="cycle_opt_1" <?= $cycleNum == 1 ? 'checked' : '' ?> disabled>
                                    <label class="form-check-label fw-bold <?= $cycleNum == 1 ? 'text-primary' : 'text-muted' ?>" for="cycle_opt_1">
                                        รอบที่ ๑ : ๑ ตุลาคม <?= $toTh($prevYear) ?> ถึง ๓๑ มีนาคม <?= $toTh($fiscalYear) ?>
                                        <?= $cycleNum == 1 ? '<span class="badge bg-success ms-2">รอบการประเมินปัจจุบัน</span>' : '' ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="cycle_display_radio" id="cycle_opt_2" <?= $cycleNum == 2 ? 'checked' : '' ?> disabled>
                                    <label class="form-check-label fw-bold <?= $cycleNum == 2 ? 'text-primary' : 'text-muted' ?>" for="cycle_opt_2">
                                        รอบที่ ๒ : ๑ เมษายน <?= $toTh($fiscalYear) ?> ถึง ๓๐ กันยายน <?= $toTh($fiscalYear) ?>
                                        <?= $cycleNum == 2 ? '<span class="badge bg-success ms-2">รอบการประเมินปัจจุบัน</span>' : '' ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- ข้อมูลบุคลากรและผู้ประเมิน -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white h-100 shadow-sm">
                                    <div class="text-muted small mb-1">ชื่อผู้รับการประเมิน (นาย / นาง / นางสาว)</div>
                                    <div class="fw-bold text-dark fs-6"><?= Html::encode($personnel->fullName) ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded bg-white h-100 shadow-sm">
                                    <div class="text-muted small mb-1">ตำแหน่ง</div>
                                    <div class="fw-bold text-dark"><?= Html::encode($personnel->position->name_th) ?><?= !empty($personnel->position->level_label) ? ' (' . Html::encode($personnel->position->level_label) . ')' : '' ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded bg-white h-100 shadow-sm">
                                    <div class="text-muted small mb-1">สังกัด</div>
                                    <div class="fw-bold text-dark"><?= Html::encode($personnel->department->name_th) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white h-100 shadow-sm">
                                    <div class="text-muted small mb-1">ชื่อผู้ประเมิน (นาย / นาง / นางสาว)</div>
                                    <div class="fw-bold text-dark fs-6"><?= Html::encode($evaluatorName) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white h-100 shadow-sm">
                                    <div class="text-muted small mb-1">ตำแหน่ง</div>
                                    <div class="fw-bold text-dark"><?= Html::encode($evaluatorPos) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนที่ ๒ : การสรุปผลการประเมิน -->
                    <div class="mb-4">
                        <div class="doc-section-title">
                            <i class="bi bi-calculator me-1 text-primary"></i> ส่วนที่ ๒ : การสรุปผลการประเมิน
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle text-center mb-0" id="summary-eval-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45%;" class="text-start py-2">องค์ประกอบการประเมิน</th>
                                        <th style="width: 18%;" class="py-2">คะแนน (ก)</th>
                                        <th style="width: 18%;" class="py-2">น้ำหนัก (ข)</th>
                                        <th style="width: 19%;" class="py-2">รวมคะแนน (ก) &times; (ข)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start py-3">
                                            <strong class="text-dark">องค์ประกอบที่ ๑ : ผลสัมฤทธิ์ของงาน</strong>
                                            <div class="small text-muted mt-1"><i class="bi bi-arrow-right-short text-primary"></i> คำนวณจากแบบที่ ๒ แบบข้อตกลงการประเมินผลสัมฤทธิ์ของงาน</div>
                                        </td>
                                        <td class="fw-bold fs-6"><span id="summary-perf-score">0.00</span> / ๑๐๐</td>
                                        <td class="fw-bold"><?= $perfWeightTh ?></td>
                                        <td class="fw-bold text-primary fs-5"><span id="summary-perf-weighted">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start py-3">
                                            <strong class="text-dark">องค์ประกอบที่ ๒ : พฤติกรรมการปฏิบัติราชการ (สมรรถนะ)</strong>
                                            <div class="small text-muted mt-1"><i class="bi bi-arrow-right-short text-primary"></i> คำนวณจากแบบที่ ๓ แบบข้อตกลงการประเมินสมรรถนะ</div>
                                        </td>
                                        <td class="fw-bold fs-6"><span id="summary-comp-score">0.00</span> / ๑๐๐</td>
                                        <td class="fw-bold"><?= $compWeightTh ?></td>
                                        <td class="fw-bold text-primary fs-5"><span id="summary-comp-weighted">0.00</span></td>
                                    </tr>
                                    <tr class="text-muted">
                                        <td class="text-start py-2">องค์ประกอบอื่น (ถ้ามี)</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                    </tr>
                                    <tr class="table-success fw-bold">
                                        <td class="text-end py-3 fs-6">รวม :</td>
                                        <td>-</td>
                                        <td class="text-success fs-6">๑๐๐%</td>
                                        <td class="text-success fs-4"><span id="summary-total-score">0.00</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ระดับผลการประเมิน -->
                        <div class="p-3 bg-light rounded border">
                            <div class="fw-bold text-dark mb-2"><i class="bi bi-award me-1 text-warning"></i> ระดับผลการประเมิน :</div>
                            <div class="row g-2 text-center">
                                <div class="col-md">
                                    <div class="p-2 border rounded bg-white grade-check-box" id="grade-box-excellent">
                                        <div class="form-check d-inline-block text-start mb-0">
                                            <input class="form-check-input" type="radio" name="summary_grade_radio" id="grade_excellent" disabled>
                                            <label class="form-check-label fw-bold text-dark" for="grade_excellent">
                                                <span class="badge bg-success me-1">ดีเด่น</span><br>
                                                <small class="text-muted">(๙๐.๐๐ - ๑๐๐)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="p-2 border rounded bg-white grade-check-box" id="grade-box-verygood">
                                        <div class="form-check d-inline-block text-start mb-0">
                                            <input class="form-check-input" type="radio" name="summary_grade_radio" id="grade_verygood" disabled>
                                            <label class="form-check-label fw-bold text-dark" for="grade_verygood">
                                                <span class="badge bg-primary me-1">ดีมาก</span><br>
                                                <small class="text-muted">(๘๐.๐๐ - ๘๙.๙๙)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="p-2 border rounded bg-white grade-check-box" id="grade-box-good">
                                        <div class="form-check d-inline-block text-start mb-0">
                                            <input class="form-check-input" type="radio" name="summary_grade_radio" id="grade_good" disabled>
                                            <label class="form-check-label fw-bold text-dark" for="grade_good">
                                                <span class="badge bg-info text-dark me-1">ดี</span><br>
                                                <small class="text-muted">(๗๐.๐๐ - ๗๙.๙๙)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="p-2 border rounded bg-white grade-check-box" id="grade-box-fair">
                                        <div class="form-check d-inline-block text-start mb-0">
                                            <input class="form-check-input" type="radio" name="summary_grade_radio" id="grade_fair" disabled>
                                            <label class="form-check-label fw-bold text-dark" for="grade_fair">
                                                <span class="badge bg-warning text-dark me-1">พอใช้</span><br>
                                                <small class="text-muted">(๖๐.๐๐ - ๖๙.๙๙)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="p-2 border rounded bg-white grade-check-box" id="grade-box-poor">
                                        <div class="form-check d-inline-block text-start mb-0">
                                            <input class="form-check-input" type="radio" name="summary_grade_radio" id="grade_poor" disabled>
                                            <label class="form-check-label fw-bold text-dark" for="grade_poor">
                                                <span class="badge bg-danger me-1">ต้องปรับปรุง</span><br>
                                                <small class="text-muted">(ต่ำกว่า ๖๐.๐๐)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนที่ ๓ : แผนพัฒนาการปฏิบัติราชการรายบุคคล (IDP) -->
                    <?php
                    $savedIdp = [];
                    if (!empty($evaluation->idp_data)) {
                        $savedIdp = is_array($evaluation->idp_data) ? $evaluation->idp_data : json_decode($evaluation->idp_data, true);
                    }
                    if (empty($savedIdp) || !is_array($savedIdp)) {
                        // Default 1 blank row for user to fill (เว้นว่างไว้ทั้งหมดตามต้นฉบับ)
                        $savedIdp = [
                            [
                                'topic' => '',
                                'method' => '',
                                'timeline' => ''
                            ]
                        ];
                    }
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="doc-section-title mb-0 flex-grow-1">
                                <i class="bi bi-graph-up-arrow me-1 text-primary"></i> ส่วนที่ ๓: แผนพัฒนาการปฏิบัติราชการรายบุคคล
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="summary-idp-table">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th style="min-width: 250px;">ความรู้ / ทักษะ / สมรรถนะ ที่ต้องได้รับการพัฒนา</th>
                                        <th style="min-width: 280px;">วิธีการพัฒนา</th>
                                        <th style="width: 210px;">ช่วงเวลาที่ต้องการการพัฒนา</th>
                                        <?php if ($isEditable): ?>
                                            <th style="width: 50px;">ลบ</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="summary-idp-tbody">
                                    <?php foreach ($savedIdp as $idpIdx => $idpRow): ?>
                                        <tr class="summary-idp-row">
                                            <td class="text-center fw-bold idp-row-number"><?= $toTh($idpIdx + 1) ?></td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm idp-topic-input auto-save-field" 
                                                       placeholder="ระบุความรู้ / ทักษะ / สมรรถนะ ที่ต้องได้รับการพัฒนา..." 
                                                       value="<?= Html::encode($idpRow['topic'] ?? '') ?>" 
                                                       <?= !$isEditable ? 'readonly' : '' ?>>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm idp-method-input auto-save-field" 
                                                       placeholder="ระบุวิธีการพัฒนา..." 
                                                       value="<?= Html::encode($idpRow['method'] ?? '') ?>" 
                                                       <?= !$isEditable ? 'readonly' : '' ?>>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm idp-timeline-input auto-save-field" 
                                                       placeholder="ระบุช่วงเวลาที่ต้องการการพัฒนา..." 
                                                       value="<?= Html::encode($idpRow['timeline'] ?? '') ?>" 
                                                       <?= !$isEditable ? 'readonly' : '' ?>>
                                            </td>
                                            <?php if ($isEditable): ?>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-idp-row" title="ลบแถว">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($isEditable): ?>
                            <div class="mt-2 text-start">
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btn-add-idp-row">
                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการแผนพัฒนา (IDP)
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ส่วนที่ ๔ : การรับทราบผลการประเมิน -->
                    <?php
                    $isAcknowledged = !empty($evaluation->acknowledgement_at);
                    $isEvalNotified = !empty($evaluation->completed_at) || !empty($evaluation->supervisor_evaluated_at);
                    $hasL2Review = !empty($evaluation->l2_evaluated_at);
                    ?>
                    <div class="mb-4">
                        <div class="doc-section-title d-flex flex-wrap justify-content-between align-items-center">
                            <span><i class="bi bi-pen me-1 text-primary"></i> ส่วนที่ ๔ : การรับทราบผลการประเมิน</span>
                            <span class="badge bg-light text-secondary border fw-normal"><i class="bi bi-info-circle me-1"></i> ขั้นตอนหลังประเมินผลเสร็จสิ้น</span>
                        </div>
                        <div class="alert alert-light border py-2 px-3 mb-3 small text-muted">
                            <i class="bi bi-info-circle text-primary me-1"></i> <strong>คำชี้แจง:</strong> การแจ้งผลและการลงนามรับทราบผลการประเมินจะดำเนินการหลังจากผู้ประเมินได้ประเมินผลการปฏิบัติราชการเรียบร้อยแล้ว ในขั้นตอนการประเมินตนเองนี้จึงยังไม่มีการลงนามใด ๆ
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 border-end">
                                <div class="p-3 bg-light rounded h-100 border">
                                    <div class="fw-bold text-dark mb-2">ผู้รับการประเมิน :</div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="ack_check_display" <?= $isAcknowledged ? 'checked' : '' ?> disabled>
                                        <label class="form-check-label <?= $isAcknowledged ? 'text-dark' : 'text-muted' ?>" for="ack_check_display">
                                            ได้รับทราบผลการประเมินและแผนพัฒนาการปฏิบัติราชการรายบุคคลแล้ว
                                        </label>
                                    </div>
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="mb-2">ลงชื่อ : <span class="<?= $isAcknowledged ? 'fw-bold text-dark' : 'text-muted' ?> border-bottom pb-1 px-2"><?= $isAcknowledged ? Html::encode($personnel->fullName) : '...................................................' ?></span> (ผู้รับการประเมิน)</div>
                                        <div class="mb-2">ตำแหน่ง : <span class="text-muted border-bottom pb-1 px-2"><?= $isAcknowledged ? Html::encode($personnel->position->name_th) : '...................................................' ?></span></div>
                                        <div>วันที่ : <span class="text-muted border-bottom pb-1 px-2"><?= $isAcknowledged ? Yii::$app->formatter->asDate($evaluation->acknowledgement_at, 'php:d/m/Y') : '...................................................' ?></span></div>
                                        <?php if (!$isAcknowledged): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border mt-2"><i class="bi bi-clock me-1"></i> รอลงนามเมื่อผู้ประเมินได้แจ้งผลแล้ว</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded h-100 border">
                                    <div class="fw-bold text-dark mb-2">ผู้ประเมิน :</div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="eval_notify_disp" id="eval_notified_disp" <?= $isEvalNotified ? 'checked' : '' ?> disabled>
                                        <label class="form-check-label <?= $isEvalNotified ? 'text-dark' : 'text-muted' ?>" for="eval_notified_disp">
                                            ได้แจ้งผลการประเมินและผู้รับการประเมินได้ลงนามรับทราบ
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="eval_notify_disp" id="eval_not_signed_disp" disabled>
                                        <label class="form-check-label text-muted" for="eval_not_signed_disp">
                                            ได้แจ้งผลการประเมินเมื่อวันที่.................................... แต่ผู้รับการประเมินไม่ลงนาม (มีพยาน)
                                        </label>
                                    </div>
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="mb-2">ลงชื่อ : <span class="<?= $isEvalNotified ? 'fw-bold text-dark' : 'text-muted' ?> border-bottom pb-1 px-2"><?= $isEvalNotified ? Html::encode($evaluatorName) : '...................................................' ?></span> (ผู้ประเมิน)</div>
                                        <div class="mb-2">ตำแหน่ง : <span class="text-muted border-bottom pb-1 px-2"><?= $isEvalNotified ? Html::encode($evaluatorPos) : '...................................................' ?></span></div>
                                        <div>วันที่ : <span class="text-muted border-bottom pb-1 px-2"><?= ($isEvalNotified && $evaluation->completed_at) ? Yii::$app->formatter->asDate($evaluation->completed_at, 'php:d/m/Y') : '...................................................' ?></span></div>
                                        <?php if (!$isEvalNotified): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border mt-2"><i class="bi bi-clock me-1"></i> สำหรับผู้ประเมินลงนามเมื่อแจ้งผลแล้ว</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนที่ ๕ : ความเห็นของผู้บังคับบัญชาเหนือขึ้นไป -->
                    <div class="mb-4">
                        <div class="doc-section-title d-flex flex-wrap justify-content-between align-items-center">
                            <span><i class="bi bi-chat-square-quote me-1 text-primary"></i> ส่วนที่ ๕ : ความเห็นของผู้บังคับบัญชาเหนือขึ้นไป</span>
                            <span class="badge bg-light text-secondary border fw-normal"><i class="bi bi-info-circle me-1"></i> ขั้นตอนการพิจารณาชั้นเหนือขึ้นไป</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 border-end">
                                <div class="p-3 bg-light rounded h-100 border">
                                    <div class="fw-bold text-dark mb-2">ผู้บังคับบัญชาเหนือขึ้นไป :</div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="sup_disp_1" id="sup_disp_1_yes" <?= $hasL2Review ? 'checked' : '' ?> disabled>
                                        <label class="form-check-label <?= $hasL2Review ? 'text-dark' : 'text-muted' ?>" for="sup_disp_1_yes">เห็นด้วยกับผลการประเมิน</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="sup_disp_1" id="sup_disp_1_no" disabled>
                                        <label class="form-check-label text-muted" for="sup_disp_1_no">มีความเห็นต่าง</label>
                                    </div>
                                    <?php if ($hasL2Review && (!empty($evaluation->l2_comment_suggestion) || !empty($evaluation->supervisor_comment_suggestion))): ?>
                                        <div class="small p-2 bg-white rounded border mb-2 text-dark">
                                            <?= Html::encode($evaluation->l2_comment_suggestion ?: $evaluation->supervisor_comment_suggestion) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="mb-2">ลงชื่อ : <span class="<?= $hasL2Review ? 'fw-bold text-dark' : 'text-muted' ?> border-bottom pb-1 px-2"><?= $hasL2Review ? Html::encode($evaluatorL2Name) : '...................................................' ?></span></div>
                                        <div class="mb-2">ตำแหน่ง : <span class="text-muted border-bottom pb-1 px-2"><?= $hasL2Review ? Html::encode($evaluatorL2Pos) : '...................................................' ?></span></div>
                                        <div>วันที่ : <span class="text-muted border-bottom pb-1 px-2"><?= ($hasL2Review && $evaluation->l2_evaluated_at) ? Yii::$app->formatter->asDate($evaluation->l2_evaluated_at, 'php:d/m/Y') : '...................................................' ?></span></div>
                                        <?php if (!$hasL2Review): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border mt-2"><i class="bi bi-clock me-1"></i> สำหรับผู้บังคับบัญชาเหนือขึ้นไปลงความเห็นและลงนาม</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded h-100 border">
                                    <div class="fw-bold text-dark mb-2">ผู้บังคับบัญชาเหนือขึ้นไปอีกชั้นหนึ่ง (ถ้ามี) :</div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="sup_disp_2" id="sup_disp_2_yes" disabled>
                                        <label class="form-check-label text-muted" for="sup_disp_2_yes">เห็นด้วยกับผลการประเมิน</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="sup_disp_2" id="sup_disp_2_no" disabled>
                                        <label class="form-check-label text-muted" for="sup_disp_2_no">มีความเห็นต่าง</label>
                                    </div>
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="mb-2">ลงชื่อ : <span class="border-bottom pb-1 px-2 text-muted">...................................................</span></div>
                                        <div class="mb-2">ตำแหน่ง : <span class="text-muted border-bottom pb-1 px-2">...................................................</span></div>
                                        <div>วันที่ : <span class="text-muted border-bottom pb-1 px-2">...................................................</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1 Bottom Action Bar -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 border-top gap-2">
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1 text-primary"></i> ตรวจสอบข้อมูลส่วนตัวและผลการประเมินภาพรวม จากนั้นคลิก <strong>"ถัดไป"</strong> เพื่อประเมินผลสัมฤทธิ์ของงาน
                        </div>
                        <button type="button" class="btn btn-primary px-4 py-2 fw-bold btn-nav-step shadow-sm" data-target-step="2">
                            ถัดไป: แบบผลสัมฤทธิ์ของงาน (แบบ ป.ผ.) <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- STEP 2: แบบ ป.ผ. (ผลสัมฤทธิ์ของงาน ๘๐%)                                    -->
        <!-- ========================================================================= -->
        <div class="eval-step-pane" id="step-pane-2" style="display: none;">

        <!-- FORM 2: PERFORMANCE (80%) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-table me-1"></i> แบบข้อตกลงการประเมินผลสัมฤทธิ์ของงาน (แบบที่ ๒)</h5>
                    <small class="text-white-50">เต็ม ๑๐๐ คะแนน (ภาระงานหลัก ๘๐% + ๕.๑ งานนโยบาย ๑๕% + ๕.๒ คู่มือ/วิชาการ ๕%) ถ่วงน้ำหนัก <?= $perfWeightTh ?> ในภาพรวม</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    น้ำหนักรวม: <span id="header-total-weight">100</span>% / 100%
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="main-work-table">
                        <thead class="table-light text-center align-middle">
                            <tr>
                                <th rowspan="2" style="width: 45px;">#</th>
                                <th rowspan="2" style="min-width: 220px;">(๑) กิจกรรม/โครงการ/งาน</th>
                                <th rowspan="2" style="min-width: 320px;">(๒) ตัวชี้วัด</th>
                                <th rowspan="2" style="width: 110px;">บุคลากร<br>ประเมินตนเอง</th>
                                <th colspan="5" class="text-center py-1">(๓) ระดับค่าเป้าหมาย</th>
                                <th rowspan="2" style="width: 70px;">(๔)<br>ค่าคะแนน<br>ที่ได้</th>
                                <th rowspan="2" style="width: 105px; min-width: 95px;">(๕)<br>น้ำหนัก<br>(%)</th>
                                <th rowspan="2" style="width: 105px;">(๖)<br>คะแนนถ่วงน้ำหนัก<br><small class="text-muted">((๔)X(๕))/๑๐๐</small></th>
                                <?php if ($isEditable): ?>
                                    <th rowspan="2" style="width: 45px;">ลบ</th>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <th style="width: 30px;" class="text-center py-1">๑</th>
                                <th style="width: 30px;" class="text-center py-1">๒</th>
                                <th style="width: 30px;" class="text-center py-1">๓</th>
                                <th style="width: 30px;" class="text-center py-1">๔</th>
                                <th style="width: 30px;" class="text-center py-1">๕</th>
                            </tr>
                        </thead>
                        <tbody id="main-work-tbody">
                            <!-- 1. ภาระงานหลัก -->
                            <tr class="table-light fw-bold">
                                <td colspan="<?= $isEditable ? 14 : 13 ?>" class="py-2 bg-light text-start">
                                    <i class="bi bi-briefcase me-1 text-primary"></i> ภาระงานหลัก (ค่าน้ำหนัก ๖๐ - ๘๐ ข้อละไม่เกิน ๓๐) ระบุงานที่ปฏิบัติ (รายละเอียดข้อมูล)
                                </td>
                            </tr>
                            <?php foreach ($mainWorkRows as $idx => $row): 
                                $w = isset($row['weight']) && $row['weight'] !== '' ? floatval($row['weight']) : '';
                                $sc = round(floatval($row['self_score'] ?? 5), 1);
                                $weighted = ($w !== '') ? (($sc / 5.0) * floatval($w)) : 0.0;
                            ?>
                                <tr class="main-work-row">
                                    <td class="text-center fw-bold row-index"><?= $idx + 1 ?></td>
                                    <td>
                                        <textarea class="form-control form-control-sm auto-save-field main-work-title mb-1" name="main_work[<?= $idx ?>][title]" rows="3" placeholder="ระบุกิจกรรม/โครงการ/ภาระงานหลัก (รายละเอียดข้อมูล)..." required <?= !$isEditable ? 'readonly' : '' ?>><?= Html::encode($row['title'] ?? '') ?></textarea>
                                    </td>
                                    <td>
                                        <div class="p-2 bg-light rounded border text-start pdca-info-box" style="font-size: 0.78rem; line-height: 1.4;">
                                            <strong class="text-primary d-block mb-1"><i class="bi bi-info-circle me-1"></i> ระดับความสำเร็จ (ตามวงจร PDCA):</strong>
                                            <span class="d-block mb-1 px-1 rounded pdca-line <?= intval(floor($sc)) == 1 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="1"><strong>ระดับ ๑ (Plan):</strong> มีแผนการดำเนินงาน/แนวทางการดำเนินงาน</span>
                                            <span class="d-block mb-1 px-1 rounded pdca-line <?= intval(floor($sc)) == 2 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="2"><strong>ระดับ ๒ (Do):</strong> ดำเนินการตามแผน/แนวทางที่กำหนด</span>
                                            <span class="d-block mb-1 px-1 rounded pdca-line <?= intval(floor($sc)) == 3 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="3"><strong>ระดับ ๓ (Check):</strong> ทบทวน ตรวจสอบ ประเมินผลการดำเนินงาน</span>
                                            <span class="d-block mb-1 px-1 rounded pdca-line <?= intval(floor($sc)) == 4 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="4"><strong>ระดับ ๔ (Act):</strong> แก้ไขปรับปรุงกระบวนการ</span>
                                            <span class="d-block px-1 rounded pdca-line <?= intval(floor($sc)) == 5 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="5"><strong>ระดับ ๕ (Impact):</strong> ปรับปรุงต่อเนื่อง สร้างคุณค่าเพิ่มหรือนวัตกรรม</span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="input-group input-group-sm justify-content-center" style="min-width: 95px; max-width: 120px; margin: 0 auto;">
                                            <span class="input-group-text px-1 text-muted small">ระดับ</span>
                                            <input type="number" step="0.1" min="1" max="5" class="form-control form-control-sm text-center auto-save-field main-score-select fw-bold border-primary" name="main_work[<?= $idx ?>][self_score]" value="<?= $sc ?>" placeholder="1-5" required <?= !$isEditable ? 'readonly' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-center main-tgt-col main-tgt-1"><span class="<?= intval(floor($sc)) == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                    <td class="text-center main-tgt-col main-tgt-2"><span class="<?= intval(floor($sc)) == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                    <td class="text-center main-tgt-col main-tgt-3"><span class="<?= intval(floor($sc)) == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                    <td class="text-center main-tgt-col main-tgt-4"><span class="<?= intval(floor($sc)) == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                    <td class="text-center main-tgt-col main-tgt-5"><span class="<?= intval(floor($sc)) == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                    <td class="text-center fw-bold text-dark main-raw-score"><?= $sc ?></td>
                                    <td>
                                        <div class="input-group input-group-sm flex-nowrap justify-content-center" style="min-width: 80px; max-width: 95px; margin: 0 auto;">
                                            <input type="number" step="1" min="1" max="80" class="form-control form-control-sm text-center auto-save-field main-weight-input fw-bold px-1" name="main_work[<?= $idx ?>][weight]" value="<?= $w ?>" placeholder="0" required <?= !$isEditable ? 'readonly' : '' ?>>
                                            <span class="input-group-text px-1 text-muted">%</span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold text-primary row-weighted-score">
                                        <?= number_format($weighted, 2) ?>
                                    </td>
                                    <?php if ($isEditable): ?>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($isEditable): ?>
                                <tr id="row-add-main-btn">
                                    <td colspan="<?= $isEditable ? 14 : 13 ?>" class="p-2 bg-light text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" id="btn-add-main-work">
                                            <i class="bi bi-plus-circle me-1"></i> เพิ่มภาระงานหลัก
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- 2. งานอื่น ๆ ตามที่ได้รับมอบหมาย (๕.๑ และ ๕.๒) -->
                            <tr class="table-secondary fw-bold">
                                <td colspan="<?= $isEditable ? 14 : 13 ?>" class="py-2 px-3 bg-secondary-subtle text-dark text-start">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-folder2-open me-1 text-primary"></i> ๕. งานอื่น ๆ ตามที่ได้รับมอบหมาย</span>
                                        <span class="badge bg-secondary">ค่าน้ำหนักรวม ๒๐% (ข้อ ๕.๑ ๑๕% + ข้อ ๕.๒ ๕%)</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- แถบด้านบน: ๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายฯ -->
                            <tr class="fw-bold" style="background-color: #f0f4ff;">
                                <td colspan="<?= $isEditable ? 14 : 13 ?>" class="py-2 px-3 text-start border-top border-primary-subtle">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary fs-6">
                                            <i class="bi bi-bookmark-check-fill me-1"></i> <strong>๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายของมหาวิทยาลัยและสำนักฯ (ค่าน้ำหนัก ๑๕)</strong>
                                        </span>
                                        <span class="badge bg-primary">ค่าน้ำหนัก ๑๕%</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- 5.1 Policy Content Row -->
                            <tr class="policy-row bg-white">
                                <td colspan="3" class="align-top py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong class="text-dark small"><i class="bi bi-list-check text-primary me-1"></i> มีการดำเนินงานตามรายการนโยบาย (ระบุรายละเอียดหัวข้อที่ดำเนิน):</strong>
                                        <span class="badge bg-light text-dark border">เลือกแล้ว: <span id="policy-count-badge"><?= $policyCount ?></span> / 7 ข้อ</span>
                                    </div>
                                    <div class="policy-checklist-box small">
                                        <?php 
                                        $policyItemId = $policyItem ? $policyItem->id : 0;
                                        foreach ($policy7Options as $pKey => $pLabel): 
                                            $isChecked = in_array($pKey, $policySelected);
                                        ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input auto-save-field secondary-checkbox" type="checkbox" name="item_checkbox[<?= $policyItemId ?>][]" value="<?= $pKey ?>" id="policy_<?= $pKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="cursor: pointer;">
                                                <label class="form-check-label text-dark" for="policy_<?= $pKey ?>" style="cursor: pointer;">
                                                    <strong><?= $pKey ?>.</strong> <?= Html::encode($pLabel) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-2 pt-1 border-top small text-muted">
                                        <i class="bi bi-info-circle me-1"></i> เกณฑ์การประเมิน: 3-5 ข้อ = 5 คะแนน, 2 ข้อ = 3 คะแนน, 1 ข้อ = 1 คะแนน, 0 ข้อ = 0 คะแนน
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary fs-6" id="policy-self-badge">ระดับ <span id="policy-self-score-badge"><?= $policyScore ?></span></span>
                                </td>
                                <td class="text-center align-middle policy-tgt-col policy-tgt-1"><span class="<?= $policyScore == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                <td class="text-center align-middle policy-tgt-col policy-tgt-2"><span class="<?= $policyScore == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                <td class="text-center align-middle policy-tgt-col policy-tgt-3"><span class="<?= $policyScore == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                <td class="text-center align-middle policy-tgt-col policy-tgt-4"><span class="<?= $policyScore == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                <td class="text-center align-middle policy-tgt-col policy-tgt-5"><span class="<?= $policyScore == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                <td class="text-center align-middle fw-bold text-dark" id="policy-score-num"><?= $policyScore ?></td>
                                <td class="text-center align-middle fw-bold">15%</td>
                                <td class="text-center align-middle fw-bold text-primary fs-6" id="policy-weighted-num"><?= number_format($policyWeighted, 2) ?></td>
                                <?php if ($isEditable): ?>
                                    <td></td>
                                <?php endif; ?>
                            </tr>

                            <!-- แถบด้านบน: ๕.๒ มีการจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย... -->
                            <tr class="fw-bold" style="background-color: #f0f4ff;">
                                <td colspan="<?= $isEditable ? 14 : 13 ?>" class="py-2 px-3 text-start border-top border-primary-subtle">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary fs-6">
                                            <i class="bi bi-book-half me-1"></i> <strong>๕.๒ มีการจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย ตำรา หนังสือ งานแปลตำราหรือหนังสือ งานวิเคราะห์หรืองานพัฒนา บทความทางวิชาการ เอกสารกรณีศึกษา ที่เสร็จสมบูรณ์ (ค่าน้ำหนัก ๕)</strong>
                                        </span>
                                        <span class="badge bg-primary">ค่าน้ำหนัก ๕%</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- 5.2 Academic Content Row -->
                            <tr class="acad-row bg-white">
                                <td colspan="3" class="align-top py-3">
                                    <div class="small text-muted mb-2"><strong>มีการดำเนินงาน:</strong> คู่มือปฏิบัติงาน/แผนปฏิบัติราชการ/งานวิจัย/ตำรา/งานสร้างสรรค์/หนังสือ/งานแปลตำราหรือหนังสือ/งานวิเคราะห์ ที่เข้าสู่กระบวนการขอตำแหน่งที่สูงขึ้นของมหาวิทยาลัยฯ</div>
                                    <div class="acad-radio-box small">
                                        <?php 
                                        $acadItemId = $acadItem ? $acadItem->id : 0;
                                        foreach ($acad5Levels as $aKey => $aLabel): 
                                            $isAcadChecked = ($acadVal == $aKey);
                                        ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input auto-save-field acad-radio" type="radio" name="item_radio[<?= $acadItemId ?>]" value="<?= $aKey ?>" id="acad_<?= $aKey ?>" <?= $isAcadChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="cursor: pointer;">
                                                <label class="form-check-label text-dark" for="acad_<?= $aKey ?>" style="cursor: pointer;">
                                                    <span class="badge bg-light text-primary border me-1">ระดับ <?= $aKey ?></span> <?= Html::encode($aLabel) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="alert alert-info py-1 px-2 mt-2 mb-0 small" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle me-1"></i> <strong>หมายเหตุ:</strong> สำหรับบุคลากรระดับชำนาญการพิเศษ จะต้องมีหลักฐานยืนยันการถ่ายทอดผลงานให้ผู้อื่น หรือช่วยเหลือสังคม (พี่เลี้ยง/ที่ปรึกษา) = ๕ คะแนน
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary fs-6" id="acad-self-badge">ระดับ <span id="acad-self-score-badge"><?= $acadVal ?></span></span>
                                </td>
                                <td class="text-center align-middle acad-tgt-col acad-tgt-1"><span class="<?= $acadVal == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                <td class="text-center align-middle acad-tgt-col acad-tgt-2"><span class="<?= $acadVal == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                <td class="text-center align-middle acad-tgt-col acad-tgt-3"><span class="<?= $acadVal == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                <td class="text-center align-middle acad-tgt-col acad-tgt-4"><span class="<?= $acadVal == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                <td class="text-center align-middle acad-tgt-col acad-tgt-5"><span class="<?= $acadVal == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                <td class="text-center align-middle fw-bold text-dark" id="acad-score-num"><?= $acadVal ?></td>
                                <td class="text-center align-middle fw-bold">5%</td>
                                <td class="text-center align-middle fw-bold text-primary fs-6" id="acad-weighted-num"><?= number_format($acadWeighted, 2) ?></td>
                                <?php if ($isEditable): ?>
                                    <td></td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">รวมน้ำหนักภาระงานหลัก (น้ำหนัก ๘๐%):</td>
                                <td colspan="6"></td>
                                <td class="text-end fw-bold">รวม:</td>
                                <td class="text-center fw-bold text-primary"><span id="footer-main-weight">80</span>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="footer-main-weighted-sum">0.00</span></td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">รวมงานอื่น ๆ ตามที่ได้รับมอบหมาย (ข้อ ๕.๑ ๑๕% + ข้อ ๕.๒ ๕% รวม ๒๐%):</td>
                                <td colspan="6"></td>
                                <td class="text-end fw-bold">รวม:</td>
                                <td class="text-center fw-bold text-primary">20%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="footer-sec-weighted-sum">0.00</span></td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                            <tr class="table-warning">
                                <td colspan="3" class="text-end fw-bold text-dark">(๗) ผลรวมส่วนผลสัมฤทธิ์ของงาน (แบบที่ ๒ เต็ม ๑๐๐ คะแนน):</td>
                                <td colspan="6"></td>
                                <td class="text-end fw-bold text-dark">รวมน้ำหนัก:</td>
                                <td class="text-center fw-bold text-dark fs-6"><span id="footer-total-weight">100</span>%</td>
                                <td class="text-center fw-bold text-primary fs-5"><span id="footer-total-perf-sum">0.00</span></td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                            <tr class="table-success">
                                <td colspan="3" class="text-end fw-bold text-success">(๘) สรุปคะแนนผลสัมฤทธิ์ของงาน ถ่วงน้ำหนัก <?= $perfWeightTh ?> ในภาพรวม ((๗) &times; <?= $perfWeightTh ?> / ๑๐๐):</td>
                                <td colspan="7"></td>
                                <td class="text-center fw-bold text-success fs-5"><span id="footer-total-perf-80">0.00</span>%</td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Step 2 Bottom Navigation -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold btn-nav-step shadow-sm" data-target-step="1">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ: แบบสรุปการประเมิน (<?= $docCode ?>)
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold btn-nav-step shadow-sm" data-target-step="3">
                    ถัดไป: แบบประเมินสมรรถนะ (แบบที่ ๓) <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
        </div><!-- End #step-pane-2 -->

        <!-- ========================================================================= -->
        <!-- STEP 3: แบบประเมินขีดความสามารถ/สมรรถนะ (<?= $compWeightTh ?>)                             -->
        <!-- ========================================================================= -->
        <div class="eval-step-pane" id="step-pane-3" style="display: none;">

        <!-- FORM 3: COMPETENCY (<?= $compWeight ?>%) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-award me-1"></i> แบบข้อตกลงการประเมินขีดความสามารถ/สมรรถนะ (แบบที่ ๓)</h5>
                    <small class="text-white-50">น้ำหนักรวม <?= $compWeightTh ?> (สมรรถนะหลัก ๔ ด้าน + สมรรถนะประจำสายงาน ๓ ด้าน)</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    คะแนนสมรรถนะ: <span id="total-comp-score">20.00</span> / <?= $compWeight ?>%
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" id="comp-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 220px;">สมรรถนะ</th>
                                <th style="width: 120px;">(๑)<br>ระดับคาดหวัง</th>
                                <th style="width: 180px;">(๒)<br>ประเมินตนเอง</th>
                                <th style="width: 90px;">(๓)<br>GAP</th>
                                <th style="width: 130px;">(๔)<br>ความสำคัญ</th>
                                <th style="min-width: 200px;">(๕)<br>แผนพัฒนาตนเอง (IDP) / ข้อเสนอแนะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competencies as $idx => $comp): 
                                $cAns = $compAnswers[$comp->id] ?? null;
                                $selfLvl = $cAns ? $cAns->level_value : ($comp->expected_level ?? 3);
                                $expLvl = $comp->expected_level ?? 3;
                                $gapVal = $selfLvl - $expLvl;
                                $importance = $cAns ? $cAns->importance : 'กลาง';
                                $idp = $cAns ? $cAns->idp_plan : '';
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= Html::encode($comp->name_th) ?></strong>
                                        <?php if ($comp->name_en): ?>
                                            <small class="text-primary d-block">(<?= Html::encode($comp->name_en) ?>)</small>
                                        <?php endif; ?>
                                        <?php if ($comp->definition): ?>
                                            <small class="text-muted d-block mt-1"><?= Html::encode($comp->definition) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6">ระดับ <?= $expLvl ?></span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field comp-level-select fw-bold border-primary" 
                                                name="comp[<?= $comp->id ?>][level]" 
                                                data-comp-id="<?= $comp->id ?>" 
                                                data-expected="<?= $expLvl ?>" 
                                                <?= !$isEditable ? 'disabled' : '' ?>>
                                            <option value="5" <?= $selfLvl == 5 ? 'selected' : '' ?>>ระดับ ๕ (เชี่ยวชาญ)</option>
                                            <option value="4" <?= $selfLvl == 4 ? 'selected' : '' ?>>ระดับ ๔ (ชำนาญ)</option>
                                            <option value="3" <?= $selfLvl == 3 ? 'selected' : '' ?>>ระดับ ๓ (ความสามารถ)</option>
                                            <option value="2" <?= $selfLvl == 2 ? 'selected' : '' ?>>ระดับ ๒ (ประยุกต์ใช้)</option>
                                            <option value="1" <?= $selfLvl == 1 ? 'selected' : '' ?>>ระดับ ๑ (พื้นฐาน)</option>
                                        </select>
                                    </td>
                                    <td class="text-center fw-bold comp-gap-display">
                                        <span class="badge <?= $gapVal >= 0 ? 'bg-success' : ($gapVal == -1 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                            <?= $gapVal > 0 ? '+' . $gapVal : $gapVal ?>
                                        </span>
                                        <input type="hidden" class="comp-gap-input" name="comp[<?= $comp->id ?>][gap]" value="<?= $gapVal > 0 ? '+' . $gapVal : $gapVal ?>">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field comp-importance-select" 
                                                name="comp[<?= $comp->id ?>][importance]" 
                                                <?= !$isEditable ? 'disabled' : '' ?>>
                                            <option value="สูง" <?= $importance === 'สูง' ? 'selected' : '' ?>>สูง</option>
                                            <option value="กลาง" <?= $importance === 'กลาง' || empty($importance) ? 'selected' : '' ?>>ปานกลาง</option>
                                            <option value="ต่ำ" <?= $importance === 'ต่ำ' ? 'selected' : '' ?>>ต่ำ</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm auto-save-field comp-idp-input" 
                                               data-comp-id="<?= $comp->id ?>"
                                               name="comp[<?= $comp->id ?>][idp]" 
                                               placeholder="ระบุแผนพัฒนาตนเอง (IDP)..." 
                                               value="<?= Html::encode($idp) ?>" 
                                               <?= !$isEditable ? 'readonly' : '' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TABLE 2: สรุปเกณฑ์การประเมินและการคิดคะแนน GAP (ตารางที่ ๒ จากเอกสารทางการ) -->
                <div class="p-3 bg-light border-top">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-calculator me-1"></i> ตารางสรุปหลักเกณฑ์การประเมินและการคิดคะแนนสมรรถนะ (GAP Calculation)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">หลักเกณฑ์การประเมิน GAP</th>
                                    <th style="width: 140px;">จำนวนสมรรถนะ</th>
                                    <th style="width: 50px;">X</th>
                                    <th style="width: 100px;">ตัวคูณคะแนน</th>
                                    <th style="width: 130px;">คะแนนที่ได้</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-start">จำนวนสมรรถนะที่มีระดับที่แสดงออก<strong>สูงกว่าหรือเท่ากับ</strong>ระดับที่คาดหวัง</td>
                                    <td class="fw-bold text-primary"><span id="gap-count-ge">7</span></td>
                                    <td>X</td>
                                    <td>๓ คะแนน</td>
                                    <td class="fw-bold text-success"><span id="gap-pts-ge">21</span></td>
                                </tr>
                                <tr>
                                    <td class="text-start">จำนวนสมรรถนะที่มีระดับที่แสดงออก<strong>ต่ำกว่า ๑ ระดับ</strong></td>
                                    <td class="fw-bold text-warning"><span id="gap-count-m1">0</span></td>
                                    <td>X</td>
                                    <td>๒ คะแนน</td>
                                    <td class="fw-bold text-warning"><span id="gap-pts-m1">0</span></td>
                                </tr>
                                <tr>
                                    <td class="text-start">จำนวนสมรรถนะที่มีระดับที่แสดงออก<strong>ต่ำกว่า ๒ ระดับ</strong></td>
                                    <td class="fw-bold text-danger"><span id="gap-count-m2">0</span></td>
                                    <td>X</td>
                                    <td>๑ คะแนน</td>
                                    <td class="fw-bold text-danger"><span id="gap-pts-m2">0</span></td>
                                </tr>
                                <tr>
                                    <td class="text-start">จำนวนสมรรถนะที่มีระดับที่แสดงออก<strong>ต่ำกว่า ๓ ระดับขึ้นไป</strong></td>
                                    <td class="fw-bold text-danger"><span id="gap-count-m3">0</span></td>
                                    <td>X</td>
                                    <td>๐ คะแนน</td>
                                    <td class="fw-bold text-danger"><span id="gap-pts-m3">0</span></td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td colspan="4" class="text-end">(๔) ผลรวมคะแนน:</td>
                                    <td class="text-primary fs-6"><span id="gap-total-sum">21</span></td>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <td colspan="4" class="text-end">(๕) สรุปคะแนนส่วนขีดความสามารถ/สมรรถนะ (ผลรวมคะแนน / (<?= count($competencies) ?> X ๓)):</td>
                                    <td class="text-primary fs-6"><span id="gap-ratio-score">100.00</span>%</td>
                                </tr>
                                <tr class="table-success fw-bold">
                                    <td colspan="4" class="text-end">* สรุปผลคะแนนรวมสมรรถนะ (ข้อ (๕) X <?= $compWeightTh ?>):</td>
                                    <td class="text-success fs-5"><span id="gap-weighted-final">20.00</span> / <?= $compWeight ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Step 3 Bottom Navigation & Actions -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold btn-nav-step shadow-sm" data-target-step="2">
                <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ: แบบผลสัมฤทธิ์ของงาน (แบบ ป.ผ.)
            </button>
            <div class="d-flex gap-2">
                <?php if ($isEditable): ?>
                    <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm" onclick="$('#btn-manual-save').click();">
                        <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                    </button>
                    <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm" onclick="$('#btn-submit-self-eval').click();">
                        <i class="bi bi-send-fill me-1"></i> ส่งแบบประเมินตนเอง
                    </button>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- End #step-pane-3 -->

    <!-- ========================================================================= -->
    <!-- TEMPLATE TYPE: พนักงานราชการ (GOVT)                                        -->
    <!-- ========================================================================= -->
    <?php elseif ($personnelType === 'GOVT'): 
        $secGovtMain = null;
        $secGovtSec = null;
        $secGovtBeh = null;
        foreach ($sections as $s) {
            if ($s->section_code === 'GOVT_MAIN_WORK') $secGovtMain = $s;
            elseif ($s->section_code === 'GOVT_SECONDARY_WORK') $secGovtSec = $s;
            elseif ($s->section_code === 'GOVT_BEHAVIOR') $secGovtBeh = $s;
        }

        $govtMainItem = ($secGovtMain && !empty($secGovtMain->items)) ? $secGovtMain->items[0] : null;
        $govtSecItem = ($secGovtSec && !empty($secGovtSec->items)) ? $secGovtSec->items[0] : null;

        $govtMainAns = $govtMainItem ? ($answers[$govtMainItem->id] ?? null) : null;
        $govtMainRows = ($govtMainAns && !empty($govtMainAns->json_value)) 
            ? (is_string($govtMainAns->json_value) ? json_decode($govtMainAns->json_value, true) : $govtMainAns->json_value) 
            : [
                ['title' => 'ปฏิบัติงานด้านธุรการ สารบรรณ และการจัดทำเอกสารราชการ', 'kpi_volume' => 5, 'kpi_quality' => 5, 'kpi_timeliness' => 5, 'kpi_resource' => 5, 'weight' => 40],
                ['title' => 'ประสานงานการจัดประชุม สัมมนา และกิจกรรมของสำนักวิทยบริการฯ', 'kpi_volume' => 4, 'kpi_quality' => 5, 'kpi_timeliness' => 4, 'kpi_resource' => 4, 'weight' => 40],
            ];

        $govtSecAns = $govtSecItem ? ($answers[$govtSecItem->id] ?? null) : null;
        $govtSecRaw = ($govtSecAns && !empty($govtSecAns->json_value)) 
            ? (is_string($govtSecAns->json_value) ? json_decode($govtSecAns->json_value, true) : $govtSecAns->json_value) 
            : ['1', '3'];

        $govtSecSelected = [];
        $govtSecDetails = [];
        if (isset($govtSecRaw['selected']) && is_array($govtSecRaw['selected'])) {
            $govtSecSelected = $govtSecRaw['selected'];
            $govtSecDetails = $govtSecRaw['details'] ?? [];
        } elseif (is_array($govtSecRaw)) {
            $govtSecSelected = $govtSecRaw;
            $govtSecDetails = [];
        }

        $govt10Options = [
            '1' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย (ระบุชื่อกิจกรรม/โครงการ พร้อมแนบหลักฐาน)',
            '2' => 'ดำเนินงานผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ของสำนักฯ (เช่น ผลงานที่แสดงให้เห็นชัดถึงการขับเคลื่อนการดำเนินแผนของสำนักฯ / มหาลัยฯ พร้อมแนบหลักฐาน)',
            '3' => 'เป็นคณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / งาน EdPEx (เช่น คำสั่งที่/ หนังสือมอบหมายหน้าที่/ หลักฐานที่เป็นลายลักษณ์อักษร พร้อมแนบหลักฐาน)',
            '4' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน /การทำ LEAN Management /การทำ Kaizen (ระบุ พร้อมแนบหลักฐาน)',
            '5' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์ (ระบุ พร้อมแนบหลักฐาน)',
            '6' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก ที่เกี่ยวข้องกับตำแหน่งหน้าที่ (ภายในปีงบประมาณ หรือ ย้อนหลัง 1 ปี **1 Cer ใช้ได้ 2 รอบประเมิน พร้อมแนบหลักฐาน)',
            '7' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (เรียน/เข้าอบรม /ผ่านการทดสอบ) (ระบุ พร้อมแนบหลักฐาน)',
            '8' => 'งานวิจัย / งานส่งเสริมความเป็นนานาชาติ /งานบริการวิชาการ / งานทำนุบำรุงศิลปวัฒนธรรม อย่างใดอย่างหนึ่ง (ระบุชื่อ พร้อมแนบหลักฐาน)',
            '9' => 'การหารายได้เข้าสำนักฯ (ระบุ พร้อมแนบหลักฐาน)',
            '10' => 'อื่น ๆ (ระบุ พร้อมแนบหลักฐาน)',
        ];

        $govtSecItemId = $govtSecItem ? $govtSecItem->id : 0;
        $govtSecCount = count($govtSecSelected);
        $govtSecPts = \common\services\EvaluationCalculatorService::gradeGovtSecondaryCount($govtSecCount);
        $govtSecScore = ($govtSecPts / 5.0) * 20.0;
    ?>

        <!-- UNIFIED GOVT SECTION 2: การประเมินผลสัมฤทธิ์ของงาน (น้ำหนักรวม ๑๐๐) -->
        <!-- UNIFIED GOVT SECTION 2: การประเมินผลสัมฤทธิ์ของงาน (น้ำหนักรวม ๑๐๐%) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">ส่วนที่ ๒ การประเมินผลสัมฤทธิ์ของงาน (น้ำหนักรวม ๑๐๐%)</h5>
                    <small class="text-white-50">ภาระงานหลัก (ค่าน้ำหนัก ๘๐%) และ ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐%)</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    น้ำหนักรวม: <span id="govt-total-weight-badge">100</span>% / 100%
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="govt-perf-unified-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th rowspan="2" style="width: 45px;">#</th>
                                <th rowspan="2" style="min-width: 320px;">หน้าที่ / ภารกิจ</th>
                                <th rowspan="2" style="width: 105px;">บุคลากร<br>ประเมินตนเอง</th>
                                <th colspan="4" class="text-center">ปัจจัยการประเมินผลสัมฤทธิ์ของงาน (ตัวชี้วัด : ผลงานจริง) (๘๐ คะแนน)</th>
                                <th colspan="5" class="text-center">ระดับค่าเป้าหมาย (ก)</th>
                                <th rowspan="2" style="width: 90px;">น้ำหนัก(%)<br>(ข)</th>
                                <th rowspan="2" style="width: 100px;">คะแนน (ค)<br><small>(กxข)/๑๐๐</small></th>
                                <?php if ($isEditable): ?>
                                    <th rowspan="2" style="width: 45px;">ลบ</th>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <th style="width: 95px;"><small>ปริมาณ<br>(25)</small></th>
                                <th style="width: 95px;"><small>คุณภาพ<br>(25)</small></th>
                                <th style="width: 95px;"><small>ตรงเวลา<br>(15)</small></th>
                                <th style="width: 95px;"><small>คุ้มค่า<br>(15)</small></th>
                                <th style="width: 38px;" class="small">1<br><small>&lt;50</small></th>
                                <th style="width: 38px;" class="small">2<br><small>50-59</small></th>
                                <th style="width: 38px;" class="small">3<br><small>60-69</small></th>
                                <th style="width: 38px;" class="small">4<br><small>70-79</small></th>
                                <th style="width: 38px;" class="small">5<br><small>&ge;80</small></th>
                            </tr>
                        </thead>
                        <tbody id="govt-perf-unified-tbody">
                            <!-- Main Work Header -->
                            <tr class="table-light fw-bold">
                                <td colspan="<?= $isEditable ? 15 : 14 ?>" class="py-2 bg-light">
                                    <i class="bi bi-briefcase me-1 text-primary"></i> ภาระงานหลัก (ค่าน้ำหนัก ๘๐) รายละเอียดภาระงานที่ปฏิบัติ (เอกสารแนบ)
                                </td>
                            </tr>
                            <?php foreach ($govtMainRows as $gIdx => $gRow): 
                                $k1 = floatval($gRow['kpi_volume'] ?? 5);
                                $k2 = floatval($gRow['kpi_quality'] ?? 5);
                                $k3 = floatval($gRow['kpi_timeliness'] ?? 5);
                                $k4 = floatval($gRow['kpi_resource'] ?? 5);
                                $gw = floatval($gRow['weight'] ?? 40);
                                $itemScore = (($k1 * 25) + ($k2 * 25) + ($k3 * 15) + ($k4 * 15)) / 80.0;
                                $weighted = ($itemScore / 5.0) * $gw;
                                $roundedScore = (int)round($itemScore);
                            ?>
                                <tr class="govt-main-work-row">
                                    <td class="text-center fw-bold row-index"><?= $gIdx + 1 ?></td>
                                    <td>
                                        <textarea class="form-control form-control-sm auto-save-field govt-work-title" name="govt_main[<?= $gIdx ?>][title]" rows="2" placeholder="ระบุรายละเอียดภาระงานที่ปฏิบัติ..." required <?= !$isEditable ? 'readonly' : '' ?>><?= Html::encode($gRow['title'] ?? '') ?></textarea>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 govt-row-self-score">ระดับ <?= number_format($itemScore, 1) ?></span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-volume" name="govt_main[<?= $gIdx ?>][kpi_volume]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k1 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-quality" name="govt_main[<?= $gIdx ?>][kpi_quality]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k2 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-timeliness" name="govt_main[<?= $gIdx ?>][kpi_timeliness]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k3 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-resource" name="govt_main[<?= $gIdx ?>][kpi_resource]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k4 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center govt-tgt-col govt-tgt-1"><span class="<?= $roundedScore == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                    <td class="text-center govt-tgt-col govt-tgt-2"><span class="<?= $roundedScore == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                    <td class="text-center govt-tgt-col govt-tgt-3"><span class="<?= $roundedScore == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                    <td class="text-center govt-tgt-col govt-tgt-4"><span class="<?= $roundedScore == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                    <td class="text-center govt-tgt-col govt-tgt-5"><span class="<?= $roundedScore == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="1" min="1" max="80" class="form-control form-control-sm text-center auto-save-field govt-work-weight fw-bold" name="govt_main[<?= $gIdx ?>][weight]" value="<?= $gw ?>" <?= !$isEditable ? 'readonly' : '' ?>>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold text-primary govt-row-weighted">
                                        <?= number_format($weighted, 2) ?>
                                    </td>
                                    <?php if ($isEditable): ?>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-govt-row"><i class="bi bi-trash"></i></button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($isEditable): ?>
                                <tr id="govt-add-row-tr">
                                    <td colspan="<?= $isEditable ? 15 : 14 ?>" class="bg-light text-end py-2">
                                        <button type="button" class="btn btn-xs btn-outline-primary shadow-sm" id="btn-add-govt-row">
                                            <i class="bi bi-plus-circle me-1"></i> เพิ่มภาระงานหลัก
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- Secondary Work Header -->
                            <tr class="table-light fw-bold">
                                <td colspan="<?= $isEditable ? 15 : 14 ?>" class="py-2 bg-light">
                                    <i class="bi bi-check2-square me-1 text-primary"></i> ๖. ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐)
                                </td>
                            </tr>
                            <?php 
                            $govtSecKpiVol = $govtSecRaw['kpi_volume'] ?? $govtSecPts;
                            $govtSecKpiQua = $govtSecRaw['kpi_quality'] ?? $govtSecPts;
                            $govtSecKpiTime = $govtSecRaw['kpi_timeliness'] ?? $govtSecPts;
                            $govtSecKpiRes = $govtSecRaw['kpi_resource'] ?? $govtSecPts;
                            $secCalcScore = (($govtSecKpiVol * 25) + ($govtSecKpiQua * 25) + ($govtSecKpiTime * 15) + ($govtSecKpiRes * 15)) / 80.0;
                            $secCalcWeighted = ($secCalcScore / 5.0) * 20.0;
                            $secRoundedScore = (int)round($secCalcScore);
                            ?>
                            <!-- Secondary Work Row 6 (Clean, Compact, Evaluated like Main Work) -->
                            <tr class="govt-sec-row bg-white">
                                <td class="text-center fw-bold align-middle">6</td>
                                <td>
                                    <div class="fw-bold text-dark">๖. ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐)</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-check-circle text-success me-1"></i>เลือกปฏิบัติ <span id="govt-sec-badge-count"><?= $govtSecCount ?></span> / 10 ข้อ
                                        </span>
                                        <a href="#govt-sec-evidence-card" class="btn btn-xs btn-outline-primary shadow-sm">
                                            <i class="bi bi-folder-check me-1"></i> จัดการรายละเอียดและแนบหลักฐาน
                                        </a>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary fs-6" id="govt-sec-self-badge">ระดับ <?= number_format($secCalcScore, 1) ?></span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm auto-save-field govt-sec-kpi" name="govt_sec_kpi[volume]" id="govt-sec-kpi-vol" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $govtSecKpiVol == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm auto-save-field govt-sec-kpi" name="govt_sec_kpi[quality]" id="govt-sec-kpi-qua" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $govtSecKpiQua == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm auto-save-field govt-sec-kpi" name="govt_sec_kpi[timeliness]" id="govt-sec-kpi-time" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $govtSecKpiTime == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm auto-save-field govt-sec-kpi" name="govt_sec_kpi[resource]" id="govt-sec-kpi-res" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $govtSecKpiRes == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="text-center align-middle govt-sec-tgt-col govt-sec-tgt-1"><span class="<?= $secRoundedScore == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                <td class="text-center align-middle govt-sec-tgt-col govt-sec-tgt-2"><span class="<?= $secRoundedScore == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                <td class="text-center align-middle govt-sec-tgt-col govt-sec-tgt-3"><span class="<?= $secRoundedScore == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                <td class="text-center align-middle govt-sec-tgt-col govt-sec-tgt-4"><span class="<?= $secRoundedScore == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                <td class="text-center align-middle govt-sec-tgt-col govt-sec-tgt-5"><span class="<?= $secRoundedScore == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                <td class="text-center fw-bold fs-6 align-middle">20%</td>
                                <td class="text-center fw-bold text-primary fs-6 align-middle" id="govt-sec-score-display">
                                    <?= number_format($secCalcWeighted, 2) ?>
                                </td>
                                <?php if ($isEditable): ?>
                                    <td class="align-middle text-center text-muted">-</td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="11" class="text-end fw-bold">รวมคะแนนภาระงานหลัก (น้ำหนัก ๘๐%):</td>
                                <td class="text-center fw-bold"><span id="govt-footer-total-weight">80</span>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="govt-main-weighted-sum">0.00</span></td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                            <tr>
                                <td colspan="11" class="text-end fw-bold">รวมคะแนนภาระงานรอง (น้ำหนัก ๒๐%):</td>
                                <td class="text-center fw-bold">20%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="govt-sec-score-sum"><?= number_format($secCalcWeighted, 2) ?></span></td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                            <tr class="table-warning">
                                <td colspan="11" class="text-end fw-bold">รวมคะแนนด้านผลสัมฤทธิ์ของงานทั้งหมด (ส่วนที่ ๒ เต็ม ๑๐๐%):</td>
                                <td class="text-center fw-bold text-dark">100%</td>
                                <td class="text-center fw-bold text-success fs-5"><span id="govt-perf-total-sum">0.00</span></td>
                                <?php if ($isEditable): ?><td></td><?php endif; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- SEPARATE EVIDENCE CARD FOR GOVT SECONDARY (10 ITEMS) - CLEAN, SPACIOUS & BEAUTIFUL -->
        <div class="card card-rmutt shadow-sm mb-4" id="govt-sec-evidence-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-folder-check me-2"></i>รายละเอียดและหลักฐานแนบภาระงานรองหรืองานที่ได้รับมอบหมาย (ข้อ ๖)</h5>
                    <small class="text-muted">ค่าน้ำหนัก ๒๐% (เลือกตามที่ปฏิบัติจริง พร้อมระบุรายละเอียดและแนบหลักฐาน)</small>
                </div>
                <span class="badge bg-primary fs-6 px-3 py-2" id="govt-sec-count-badge-card">
                    เลือกปฏิบัติแล้ว <span id="govt-sec-count-num"><?= $govtSecCount ?></span> / 10 ข้อ
                </span>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-light border-start border-primary border-4 p-3 mb-4 rounded shadow-sm">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i> เกณฑ์ระดับค่าเป้าหมายตามประกาศมหาวิทยาลัย:</div>
                    <div class="small text-muted">
                        ดำเนินการ <strong>6 - 10 ข้อ</strong> = ระดับ ๕ (๒๐ คะแนน) | 
                        ดำเนินการ <strong>5 ข้อ</strong> = ระดับ ๔ (๑๖ คะแนน) | 
                        ดำเนินการ <strong>3 - 4 ข้อ</strong> = ระดับ ๓ (๑๒ คะแนน) | 
                        ดำเนินการ <strong>2 ข้อ</strong> = ระดับ ๒ (๘ คะแนน) | 
                        ดำเนินการ <strong>1 ข้อ</strong> = ระดับ ๑ (๔ คะแนน)
                    </div>
                </div>

                <div class="govt-sec-checklist">
                    <?php foreach ($govt10Options as $optKey => $optLabel): 
                        $isChecked = in_array($optKey, $govtSecSelected);
                        $rawOpt = $govtSecDetails[$optKey] ?? null;
                        $optEntries = [];
                        if (is_array($rawOpt)) {
                            if (isset($rawOpt[0]) && is_array($rawOpt[0])) {
                                $optEntries = $rawOpt;
                            } elseif (isset($rawOpt['detail']) || isset($rawOpt['file_id'])) {
                                $optEntries = [$rawOpt];
                            }
                        } elseif (is_string($rawOpt) && trim($rawOpt) !== '') {
                            $optEntries = [['detail' => $rawOpt, 'file_id' => null, 'file_name' => '', 'file_url' => '']];
                        }
                        if (empty($optEntries)) {
                            $optEntries = [['detail' => '', 'file_id' => null, 'file_name' => '', 'file_url' => '']];
                        }
                    ?>
                        <div class="card mb-3 border rounded shadow-sm govt-sec-option-card <?= $isChecked ? 'border-primary' : '' ?>">
                            <div class="card-header bg-light py-2 d-flex align-items-center gap-2">
                                <input class="form-check-input auto-save-field govt-sec-checkbox" type="checkbox" name="govt_sec_check[]" value="<?= $optKey ?>" id="govt_sec_<?= $optKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark flex-grow-1 mb-0" for="govt_sec_<?= $optKey ?>" style="cursor: pointer;">
                                    (<?= $optKey ?>) <?= Html::encode($optLabel) ?>
                                </label>
                            </div>
                            <div class="card-body p-3 govt-sec-entries-body <?= !$isChecked ? 'd-none' : '' ?>">
                                <div class="govt-sec-entries-wrapper" data-opt-key="<?= $optKey ?>">
                                    <?php foreach ($optEntries as $eIdx => $entry): 
                                        $hasFile = !empty($entry['file_id']) || !empty($entry['file_url']);
                                        $fileName = $entry['file_name'] ?? '';
                                        $fileUrl = $entry['file_url'] ?? '';
                                        $fileId = $entry['file_id'] ?? '';
                                        $isImage = (bool)preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fileName);
                                        $isPdf = (bool)preg_match('/\.pdf$/i', $fileName);
                                    ?>
                                        <div class="govt-sec-entry-item mb-2 p-2 border rounded bg-white shadow-sm" data-row-idx="<?= $eIdx ?>">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-lg-6 col-md-12">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted">
                                                            <i class="bi bi-pencil-square me-1"></i> <span class="entry-order-label">#<?= $eIdx + 1 ?></span>
                                                        </span>
                                                        <input type="text" class="form-control form-control-sm govt-sec-detail-input" placeholder="ระบุรายละเอียดภาระงาน เช่น ชื่อกิจกรรม/โครงการ, ผลงาน..." value="<?= Html::encode($entry['detail'] ?? '') ?>" <?= !$isEditable ? 'readonly' : '' ?>>
                                                    </div>
                                                </div>
                                                <div class="col-lg-5 col-md-10">
                                                    <div class="govt-sec-file-box">
                                                        <div class="govt-sec-file-attached d-flex align-items-center justify-content-between p-1 px-2 border rounded bg-light <?= !$hasFile ? 'd-none' : '' ?>">
                                                            <div class="text-truncate me-2 small">
                                                                <i class="bi bi-file-earmark-check text-success me-1"></i>
                                                                <span class="govt-sec-file-name fw-bold text-dark" title="<?= Html::encode($fileName) ?>"><?= Html::encode($fileName ?: 'เอกสารหลักฐาน') ?></span>
                                                            </div>
                                                            <div class="btn-group btn-group-sm flex-shrink-0">
                                                                <button type="button" class="btn btn-xs btn-outline-info govt-sec-preview-btn" 
                                                                        data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                                                        onclick="previewEvidenceFile(this)"
                                                                        data-url="<?= Html::encode($fileUrl) ?>" 
                                                                        data-name="<?= Html::encode($fileName) ?>"
                                                                        data-is-image="<?= $isImage ? '1' : '0' ?>"
                                                                        data-is-pdf="<?= $isPdf ? '1' : '0' ?>"
                                                                        title="พรีวิวหลักฐาน">
                                                                    <i class="bi bi-eye"></i> พรีวิว
                                                                </button>
                                                                <a href="<?= Html::encode($fileUrl ?: '#') ?>" target="_blank" class="btn btn-xs btn-outline-secondary govt-sec-download-btn" title="ดาวน์โหลด">
                                                                    <i class="bi bi-download"></i>
                                                                </a>
                                                                <?php if ($isEditable): ?>
                                                                    <button type="button" class="btn btn-xs btn-outline-danger govt-sec-remove-file-btn" title="ลบไฟล์แนบนี้">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="govt-sec-file-uploader <?= $hasFile ? 'd-none' : '' ?>">
                                                            <?php if ($isEditable): ?>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="file" class="form-control form-control-sm govt-sec-file-input" accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.xlsx">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-govt-upload-file">
                                                                        <i class="bi bi-upload me-1"></i> แนบไฟล์
                                                                    </button>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted small fst-italic">ไม่มีหลักฐานแนบ</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <input type="hidden" class="govt-sec-file-id" value="<?= Html::encode($fileId) ?>">
                                                        <input type="hidden" class="govt-sec-file-name-val" value="<?= Html::encode($fileName) ?>">
                                                        <input type="hidden" class="govt-sec-file-url-val" value="<?= Html::encode($fileUrl) ?>">
                                                    </div>
                                                </div>
                                                <div class="col-lg-1 col-md-2 text-end">
                                                    <?php if ($isEditable): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-govt-entry" title="ลบรายการนี้" style="<?= count($optEntries) <= 1 ? 'display:none;' : '' ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($isEditable): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-xs btn-outline-primary shadow-sm btn-add-govt-entry" data-opt-key="<?= $optKey ?>">
                                            <i class="bi bi-plus-circle me-1"></i> เพิ่มรายละเอียดและหลักฐาน (มีมากกว่า ๑)
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 3. GOVT BEHAVIOR COMPETENCIES (20%) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0 text-white">ส่วนที่ ๓ การประเมินพฤติกรรมการปฏิบัติงาน (ค่าน้ำหนัก <?= $compWeightTh ?>)</h5>
                <small class="text-white-50">ประเมินสมรรถนะ ๕ ด้าน ระดับ ๑-๕ (๑: ต่ำกว่ากำหนดมาก, ๒: ต่ำกว่ากำหนด, ๓: ตามกำหนด, ๔: เกินกว่าที่กำหนด, ๕: เกินกว่าที่กำหนดมาก)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="min-width: 200px;">สมรรถนะ / พฤติกรรมที่ประเมิน</th>
                                <th>คำจำกัดความ / รายละเอียดพฤติกรรม</th>
                                <th style="width: 140px;">ระดับที่คาดหวัง</th>
                                <th style="width: 220px;">ระดับที่ประเมินตนเอง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competencies as $cIdx => $comp): 
                                $cAns = $compAnswers[$comp->id] ?? null;
                                $selfLvl = $cAns ? $cAns->level_value : ($comp->expected_level ?? 3);
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $cIdx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= Html::encode($comp->name_th) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block"><?= Html::encode($comp->definition) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary fs-6">ระดับ <?= $comp->expected_level ?? 3 ?> (ตามกำหนด)</span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm auto-save-field govt-beh-select fw-bold border-primary" name="comp[<?= $comp->id ?>][level]" data-comp-id="<?= $comp->id ?>" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <option value="5" <?= $selfLvl == 5 ? 'selected' : '' ?>>ระดับ ๕ (เกินกว่าที่กำหนดมาก)</option>
                                            <option value="4" <?= $selfLvl == 4 ? 'selected' : '' ?>>ระดับ ๔ (เกินกว่าที่กำหนด)</option>
                                            <option value="3" <?= $selfLvl == 3 ? 'selected' : '' ?>>ระดับ ๓ (ตามกำหนด)</option>
                                            <option value="2" <?= $selfLvl == 2 ? 'selected' : '' ?>>ระดับ ๒ (ต่ำกว่ากำหนด)</option>
                                            <option value="1" <?= $selfLvl == 1 ? 'selected' : '' ?>>ระดับ ๑ (ต่ำกว่ากำหนดมาก)</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">รวมคะแนนพฤติกรรมการปฏิบัติงาน (เต็ม ๑๐๐%):</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="govt-beh-percent-sum">0.00</span>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. GOVT EVALUATION SUMMARY (ส่วนที่ ๔ การสรุปผลการประเมิน) -->
        <div class="card card-rmutt shadow-sm mb-4 border-success">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-calculator me-2"></i>ส่วนที่ ๔ การสรุปผลการประเมิน</h5>
                    <small class="text-white-50">สรุปคะแนนตามแบบฟอร์ม: ผลสัมฤทธิ์ของงาน (น้ำหนัก <?= $perfWeightTh ?>) + พฤติกรรมการปฏิบัติงาน (น้ำหนัก <?= $compWeightTh ?>) รวม ๑๐๐%</small>
                </div>
                <div>
                    <span class="badge bg-light text-dark fs-6">ระดับผลการประเมิน: <span id="govt-summary-level" class="text-success fw-bold">ดีเด่น</span></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>องค์ประกอบการประเมิน</th>
                                <th style="width: 200px;">คะแนนที่ได้ (ก)<br><small class="text-muted">(เต็ม ๑๐๐)</small></th>
                                <th style="width: 150px;">น้ำหนัก (ข)</th>
                                <th style="width: 220px;">รวมคะแนน (ก x ข) / ๑๐๐</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>๑. ผลการประเมินด้านผลสัมฤทธิ์ของงาน</strong>
                                    <div class="small text-muted ps-3">
                                        - ภาระงานหลัก (น้ำหนัก ๘๐%): <span id="govt-summary-main-score">0.00</span> คะแนน<br>
                                        - ภาระงานรอง (น้ำหนัก ๒๐%): <span id="govt-summary-sec-score">0.00</span> คะแนน
                                    </div>
                                </td>
                                <td class="text-center fw-bold fs-6"><span id="govt-summary-perf-score">0.00</span> / 100</td>
                                <td class="text-center fw-bold"><?= $perfWeight ?>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="govt-summary-perf-weighted">0.00</span> / <?= $perfWeight ?>%</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>๒. ผลการประเมินด้านพฤติกรรมการปฏิบัติงาน</strong>
                                    <div class="small text-muted ps-3">
                                        - สมรรถนะ ๕ ด้าน (คะแนนเฉลี่ยระดับ ๑ - ๕)
                                    </div>
                                </td>
                                <td class="text-center fw-bold fs-6"><span id="govt-summary-beh-score">0.00</span> / 100</td>
                                <td class="text-center fw-bold"><?= $compWeight ?>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="govt-summary-beh-weighted">0.00</span> / <?= $compWeight ?>%</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-success table-hover">
                            <tr class="fw-bold fs-6">
                                <td class="text-end">รวมคะแนนสุทธิทั้งสิ้น:</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center">100%</td>
                                <td class="text-center text-success fs-5"><span id="govt-summary-total">0.00</span>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-2">
                <div class="row align-items-center text-muted small">
                    <div class="col-md-8">
                        <strong>เกณฑ์ระดับผลการประเมิน:</strong> ดีเด่น (95 - 100%) | ดีมาก (85 - 94%) | ดี (75 - 84%) | พอใช้ (65 - 74%) | ต้องปรับปรุง (0 - 64%)
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="text-dark fw-bold">สรุปผลการประเมินตนเอง</span>
                    </div>
                </div>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- TEMPLATE TYPE: พนักงานพิเศษเงินรายได้ (SPECIAL)                            -->
    <!-- ========================================================================= -->
    <?php elseif ($personnelType === 'SPECIAL'): 
        $secSpec1 = null;
        $secSpec2 = null;
        foreach ($sections as $s) {
            if (in_array($s->section_code, ['SPEC_PERFORMANCE', 'SPEC_SECTION_1'])) $secSpec1 = $s;
            elseif (in_array($s->section_code, ['SPEC_CHARACTERISTICS', 'SPEC_SECTION_2'])) $secSpec2 = $s;
        }

        $specDirectItems = [];
        $spec16Item = null;
        if ($secSpec1) {
            foreach ($secSpec1->items as $it) {
                if ($it->input_type === 'checkbox_list' || $it->item_code === 'SPEC_1_6_SECONDARY') {
                    $spec16Item = $it;
                } else {
                    $specDirectItems[] = $it;
                }
            }
        }

        $spec16Ans = $spec16Item ? ($answers[$spec16Item->id] ?? null) : null;
        $spec16Raw = ($spec16Ans && !empty($spec16Ans->json_value)) 
            ? (is_string($spec16Ans->json_value) ? json_decode($spec16Ans->json_value, true) : $spec16Ans->json_value) 
            : ['1', '2', '3', '4', '5', '6'];

        $spec16Selected = [];
        $spec16Details = [];
        if (isset($spec16Raw['selected']) && is_array($spec16Raw['selected'])) {
            $spec16Selected = $spec16Raw['selected'];
            $spec16Details = $spec16Raw['details'] ?? [];
        } elseif (is_array($spec16Raw)) {
            $spec16Selected = $spec16Raw;
            $spec16Details = [];
        }

        $spec10Options = [
            '1' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย (ระบุชื่อกิจกรรม/โครงการ พร้อมแนบหลักฐาน)',
            '2' => 'ดำเนินงานผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ของสำนักฯ (เช่น ผลงานที่แสดงให้เห็นชัดถึงการขับเคลื่อนการดำเนินแผนของสำนักฯ / มหาลัยฯ พร้อมแนบหลักฐาน)',
            '3' => 'เป็นคณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / งาน EdPEx (เช่น คำสั่งที่/ หนังสือมอบหมายหน้าที่/ หลักฐานที่เป็นลายลักษณ์อักษร พร้อมแนบหลักฐาน)',
            '4' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน /การทำ LEAN Management /การทำ Kaizen (ระบุ พร้อมแนบหลักฐาน)',
            '5' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์ (ระบุ พร้อมแนบหลักฐาน)',
            '6' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก ที่เกี่ยวข้องกับตำแหน่งหน้าที่ (ภายในปีงบประมาณ หรือ ย้อนหลัง 1 ปี **1 Cer ใช้ได้ 2 รอบประเมิน พร้อมแนบหลักฐาน)',
            '7' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (เรียน/เข้าอบรม /ผ่านการทดสอบ) (ระบุ พร้อมแนบหลักฐาน)',
            '8' => 'งานวิจัย / งานส่งเสริมความเป็นนานาชาติ /งานบริการวิชาการ / งานทำนุบำรุงศิลปวัฒนธรรม อย่างใดอย่างหนึ่ง (ระบุชื่อ พร้อมแนบหลักฐาน)',
            '9' => 'การหารายได้เข้าสำนักฯ (ระบุ พร้อมแนบหลักฐาน)',
            '10' => 'อื่น ๆ (ระบุ พร้อมแนบหลักฐาน)',
        ];
    ?>

        <!-- SPECIAL SECTION 1: PERFORMANCE (50 POINTS FOR ITEMS 1.1-1.5) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">ด้านที่ ๑ ผลสัมฤทธิ์ของงาน / ผลงาน (ข้อ ๑.๑ - ๑.๕: ๕๐ คะแนน)</h5>
                    <small class="text-white-50">ประเมินตาม ๕ ปัจจัย: ปริมาณผลงาน (10), คุณภาพ (10), ความทันเวลา (10), ความคุ้มค่าทรัพยากร (10), ผลสัมฤทธิ์ (10)</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    รวมข้อ ๑.๑-๑.๕: <span id="spec-sec1-direct-sum">50.00</span> / 50 คะแนน
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th>รายการประเมิน</th>
                                <th style="width: 120px;">คะแนนเต็ม</th>
                                <th style="width: 220px;">การประเมินตนเอง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($specDirectItems as $sIdx => $item): 
                                $ans = $answers[$item->id] ?? null;
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $sIdx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= Html::encode($item->name_th) ?></strong>
                                        <?php if ($item->description): ?>
                                            <small class="text-muted d-block"><?= nl2br(Html::encode($item->description)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-primary"><?= $item->max_score ?></td>
                                    <td>
                                        <input type="number" step="0.5" min="0" max="<?= $item->max_score ?>" class="form-control form-control-sm text-center auto-save-field spec-score-input spec-sec1-score-input fw-bold" name="item_score[<?= $item->id ?>]" data-item-id="<?= $item->id ?>" value="<?= Html::encode($ans ? ($ans->numeric_value ?? $ans->text_value) : $item->max_score) ?>" <?= !$isEditable ? 'readonly' : '' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SPECIAL ITEM 1.6: SECONDARY WORK CHECKLIST (5 POINTS - เหมือนข้อ 6 พนง.ราชการ) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">๑.๖ องค์ประกอบอื่น ๆ: ภาระงานรองหรืองานที่ได้รับมอบหมาย (คะแนนเต็ม ๕ คะแนน)</h5>
                    <small class="text-white-50">เกณฑ์: 6-10 ข้อ = 5 คะแนน, 5 ข้อ = 4 คะแนน, 3-4 ข้อ = 3 คะแนน, 2 ข้อ = 2 คะแนน, 1 ข้อ = 1 คะแนน</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    เลือกแล้ว: <span id="spec-sec-count-badge">0</span>/10 ข้อ | ระดับคะแนน: <span id="spec-sec-points-badge">0</span>/5 คะแนน
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 70px;">เลือก</th>
                                <th style="width: 60px;">ข้อ</th>
                                <th>รายการภาระงานรองหรืองานที่ได้รับมอบหมาย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $spec16ItemId = $spec16Item ? $spec16Item->id : 0;
                            foreach ($spec10Options as $optKey => $optLabel): 
                                $isChecked = in_array($optKey, $spec16Selected);
                                $rawOpt = $spec16Details[$optKey] ?? null;
                                $optEntries = [];
                                if (is_array($rawOpt)) {
                                    if (isset($rawOpt[0]) && is_array($rawOpt[0])) {
                                        $optEntries = $rawOpt;
                                    } elseif (isset($rawOpt['detail']) || isset($rawOpt['file_id'])) {
                                        $optEntries = [$rawOpt];
                                    }
                                } elseif (is_string($rawOpt) && trim($rawOpt) !== '') {
                                    $optEntries = [
                                        [
                                            'detail' => $rawOpt,
                                            'file_id' => null,
                                            'file_name' => '',
                                            'file_url' => '',
                                        ]
                                    ];
                                }
                                if (empty($optEntries)) {
                                    $optEntries = [
                                        [
                                            'detail' => '',
                                            'file_id' => null,
                                            'file_name' => '',
                                            'file_url' => '',
                                        ]
                                    ];
                                }
                            ?>
                                <tr>
                                    <td class="text-center align-top pt-3">
                                        <input class="form-check-input auto-save-field spec-sec-checkbox" type="checkbox" name="spec_sec_check[]" value="<?= $optKey ?>" id="spec_sec_<?= $optKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                    </td>
                                    <td class="text-center fw-bold text-muted align-top pt-3"><?= $optKey ?></td>
                                    <td>
                                        <label class="form-check-label fw-bold text-dark mb-2 d-block" for="spec_sec_<?= $optKey ?>" style="cursor: pointer;">
                                            <?= Html::encode($optLabel) ?>
                                        </label>

                                        <div class="spec-sec-evidence-container <?= !$isChecked ? 'd-none' : '' ?>">
                                            <div class="spec-sec-entries-wrapper" data-opt-key="<?= $optKey ?>">
                                                <?php foreach ($optEntries as $eIdx => $entry): 
                                                    $hasFile = !empty($entry['file_id']) || !empty($entry['file_url']);
                                                    $fileName = $entry['file_name'] ?? '';
                                                    $fileUrl = $entry['file_url'] ?? '';
                                                    $fileId = $entry['file_id'] ?? '';
                                                    $isImage = (bool)preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fileName);
                                                    $isPdf = (bool)preg_match('/\.pdf$/i', $fileName);
                                                ?>
                                                    <div class="spec-sec-entry-item mb-2 p-2 border rounded bg-white shadow-sm" data-row-idx="<?= $eIdx ?>">
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col-lg-6 col-md-12">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text bg-light text-muted">
                                                                        <i class="bi bi-pencil-square me-1"></i> <span class="entry-order-label">#<?= $eIdx + 1 ?></span>
                                                                    </span>
                                                                    <input type="text" class="form-control form-control-sm spec-sec-detail-input" placeholder="ระบุรายละเอียด เช่น ชื่อกิจกรรม/โครงการ, คำสั่งแต่งตั้ง, ผลงาน..." value="<?= Html::encode($entry['detail'] ?? '') ?>" <?= !$isEditable ? 'readonly' : '' ?>>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-5 col-md-10">
                                                                <div class="spec-sec-file-box">
                                                                    <!-- สถานะมีไฟล์แนบ -->
                                                                    <div class="spec-sec-file-attached d-flex align-items-center justify-content-between p-1 px-2 border rounded bg-light <?= !$hasFile ? 'd-none' : '' ?>">
                                                                        <div class="text-truncate me-2 small">
                                                                            <i class="bi bi-file-earmark-check text-success me-1"></i>
                                                                            <span class="spec-sec-file-name fw-bold text-dark" title="<?= Html::encode($fileName) ?>"><?= Html::encode($fileName ?: 'เอกสารหลักฐาน') ?></span>
                                                                        </div>
                                                                        <div class="btn-group btn-group-sm flex-shrink-0">
                                                                            <button type="button" class="btn btn-xs btn-outline-info spec-sec-preview-btn" 
                                                                                    data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                                                                    onclick="previewEvidenceFile(this)"
                                                                                    data-url="<?= Html::encode($fileUrl) ?>" 
                                                                                    data-name="<?= Html::encode($fileName) ?>"
                                                                                    data-is-image="<?= $isImage ? '1' : '0' ?>"
                                                                                    data-is-pdf="<?= $isPdf ? '1' : '0' ?>"
                                                                                    title="พรีวิวหลักฐาน">
                                                                                <i class="bi bi-eye"></i> พรีวิว
                                                                            </button>
                                                                            <a href="<?= Html::encode($fileUrl ?: '#') ?>" target="_blank" class="btn btn-xs btn-outline-secondary spec-sec-download-btn" title="ดาวน์โหลด">
                                                                                <i class="bi bi-download"></i>
                                                                            </a>
                                                                            <?php if ($isEditable): ?>
                                                                                <button type="button" class="btn btn-xs btn-outline-danger spec-sec-remove-file-btn" title="ลบไฟล์แนบนี้">
                                                                                    <i class="bi bi-x-lg"></i>
                                                                                </button>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    <!-- สถานะยังไม่แนบไฟล์ -->
                                                                    <div class="spec-sec-file-uploader <?= $hasFile ? 'd-none' : '' ?>">
                                                                        <?php if ($isEditable): ?>
                                                                            <div class="input-group input-group-sm">
                                                                                <input type="file" class="form-control form-control-sm spec-sec-file-input" accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.xlsx">
                                                                                <button type="button" class="btn btn-sm btn-outline-primary btn-spec-upload-file">
                                                                                    <i class="bi bi-upload me-1"></i> แนบไฟล์
                                                                                </button>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <span class="text-muted small fst-italic">ไม่มีหลักฐานแนบ</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <input type="hidden" class="spec-sec-file-id" value="<?= Html::encode($fileId) ?>">
                                                                    <input type="hidden" class="spec-sec-file-name-val" value="<?= Html::encode($fileName) ?>">
                                                                    <input type="hidden" class="spec-sec-file-url-val" value="<?= Html::encode($fileUrl) ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-1 col-md-2 text-end">
                                                                <?php if ($isEditable): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-spec-entry" title="ลบรายการนี้" style="<?= count($optEntries) <= 1 ? 'display:none;' : '' ?>">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if ($isEditable): ?>
                                                <div class="mt-1">
                                                    <button type="button" class="btn btn-xs btn-outline-primary shadow-sm btn-add-spec-entry" data-opt-key="<?= $optKey ?>">
                                                        <i class="bi bi-plus-circle me-1"></i> เพิ่มรายละเอียดและหลักฐาน (มีมากกว่า ๑)
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">คะแนนรวมด้านที่ ๑ ทั้งหมด (ข้อ ๑.๑ - ๑.๖ เต็ม ๕๕ คะแนน):</td>
                                <td class="fw-bold text-primary fs-6"><span id="spec-sec1-sum">55.00</span> / 55 คะแนน</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- SPECIAL SECTION 2: CHARACTERISTICS (45 POINTS) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (๔๕ คะแนน)</h5>
                    <small class="text-white-50">ความสามารถ วินัย ความรับผิดชอบ ความร่วมมือ การมาทำงาน การวางแผน ความคิดริเริ่ม</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    รวมด้านที่ ๒: <span id="spec-sec2-sum">45.00</span> / 45 คะแนน
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th>รายการประเมิน</th>
                                <th style="width: 120px;">คะแนนเต็ม</th>
                                <th style="width: 220px;">การประเมินตนเอง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($secSpec2):
                                foreach ($secSpec2->items as $sIdx => $item): 
                                    $ans = $answers[$item->id] ?? null;
                            ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $sIdx + 1 ?></td>
                                        <td>
                                            <strong class="text-dark d-block"><?= Html::encode($item->name_th) ?></strong>
                                            <?php if ($item->description): ?>
                                                <small class="text-muted d-block"><?= nl2br(Html::encode($item->description)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?= $item->max_score ?></td>
                                        <td>
                                            <input type="number" step="0.5" min="0" max="<?= $item->max_score ?>" class="form-control form-control-sm text-center auto-save-field spec-score-input spec-sec2-score-input fw-bold" name="item_score[<?= $item->id ?>]" data-item-id="<?= $item->id ?>" value="<?= Html::encode($ans ? ($ans->numeric_value ?? $ans->text_value) : $item->max_score) ?>" <?= !$isEditable ? 'readonly' : '' ?>>
                                        </td>
                                    </tr>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- ==================== EVIDENCE FILE ATTACHMENTS ==================== -->
    <div class="card card-rmutt shadow-sm mb-4" id="evidence-files-card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-paperclip me-1 text-primary"></i> ๔. เอกสารหลักฐานประกอบการประเมิน (Evidence Files)
            </h6>
            <span class="badge bg-light text-muted border">รองรับ PDF, JPG, PNG, ZIP, DOCX, XLSX</span>
        </div>
        <div class="card-body">
            
            <?php if ($isEditable): ?>
                <div class="p-3 border rounded bg-light mb-3">
                    <form id="evidence-upload-form" enctype="multipart/form-data" class="row g-2 align-items-center">
                        <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
                        <input type="hidden" name="evaluation_id" value="<?= $evaluation->id ?>">
                        <div class="col-md-5">
                            <input type="text" name="description" id="evidence-description" class="form-control form-control-sm" placeholder="ระบุคำอธิบายหลักฐาน เช่น ใบ Certificate ภาษาอังกฤษ, คำสั่งแต่งตั้ง..." required>
                        </div>
                        <div class="col-md-5">
                            <input type="file" name="evidence_file" id="evidence-file" class="form-control form-control-sm" required accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.xlsx">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100" id="btn-upload-file">
                                <i class="bi bi-upload me-1"></i> อัปโหลด
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div id="evidence-file-list" class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>คำอธิบายหลักฐาน</th>
                            <th>ชื่อไฟล์</th>
                            <th style="width: 120px;" class="text-center">ขนาด</th>
                            <th style="width: 140px;" class="text-center">วันที่อัปโหลด</th>
                            <th style="width: 100px;" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="evidence-table-body">
                        <?php if (empty($evidenceFiles)): ?>
                            <tr id="empty-file-row">
                                <td colspan="6" class="text-center text-muted py-3">ยังไม่มีไฟล์หลักฐานแนบในแบบประเมินนี้</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($evidenceFiles as $fIdx => $file): ?>
                                <tr>
                                    <td class="text-center"><?= $fIdx + 1 ?></td>
                                    <td><strong><?= Html::encode($file->description ?: 'เอกสารประกอบ') ?></strong></td>
                                    <td>
                                        <a href="<?= $file->fileUrl ?>" target="_blank" class="text-decoration-none">
                                            <i class="bi bi-file-earmark-arrow-down text-primary me-1"></i> <?= Html::encode($file->original_filename) ?>
                                        </a>
                                    </td>
                                    <td class="text-center"><?= number_format($file->file_size / 1024, 1) ?> KB</td>
                                    <td class="text-center text-muted small"><?= Yii::$app->formatter->asDate($file->created_at, 'php:d/m/Y H:i') ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-info me-1 btn-preview-evidence shadow-sm" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#previewEvidenceModal"
                                                onclick="previewEvidenceFile(this)"
                                                data-url="<?= $file->fileUrl ?>" 
                                                data-name="<?= Html::encode($file->original_name) ?>"
                                                data-is-image="<?= $file->isImage() ? '1' : '0' ?>"
                                                data-is-pdf="<?= $file->isPdf() ? '1' : '0' ?>">
                                            <i class="bi bi-eye"></i> พรีวิว
                                        </button>
                                        <?php if ($isEditable): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-file-id="<?= $file->id ?>"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- Hidden Form for Submit -->
<form id="form-submit-self" action="<?= Url::to(['submit-self', 'id' => $evaluation->id]) ?>" method="post" style="display: none;">
    <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
</form>

<!-- Modal Preview Evidence -->
<div class="modal fade" id="previewEvidenceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold text-white mb-0" id="previewModalTitle">
                    <i class="bi bi-file-earmark-pdf me-1 text-warning"></i> พรีวิวเอกสารหลักฐาน
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light text-center" style="min-height: 500px;" id="previewModalContainer">
                <div class="p-5 text-muted">กำลังโหลดเอกสาร...</div>
            </div>
            <div class="modal-footer bg-white py-2">
                <a id="previewOpenNewTab" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดในแท็บใหม่ / ดาวน์โหลด
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
function previewEvidenceFile(btn) {
    var url = btn.getAttribute('data-url');
    var name = btn.getAttribute('data-name') || '';
    var isImage = btn.getAttribute('data-is-image') === '1';
    var isPdf = btn.getAttribute('data-is-pdf') === '1';

    var titleEl = document.getElementById('previewModalTitle');
    var linkEl = document.getElementById('previewOpenNewTab');
    var container = document.getElementById('previewModalContainer');

    if (titleEl) {
        titleEl.textContent = ' พรีวิวเอกสาร: ' + name;
        var icon = document.createElement('i');
        icon.className = 'bi bi-file-earmark me-1 text-warning';
        titleEl.prepend(icon);
    }
    if (linkEl) linkEl.setAttribute('href', url);

    if (container) {
        container.innerHTML = '';
        if (isImage) {
            var wrapper = document.createElement('div');
            wrapper.className = 'p-3';
            var img = document.createElement('img');
            img.src = url;
            img.className = 'img-fluid rounded shadow-sm d-block mx-auto';
            img.style.maxHeight = '75vh';
            img.alt = name;
            wrapper.appendChild(img);
            container.appendChild(wrapper);
        } else if (isPdf) {
            var iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.style.width = '100%';
            iframe.style.height = '75vh';
            iframe.style.border = 'none';
            container.appendChild(iframe);
        } else {
            var box = document.createElement('div');
            box.className = 'p-5 text-center';
            box.innerHTML = '<i class="bi bi-file-earmark-zip display-1 text-muted d-block mb-3"></i><p class="fs-5 text-dark">ไฟล์ประเภทนี้ไม่สามารถแสดงพรีวิวแบบ Inline ได้</p>';
            var dlBtn = document.createElement('a');
            dlBtn.href = url;
            dlBtn.target = '_blank';
            dlBtn.className = 'btn btn-primary px-4';
            dlBtn.innerHTML = '<i class="bi bi-download me-1"></i> ดาวน์โหลดไฟล์เอกสาร';
            box.appendChild(dlBtn);
            container.appendChild(box);
        }
    }
}
</script>

<!-- JavaScript for Calculations, Dynamic Rows, and Auto-Save -->
<?php
$autoSaveUrl = Url::to(['auto-save']);
$uploadUrl = Url::to(['upload-evidence']);
$deleteFileUrl = Url::to(['delete-evidence']);
$evaluationId = $evaluation->id;
$mainItemId = (isset($mainItem) && $mainItem) ? $mainItem->id : 0;
$govtMainItemId = (isset($govtMainItem) && $govtMainItem) ? $govtMainItem->id : 0;
$govtSecItemId = (isset($govtSecItem) && $govtSecItem) ? $govtSecItem->id : 0;
$spec16ItemId = (isset($spec16Item) && $spec16Item) ? $spec16Item->id : 0;
$perfWeightVal = floatval($perfWeight);
$compWeightVal = floatval($compWeight);

$script = <<<JS
$(function() {
    let autoSaveTimer = null;

    // Multi-Step Wizard Engine (CIVIL & UNIVERSITY)
    function goToStep(step, updateHash = true) {
        step = parseInt(step);
        if (isNaN(step) || step < 1 || step > 3) step = 1;

        // Auto-save any changes before moving
        triggerAutoSave();

        // Switch active step pane
        $('.eval-step-pane').hide();
        $('#step-pane-' + step).fadeIn(150);

        // Update Wizard Indicator Tabs
        $('.eval-step-btn').removeClass('active');
        $('#step-tab-' + step).addClass('active');

        $('.eval-step-btn .step-badge').removeClass('bg-primary').addClass('bg-secondary');
        $('#step-tab-' + step + ' .step-badge').removeClass('bg-secondary').addClass('bg-primary');

        // Update Quick Nav in sticky top header
        $('.eval-step-quick-nav button').removeClass('btn-primary active').addClass('btn-outline-primary');
        $('#float-step-' + step).removeClass('btn-outline-primary').addClass('btn-primary active');
        $('#float-step-' + step + ' .badge').removeClass('bg-secondary text-white').addClass('bg-light text-primary');

        // Toggle Evidence Files Card: hide on step 1, show on step 2 & 3
        let pType = "{$personnelType}";
        if (pType === 'CIVIL' || pType === 'UNIVERSITY') {
            if (step === 1) {
                $('#evidence-files-card').hide();
            } else {
                $('#evidence-files-card').show();
            }
        }

        // Recalculate everything so Step 1 summary and badge are updated
        recalculateAll();

        if (updateHash) {
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, null, '#step-' + step);
            } else {
                window.location.hash = '#step-' + step;
            }
            if ($('#evalWizardNavCard').length) {
                $('html, body').animate({
                    scrollTop: $('#evalWizardNavCard').offset().top - 80
                }, 200);
            }
        }
    }

    // Step navigation click handlers
    $(document).on('click', '.eval-step-btn', function() {
        let step = $(this).data('step');
        goToStep(step);
    });

    $(document).on('click', '.btn-nav-step', function() {
        let step = $(this).data('target-step');
        goToStep(step);
    });

    // Dynamic IDP rows (Form 1 Section 3)
    let toThDigits = function(num) {
        let th = ['๐','๑','๒','๓','๔','๕','๖','๗','๘','๙'];
        return String(num).split('').map(function(d) { return th[d] || d; }).join('');
    };

    function reindexIdpRows() {
        $('#summary-idp-tbody .summary-idp-row').each(function(idx) {
            $(this).find('.idp-row-number').text(toThDigits(idx + 1));
        });
    }

    $(document).on('click', '#btn-add-idp-row', function() {
        let rowCount = $('#summary-idp-tbody .summary-idp-row').length + 1;
        let newRow = '<tr class="summary-idp-row">' +
            '<td class="text-center fw-bold idp-row-number">' + toThDigits(rowCount) + '</td>' +
            '<td><input type="text" class="form-control form-control-sm idp-topic-input auto-save-field" placeholder="ระบุความรู้ / ทักษะ / สมรรถนะ ที่ต้องได้รับการพัฒนา..."></td>' +
            '<td><input type="text" class="form-control form-control-sm idp-method-input auto-save-field" placeholder="ระบุวิธีการพัฒนา..."></td>' +
            '<td><input type="text" class="form-control form-control-sm idp-timeline-input auto-save-field" placeholder="ระบุช่วงเวลาที่ต้องการการพัฒนา..." value=""></td>' +
            '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-idp-row" title="ลบแถว"><i class="bi bi-trash"></i></button></td>' +
            '</tr>';
        $('#summary-idp-tbody').append(newRow);
        reindexIdpRows();
        triggerAutoSave();
    });

    $(document).on('click', '.btn-remove-idp-row', function() {
        if ($('#summary-idp-tbody .summary-idp-row').length > 1) {
            $(this).closest('tr').remove();
            reindexIdpRows();
        } else {
            let row = $(this).closest('tr');
            row.find('.idp-topic-input').val('');
            row.find('.idp-method-input').val('');
            row.find('.idp-timeline-input').val('');
        }
        triggerAutoSave();
    });

    // Hash check on initial page load
    let initStep = 1;
    let initialHash = window.location.hash;
    if (initialHash === '#step-2') initStep = 2;
    else if (initialHash === '#step-3') initStep = 3;
    goToStep(initStep, false);

    // Recalculate everything on load
    recalculateAll();

    // Event listeners
    $(document).on('input change', '.auto-save-field', function() {
        recalculateAll();
        triggerAutoSave();
    });

    $(document).on('blur change', '.main-score-select', function() {
        let val = parseFloat($(this).val());
        if (!isNaN(val)) {
            val = Math.min(5, Math.max(1, Math.round(val * 10) / 10));
            $(this).val(val);
        }
        recalculateAll();
    });

    $(document).on('change', '.govt-sec-checkbox', function() {
        let card = $(this).closest('.govt-sec-option-card');
        let body = card.find('.govt-sec-entries-body');
        if ($(this).is(':checked')) {
            card.addClass('border-primary');
            body.removeClass('d-none');
        } else {
            card.removeClass('border-primary');
            body.addClass('d-none');
        }

        let count = $('.govt-sec-checkbox:checked').length;
        let rubricLevel = 1;
        if (count >= 6) rubricLevel = 5;
        else if (count === 5) rubricLevel = 4;
        else if (count >= 3) rubricLevel = 3;
        else if (count === 2) rubricLevel = 2;
        else if (count === 1) rubricLevel = 1;

        if (!$('#govt-sec-kpi-vol').data('manually-set')) {
            $('#govt-sec-kpi-vol, #govt-sec-kpi-qua, #govt-sec-kpi-time, #govt-sec-kpi-res').val(rubricLevel);
        }

        recalculateAll();
        triggerAutoSave();
    });

    $(document).on('change', '.spec-sec-checkbox', function() {
        let row = $(this).closest('tr');
        let container = row.find('.spec-sec-evidence-container');
        if ($(this).is(':checked')) {
            container.removeClass('d-none');
        } else {
            container.addClass('d-none');
        }

        recalculateAll();
        triggerAutoSave();
    });

    $(document).on('change', '.govt-sec-kpi', function() {
        $('#govt-sec-kpi-vol').data('manually-set', true);
    });

    $('#btn-manual-save').on('click', function() {
        triggerAutoSave(true);
    });

    // Submit Self Evaluation
    $('#btn-submit-self-eval').on('click', function() {
        let pType = "{$personnelType}";
        if (pType === 'CIVIL' || pType === 'UNIVERSITY') {
            let totalWeight = calculateMainWorkWeight();
            if (Math.abs(totalWeight - 80) > 0.01) {
                alert('คำเตือน: น้ำหนักรวมของภาระงานหลักต้องเท่ากับ 80% พอดี (ปัจจุบัน: ' + totalWeight + '%)');
                return;
            }
        }

        if (confirm('คุณต้องการยืนยันการส่งแบบประเมินตนเองนี้ใช่หรือไม่?\\n\\nเมื่อส่งแล้วจะไม่สามารถแก้ไขได้จนกว่าผู้บังคับบัญชาจะส่งกลับ')) {
            $('#form-submit-self').submit();
        }
    });

    // Dynamic Row Adding for Civil/Univ
    $('#btn-add-main-work').on('click', function() {
        let rowCount = $('.main-work-row').length;
        let newRow = `
            <tr class="main-work-row">
                <td class="text-center fw-bold row-index">\${rowCount + 1}</td>
                <td>
                    <textarea class="form-control form-control-sm auto-save-field main-work-title mb-1" name="main_work[\${rowCount}][title]" rows="3" placeholder="ระบุกิจกรรม/โครงการ/ภาระงานหลัก (รายละเอียดข้อมูล)..." required></textarea>
                </td>
                <td>
                    <div class="p-2 bg-light rounded border text-start pdca-info-box" style="font-size: 0.78rem; line-height: 1.4;">
                        <strong class="text-primary d-block mb-1"><i class="bi bi-info-circle me-1"></i> ระดับความสำเร็จ (ตามวงจร PDCA):</strong>
                        <span class="d-block mb-1 px-1 rounded pdca-line text-muted" data-level="1"><strong>ระดับ ๑ (Plan):</strong> มีแผนการดำเนินงาน/แนวทางการดำเนินงาน</span>
                        <span class="d-block mb-1 px-1 rounded pdca-line text-muted" data-level="2"><strong>ระดับ ๒ (Do):</strong> ดำเนินการตามแผน/แนวทางที่กำหนด</span>
                        <span class="d-block mb-1 px-1 rounded pdca-line text-muted" data-level="3"><strong>ระดับ ๓ (Check):</strong> ทบทวน ตรวจสอบ ประเมินผลการดำเนินงาน</span>
                        <span class="d-block mb-1 px-1 rounded pdca-line text-muted" data-level="4"><strong>ระดับ ๔ (Act):</strong> แก้ไขปรับปรุงกระบวนการ</span>
                        <span class="d-block px-1 rounded pdca-line bg-success-subtle text-success fw-bold border border-success-subtle" data-level="5"><strong>ระดับ ๕ (Impact):</strong> ปรับปรุงต่อเนื่อง สร้างคุณค่าเพิ่มหรือนวัตกรรม</span>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <div class="input-group input-group-sm justify-content-center" style="min-width: 95px; max-width: 120px; margin: 0 auto;">
                        <span class="input-group-text px-1 text-muted small">ระดับ</span>
                        <input type="number" step="0.1" min="1" max="5" class="form-control form-control-sm text-center auto-save-field main-score-select fw-bold border-primary" name="main_work[\${rowCount}][self_score]" value="5" placeholder="1-5" required>
                    </div>
                </td>
                <td class="text-center main-tgt-col main-tgt-1"><span class="text-muted">1</span></td>
                <td class="text-center main-tgt-col main-tgt-2"><span class="text-muted">2</span></td>
                <td class="text-center main-tgt-col main-tgt-3"><span class="text-muted">3</span></td>
                <td class="text-center main-tgt-col main-tgt-4"><span class="text-muted">4</span></td>
                <td class="text-center main-tgt-col main-tgt-5"><span class="badge bg-primary">5</span></td>
                <td class="text-center fw-bold text-dark main-raw-score">5</td>
                <td>
                    <div class="input-group input-group-sm flex-nowrap justify-content-center" style="min-width: 80px; max-width: 95px; margin: 0 auto;">
                        <input type="number" step="1" min="1" max="80" class="form-control form-control-sm text-center auto-save-field main-weight-input fw-bold px-1" name="main_work[\${rowCount}][weight]" value="10" required>
                        <span class="input-group-text px-1 text-muted">%</span>
                    </div>
                </td>
                <td class="text-center fw-bold text-primary row-weighted-score">10.00</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        $('#row-add-main-btn').before(newRow);
        recalculateAll();
        triggerAutoSave();
    });

    // Dynamic Row Adding for GOVT
    $('#btn-add-govt-row').on('click', function() {
        let rowCount = $('.govt-main-work-row').length;
        let newRow = `
            <tr class="govt-main-work-row">
                <td class="text-center fw-bold row-index">\${rowCount + 1}</td>
                <td>
                    <textarea class="form-control form-control-sm auto-save-field govt-work-title" name="govt_main[\${rowCount}][title]" rows="2" placeholder="ระบุรายละเอียดภาระงานที่ปฏิบัติ..." required></textarea>
                </td>
                <td class="text-center">
                    <span class="badge bg-primary fs-6 govt-row-self-score">ระดับ 5.0</span>
                </td>
                <td>
                    <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-volume" name="govt_main[\${rowCount}][kpi_volume]">
                        <option value="5" selected>ระดับ 5</option>
                        <option value="4">ระดับ 4</option>
                        <option value="3">ระดับ 3</option>
                        <option value="2">ระดับ 2</option>
                        <option value="1">ระดับ 1</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-quality" name="govt_main[\${rowCount}][kpi_quality]">
                        <option value="5" selected>ระดับ 5</option>
                        <option value="4">ระดับ 4</option>
                        <option value="3">ระดับ 3</option>
                        <option value="2">ระดับ 2</option>
                        <option value="1">ระดับ 1</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-timeliness" name="govt_main[\${rowCount}][kpi_timeliness]">
                        <option value="5" selected>ระดับ 5</option>
                        <option value="4">ระดับ 4</option>
                        <option value="3">ระดับ 3</option>
                        <option value="2">ระดับ 2</option>
                        <option value="1">ระดับ 1</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm auto-save-field govt-kpi-select govt-kpi-resource" name="govt_main[\${rowCount}][kpi_resource]">
                        <option value="5" selected>ระดับ 5</option>
                        <option value="4">ระดับ 4</option>
                        <option value="3">ระดับ 3</option>
                        <option value="2">ระดับ 2</option>
                        <option value="1">ระดับ 1</option>
                    </select>
                </td>
                <td class="text-center govt-tgt-col govt-tgt-1"><span class="text-muted">1</span></td>
                <td class="text-center govt-tgt-col govt-tgt-2"><span class="text-muted">2</span></td>
                <td class="text-center govt-tgt-col govt-tgt-3"><span class="text-muted">3</span></td>
                <td class="text-center govt-tgt-col govt-tgt-4"><span class="text-muted">4</span></td>
                <td class="text-center govt-tgt-col govt-tgt-5"><span class="badge bg-primary">5</span></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" step="1" min="1" max="80" class="form-control form-control-sm text-center auto-save-field govt-work-weight fw-bold" name="govt_main[\${rowCount}][weight]" value="20">
                        <span class="input-group-text">%</span>
                    </div>
                </td>
                <td class="text-center fw-bold text-primary govt-row-weighted">20.00</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-govt-row"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        $('#govt-add-row-tr').before(newRow);
        recalculateAll();
        triggerAutoSave();
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#main-work-tbody tr').length <= 1) {
            alert('ต้องมีภาระงานหลักอย่างน้อย 1 รายการ');
            return;
        }
        $(this).closest('tr').remove();
        $('#main-work-tbody tr').each(function(idx) {
            $(this).find('.row-index').text(idx + 1);
        });
        recalculateAll();
        triggerAutoSave();
    });

    $(document).on('click', '.btn-remove-govt-row', function() {
        if ($('#govt-main-work-tbody tr').length <= 1) {
            alert('ต้องมีภาระงานหลักอย่างน้อย 1 รายการ');
            return;
        }
        $(this).closest('tr').remove();
        $('#govt-main-work-tbody tr').each(function(idx) {
            $(this).find('.row-index').text(idx + 1);
        });
        recalculateAll();
        triggerAutoSave();
    });

    // Dynamic Row & Evidence Upload for SPECIAL 1.6
    $(document).on('click', '.btn-add-spec-entry', function() {
        let optKey = $(this).data('opt-key');
        let container = $('.spec-sec-entries-wrapper[data-opt-key="' + optKey + '"]');
        let rowCount = container.find('.spec-sec-entry-item').length;
        let newIdx = rowCount + 1;

        let newRow = $(`
            <div class="spec-sec-entry-item mb-2 p-2 border rounded bg-white shadow-sm" data-row-idx="\${rowCount}">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">
                                <i class="bi bi-pencil-square me-1"></i> <span class="entry-order-label">#\${newIdx}</span>
                            </span>
                            <input type="text" class="form-control form-control-sm spec-sec-detail-input" placeholder="ระบุรายละเอียด เช่น ชื่อกิจกรรม/โครงการ, คำสั่งแต่งตั้ง, ผลงาน...">
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-10">
                        <div class="spec-sec-file-box">
                            <div class="spec-sec-file-attached d-flex align-items-center justify-content-between p-1 px-2 border rounded bg-light d-none">
                                <div class="text-truncate me-2 small">
                                    <i class="bi bi-file-earmark-check text-success me-1"></i>
                                    <span class="spec-sec-file-name fw-bold text-dark"></span>
                                </div>
                                <div class="btn-group btn-group-sm flex-shrink-0">
                                    <button type="button" class="btn btn-xs btn-outline-info spec-sec-preview-btn" 
                                            data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                            onclick="previewEvidenceFile(this)"
                                            data-url="" data-name="" data-is-image="0" data-is-pdf="0" title="พรีวิวหลักฐาน">
                                        <i class="bi bi-eye"></i> พรีวิว
                                    </button>
                                    <a href="#" target="_blank" class="btn btn-xs btn-outline-secondary spec-sec-download-btn" title="ดาวน์โหลด">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger spec-sec-remove-file-btn" title="ลบไฟล์แนบนี้">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="spec-sec-file-uploader">
                                <div class="input-group input-group-sm">
                                    <input type="file" class="form-control form-control-sm spec-sec-file-input" accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.xlsx">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-spec-upload-file">
                                        <i class="bi bi-upload me-1"></i> แนบไฟล์
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" class="spec-sec-file-id" value="">
                            <input type="hidden" class="spec-sec-file-name-val" value="">
                            <input type="hidden" class="spec-sec-file-url-val" value="">
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-spec-entry" title="ลบรายการนี้">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);

        container.append(newRow);
        container.find('.btn-remove-spec-entry').show();

        // Auto-check checkbox if unchecked
        let cb = $('#spec_sec_' + optKey);
        if (!cb.prop('checked')) {
            cb.prop('checked', true).trigger('change');
        }

        triggerAutoSave();
    });

    $(document).on('click', '.btn-remove-spec-entry', function() {
        let container = $(this).closest('.spec-sec-entries-wrapper');
        $(this).closest('.spec-sec-entry-item').remove();

        // Re-index labels
        container.find('.spec-sec-entry-item').each(function(idx) {
            $(this).find('.entry-order-label').text('#' + (idx + 1));
        });

        if (container.find('.spec-sec-entry-item').length <= 1) {
            container.find('.btn-remove-spec-entry').hide();
        }

        triggerAutoSave();
    });

    $(document).on('input change', '.spec-sec-detail-input', function() {
        let optKey = $(this).closest('.spec-sec-entries-wrapper').data('opt-key');
        let cb = $('#spec_sec_' + optKey);
        if (!cb.prop('checked') && $(this).val().trim() !== '') {
            cb.prop('checked', true).trigger('change');
        }
        triggerAutoSave();
    });

    $(document).on('click', '.btn-spec-upload-file', function(e) {
        e.preventDefault();
        let fileInput = $(this).closest('.spec-sec-file-uploader').find('.spec-sec-file-input');
        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            fileInput.trigger('click');
            return;
        }
        uploadSpecFile(fileInput);
    });

    $(document).on('change', '.spec-sec-file-input', function() {
        if (this.files && this.files.length > 0) {
            uploadSpecFile($(this));
        }
    });

    function uploadSpecFile(fileInput) {
        let file = fileInput[0].files[0];
        if (!file) return;

        let itemRow = fileInput.closest('.spec-sec-entry-item');
        let fileBox = itemRow.find('.spec-sec-file-box');
        let optKey = itemRow.closest('.spec-sec-entries-wrapper').data('opt-key');
        let uploadBtn = fileBox.find('.btn-spec-upload-file');
        let originalBtnHtml = uploadBtn.html();

        uploadBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังแนบ...');

        let formData = new FormData();
        formData.append('evidence_file', file);
        formData.append('evaluation_id', '{$evaluationId}');
        formData.append('evaluation_item_id', '{$spec16ItemId}');
        formData.append('description', 'หลักฐานข้อ ๑.๖ ข้อ ' + optKey);
        formData.append('{$csrfParam}', '{$csrfToken}');

        $.ajax({
            url: '{$uploadUrl}',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                uploadBtn.prop('disabled', false).html(originalBtnHtml);
                if (res.success && res.file) {
                    let f = res.file;
                    let ext = f.name.split('.').pop().toLowerCase();
                    let isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1 ? '1' : '0';
                    let isPdf = ext === 'pdf' ? '1' : '0';

                    fileBox.find('.spec-sec-file-id').val(f.id);
                    fileBox.find('.spec-sec-file-name-val').val(f.name);
                    fileBox.find('.spec-sec-file-url-val').val(f.url);

                    fileBox.find('.spec-sec-file-name').text(f.name).attr('title', f.name);
                    fileBox.find('.spec-sec-preview-btn')
                        .attr('data-url', f.url)
                        .attr('data-name', f.name)
                        .attr('data-is-image', isImage)
                        .attr('data-is-pdf', isPdf);
                    fileBox.find('.spec-sec-download-btn').attr('href', f.url);

                    fileBox.find('.spec-sec-file-uploader').addClass('d-none');
                    fileBox.find('.spec-sec-file-attached').removeClass('d-none');

                    // Auto-check checkbox if unchecked
                    let cb = $('#spec_sec_' + optKey);
                    if (!cb.prop('checked')) {
                        cb.prop('checked', true).trigger('change');
                    }

                    // Append to evidence table bottom
                    let emptyRow = $('#empty-file-row');
                    if (emptyRow.length) emptyRow.remove();
                    let tbody = $('#evidence-table-body');
                    let count = tbody.find('tr').length + 1;
                    tbody.append(`
                        <tr>
                            <td class="text-center">\${count}</td>
                            <td><strong>หลักฐานข้อ ๑.๖ ข้อ \${optKey}</strong></td>
                            <td><a href="\${f.url}" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark-arrow-down text-primary me-1"></i> \${f.name}</a></td>
                            <td class="text-center">\${f.size}</td>
                            <td class="text-center text-muted small">เมื่อสักครู่</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-info me-1 btn-preview-evidence shadow-sm"
                                        data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                        onclick="previewEvidenceFile(this)"
                                        data-url="\${f.url}" data-name="\${f.name}" data-is-image="\${isImage}" data-is-pdf="\${isPdf}">
                                    <i class="bi bi-eye"></i> พรีวิว
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-file-id="\${f.id}"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `);

                    triggerAutoSave();
                } else {
                    alert(res.message || 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
                }
            },
            error: function() {
                uploadBtn.prop('disabled', false).html(originalBtnHtml);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
            }
        });
    }

    $(document).on('click', '.spec-sec-remove-file-btn', function() {
        let fileBox = $(this).closest('.spec-sec-file-box');
        fileBox.find('.spec-sec-file-id').val('');
        fileBox.find('.spec-sec-file-name-val').val('');
        fileBox.find('.spec-sec-file-url-val').val('');
        fileBox.find('.spec-sec-file-input').val('');
        fileBox.find('.spec-sec-file-attached').addClass('d-none');
        fileBox.find('.spec-sec-file-uploader').removeClass('d-none');
        triggerAutoSave();
    });

    // --- GOVT Section 2 (Item 6) Dynamic Rows & File Upload ---
    $(document).on('click', '.btn-add-govt-entry', function() {
        let optKey = $(this).data('opt-key');
        let container = $('.govt-sec-entries-wrapper[data-opt-key="' + optKey + '"]');
        let rowCount = container.find('.govt-sec-entry-item').length;
        let newIdx = rowCount + 1;

        let newRow = $(`
            <div class="govt-sec-entry-item mb-2 p-2 border rounded bg-white shadow-sm" data-row-idx="\${rowCount}">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">
                                <i class="bi bi-pencil-square me-1"></i> <span class="entry-order-label">#\${newIdx}</span>
                            </span>
                            <input type="text" class="form-control form-control-sm govt-sec-detail-input" placeholder="ระบุรายละเอียด เช่น ชื่อกิจกรรม/โครงการ, คำสั่งแต่งตั้ง, ผลงาน...">
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-10">
                        <div class="govt-sec-file-box">
                            <div class="govt-sec-file-attached d-flex align-items-center justify-content-between p-1 px-2 border rounded bg-light d-none">
                                <div class="text-truncate me-2 small">
                                    <i class="bi bi-file-earmark-check text-success me-1"></i>
                                    <span class="govt-sec-file-name fw-bold text-dark"></span>
                                </div>
                                <div class="btn-group btn-group-sm flex-shrink-0">
                                    <button type="button" class="btn btn-xs btn-outline-info govt-sec-preview-btn" 
                                            data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                            onclick="previewEvidenceFile(this)"
                                            data-url="" data-name="" data-is-image="0" data-is-pdf="0" title="พรีวิวหลักฐาน">
                                        <i class="bi bi-eye"></i> พรีวิว
                                    </button>
                                    <a href="#" target="_blank" class="btn btn-xs btn-outline-secondary govt-sec-download-btn" title="ดาวน์โหลด">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger govt-sec-remove-file-btn" title="ลบไฟล์แนบนี้">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="govt-sec-file-uploader">
                                <div class="input-group input-group-sm">
                                    <input type="file" class="form-control form-control-sm govt-sec-file-input" accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.xlsx">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-govt-upload-file">
                                        <i class="bi bi-upload me-1"></i> แนบไฟล์
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" class="govt-sec-file-id" value="">
                            <input type="hidden" class="govt-sec-file-name-val" value="">
                            <input type="hidden" class="govt-sec-file-url-val" value="">
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-govt-entry" title="ลบรายการนี้">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);

        container.append(newRow);
        container.find('.btn-remove-govt-entry').show();

        // Auto-check checkbox if unchecked
        let cb = $('#govt_sec_' + optKey);
        if (!cb.prop('checked')) {
            cb.prop('checked', true).trigger('change');
        }

        triggerAutoSave();
    });

    $(document).on('click', '.btn-remove-govt-entry', function() {
        let container = $(this).closest('.govt-sec-entries-wrapper');
        $(this).closest('.govt-sec-entry-item').remove();

        // Re-index labels
        container.find('.govt-sec-entry-item').each(function(idx) {
            $(this).find('.entry-order-label').text('#' + (idx + 1));
        });

        if (container.find('.govt-sec-entry-item').length <= 1) {
            container.find('.btn-remove-govt-entry').hide();
        }

        triggerAutoSave();
    });

    $(document).on('input change', '.govt-sec-detail-input', function() {
        let optKey = $(this).closest('.govt-sec-entries-wrapper').data('opt-key');
        let cb = $('#govt_sec_' + optKey);
        if (!cb.prop('checked') && $(this).val().trim() !== '') {
            cb.prop('checked', true).trigger('change');
        }
        triggerAutoSave();
    });

    $(document).on('click', '.btn-govt-upload-file', function(e) {
        e.preventDefault();
        let fileInput = $(this).closest('.govt-sec-file-uploader').find('.govt-sec-file-input');
        if (!fileInput[0].files || fileInput[0].files.length === 0) {
            fileInput.trigger('click');
            return;
        }
        uploadGovtFile(fileInput);
    });

    $(document).on('change', '.govt-sec-file-input', function() {
        if (this.files && this.files.length > 0) {
            uploadGovtFile($(this));
        }
    });

    function uploadGovtFile(fileInput) {
        let file = fileInput[0].files[0];
        if (!file) return;

        let itemRow = fileInput.closest('.govt-sec-entry-item');
        let fileBox = itemRow.find('.govt-sec-file-box');
        let optKey = itemRow.closest('.govt-sec-entries-wrapper').data('opt-key');
        let uploadBtn = fileBox.find('.btn-govt-upload-file');
        let originalBtnHtml = uploadBtn.html();

        uploadBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังแนบ...');

        let formData = new FormData();
        formData.append('evidence_file', file);
        formData.append('evaluation_id', '{$evaluationId}');
        formData.append('evaluation_item_id', '{$govtSecItemId}');
        formData.append('description', 'หลักฐานข้อ ๖ ข้อ ' + optKey);
        formData.append('{$csrfParam}', '{$csrfToken}');

        $.ajax({
            url: '{$uploadUrl}',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                uploadBtn.prop('disabled', false).html(originalBtnHtml);
                if (res.success && res.file) {
                    let f = res.file;
                    let ext = f.name.split('.').pop().toLowerCase();
                    let isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1 ? '1' : '0';
                    let isPdf = ext === 'pdf' ? '1' : '0';

                    fileBox.find('.govt-sec-file-id').val(f.id);
                    fileBox.find('.govt-sec-file-name-val').val(f.name);
                    fileBox.find('.govt-sec-file-url-val').val(f.url);

                    fileBox.find('.govt-sec-file-name').text(f.name).attr('title', f.name);
                    fileBox.find('.govt-sec-preview-btn')
                        .attr('data-url', f.url)
                        .attr('data-name', f.name)
                        .attr('data-is-image', isImage)
                        .attr('data-is-pdf', isPdf);
                    fileBox.find('.govt-sec-download-btn').attr('href', f.url);

                    fileBox.find('.govt-sec-file-uploader').addClass('d-none');
                    fileBox.find('.govt-sec-file-attached').removeClass('d-none');

                    // Auto-check checkbox if unchecked
                    let cb = $('#govt_sec_' + optKey);
                    if (!cb.prop('checked')) {
                        cb.prop('checked', true).trigger('change');
                    }

                    // Append to evidence table bottom
                    let emptyRow = $('#empty-file-row');
                    if (emptyRow.length) emptyRow.remove();
                    let tbody = $('#evidence-table-body');
                    let count = tbody.find('tr').length + 1;
                    tbody.append(`
                        <tr>
                            <td class="text-center">\${count}</td>
                            <td><strong>หลักฐานข้อ ๖ ข้อ \${optKey}</strong></td>
                            <td><a href="\${f.url}" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark-arrow-down text-primary me-1"></i> \${f.name}</a></td>
                            <td class="text-center">\${f.size}</td>
                            <td class="text-center text-muted small">เมื่อสักครู่</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-info me-1 btn-preview-evidence shadow-sm"
                                        data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                        onclick="previewEvidenceFile(this)"
                                        data-url="\${f.url}" data-name="\${f.name}" data-is-image="\${isImage}" data-is-pdf="\${isPdf}">
                                    <i class="bi bi-eye"></i> พรีวิว
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-file" data-file-id="\${f.id}"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `);

                    triggerAutoSave();
                } else {
                    alert(res.message || 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
                }
            },
            error: function() {
                uploadBtn.prop('disabled', false).html(originalBtnHtml);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
            }
        });
    }

    $(document).on('click', '.govt-sec-remove-file-btn', function() {
        let fileBox = $(this).closest('.govt-sec-file-box');
        fileBox.find('.govt-sec-file-id').val('');
        fileBox.find('.govt-sec-file-name-val').val('');
        fileBox.find('.govt-sec-file-url-val').val('');
        fileBox.find('.govt-sec-file-input').val('');
        fileBox.find('.govt-sec-file-attached').addClass('d-none');
        fileBox.find('.govt-sec-file-uploader').removeClass('d-none');
        triggerAutoSave();
    });

    function calculateMainWorkWeight() {
        let total = 0;
        $('.main-weight-input').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        return total;
    }

    function recalculateAll() {
        // Civil / Univ
        let totalMainWeight = 0;
        let totalMainWeightedScore = 0;
        $('.main-work-row').each(function(idx) {
            $(this).find('.row-index').text(idx + 1);
            let w = parseFloat($(this).find('.main-weight-input').val()) || 0;
            let rawSc = parseFloat($(this).find('.main-score-select').val());
            let sc = isNaN(rawSc) ? 5 : Math.round(rawSc * 10) / 10;
            let weighted = (sc / 5.0) * w;
            $(this).find('.main-raw-score').text(sc);
            $(this).find('.row-weighted-score').text(weighted.toFixed(2));
            totalMainWeight += w;
            totalMainWeightedScore += weighted;

            // Target level highlight
            let score = Math.min(5, Math.max(1, Math.floor(sc)));
            for (let s = 1; s <= 5; s++) {
                let cell = $(this).find('.main-tgt-' + s);
                if (s === score) {
                    cell.html('<span class="badge bg-primary">' + s + '</span>');
                } else {
                    cell.html('<span class="text-muted">' + s + '</span>');
                }
            }

            // Dynamically highlight corresponding PDCA step line in green
            $(this).find('.pdca-line').each(function() {
                let lvl = parseInt($(this).data('level'));
                if (lvl === score) {
                    $(this).removeClass('text-muted').addClass('bg-success-subtle text-success fw-bold border border-success-subtle');
                } else {
                    $(this).removeClass('bg-success-subtle text-success fw-bold border border-success-subtle').addClass('text-muted');
                }
            });
        });
        $('#footer-main-weight').text(totalMainWeight);
        $('#footer-main-weighted-sum').text(totalMainWeightedScore.toFixed(2));

        // Policy Checklist (5.1)
        let policyCheckedCount = $('.secondary-checkbox:checked').length;
        let policyScore = 0;
        if (policyCheckedCount >= 3) policyScore = 5;
        else if (policyCheckedCount === 2) policyScore = 3;
        else if (policyCheckedCount === 1) policyScore = 1;
        let policyWeighted = (policyScore / 5.0) * 15.0;

        $('#policy-count-badge').text(policyCheckedCount);
        $('#policy-self-score-badge, #policy-score-num').text(policyScore);
        $('#policy-weighted-num').text(policyWeighted.toFixed(2));
        for (let s = 1; s <= 5; s++) {
            let cell = $('.policy-tgt-' + s);
            if (s === policyScore) {
                cell.html('<span class="badge bg-primary">' + s + '</span>');
            } else {
                cell.html('<span class="text-muted">' + s + '</span>');
            }
        }

        // Academic Progress (5.2)
        let acadVal = $('.acad-radio:checked').length ? parseInt($('.acad-radio:checked').val()) : 0;
        let acadWeighted = (acadVal / 5.0) * 5.0;
        $('#acad-self-score-badge, #acad-score-num').text(acadVal);
        $('#acad-weighted-num').text(acadWeighted.toFixed(2));
        for (let s = 1; s <= 5; s++) {
            let cell = $('.acad-tgt-' + s);
            if (s === acadVal) {
                cell.html('<span class="badge bg-primary">' + s + '</span>');
            } else {
                cell.html('<span class="text-muted">' + s + '</span>');
            }
        }

        // Summary Footers
        let secSum = policyWeighted + acadWeighted;
        let totalPerfSum = totalMainWeightedScore + secSum;
        let totalTableWeight = totalMainWeight + 20;
        let perf80 = (totalPerfSum / 100.0) * {$perfWeightVal};

        $('#footer-main-weight').text(totalMainWeight);
        $('#footer-sec-weighted-sum').text(secSum.toFixed(2));
        $('#footer-total-weight, #header-total-weight').text(totalTableWeight);
        $('#footer-total-perf-sum').text(totalPerfSum.toFixed(2));
        $('#footer-total-perf-80').text(perf80.toFixed(2));

        // Competencies (Civil / Univ GAP Engine)
        let countGe = 0;
        let countM1 = 0;
        let countM2 = 0;
        let countM3 = 0;
        let totalComps = 0;

        $('.comp-level-select').each(function() {
            let actual = parseInt($(this).val()) || 3;
            let expected = parseInt($(this).data('expected')) || 3;
            let diff = actual - expected;
            let tr = $(this).closest('tr');
            let gapDisplay = tr.find('.comp-gap-display');
            let gapInput = tr.find('.comp-gap-input');
            
            let badgeClass = 'bg-success';
            let text = (diff > 0 ? '+' + diff : diff);
            if (diff === -1) {
                badgeClass = 'bg-warning text-dark';
            } else if (diff < -1) {
                badgeClass = 'bg-danger text-white';
            }
            gapDisplay.html('<span class="badge ' + badgeClass + '">' + text + '</span>');
            gapInput.val(text);

            if (diff >= 0) countGe++;
            else if (diff === -1) countM1++;
            else if (diff === -2) countM2++;
            else countM3++;
            totalComps++;
        });

        let ptsGe = countGe * 3;
        let ptsM1 = countM1 * 2;
        let ptsM2 = countM2 * 1;
        let ptsM3 = countM3 * 0;
        let totalGapSum = ptsGe + ptsM1 + ptsM2 + ptsM3;

        $('#gap-count-ge').text(countGe);
        $('#gap-pts-ge').text(ptsGe);
        $('#gap-count-m1').text(countM1);
        $('#gap-pts-m1').text(ptsM1);
        $('#gap-count-m2').text(countM2);
        $('#gap-pts-m2').text(ptsM2);
        $('#gap-count-m3').text(countM3);
        $('#gap-pts-m3').text(ptsM3);
        $('#gap-total-sum').text(totalGapSum);

        if (totalComps > 0) {
            let maxPossible = totalComps * 3.0;
            let ratio = (totalGapSum / maxPossible) * 100.0;
            let finalWeighted = (totalGapSum / maxPossible) * {$compWeightVal};
            $('#gap-ratio-score').text(ratio.toFixed(2));
            $('#gap-weighted-final').text(finalWeighted.toFixed(2));
            $('#total-comp-score').text(finalWeighted.toFixed(2));
        }

        // Step 1: แบบสรุป ปม. Live Score & Level Sync (CIVIL & UNIVERSITY)
        let compRatio = (totalComps > 0) ? (totalGapSum / (totalComps * 3.0)) * 100.0 : 100.0;
        let compWeighted20 = (totalComps > 0) ? (totalGapSum / (totalComps * 3.0)) * {$compWeightVal} : {$compWeightVal};
        let grandTotal = perf80 + compWeighted20;

        $('#summary-perf-score').text(totalPerfSum.toFixed(2));
        $('#summary-perf-weighted').text(perf80.toFixed(2));
        $('#summary-comp-score').text(compRatio.toFixed(2));
        $('#summary-comp-weighted').text(compWeighted20.toFixed(2));
        $('#summary-total-score').text(grandTotal.toFixed(2));

        // Sync Evaluation Grade Radio & Badges in Step 1
        $('.grade-check-box').removeClass('active-grade');
        $('input[name="summary_grade_radio"]').prop('checked', false);

        if (grandTotal >= 90.0) {
            $('#grade_excellent').prop('checked', true);
            $('#grade-box-excellent').addClass('active-grade');
        } else if (grandTotal >= 80.0) {
            $('#grade_verygood').prop('checked', true);
            $('#grade-box-verygood').addClass('active-grade');
        } else if (grandTotal >= 70.0) {
            $('#grade_good').prop('checked', true);
            $('#grade-box-good').addClass('active-grade');
        } else if (grandTotal >= 60.0) {
            $('#grade_fair').prop('checked', true);
            $('#grade-box-fair').addClass('active-grade');
        } else {
            $('#grade_poor').prop('checked', true);
            $('#grade-box-poor').addClass('active-grade');
        }

        // GOVT Main Work
        let govtTotalWeight = 0;
        let govtTotalWeightedScore = 0;
        $('.govt-main-work-row').each(function() {
            let k1 = parseFloat($(this).find('.govt-kpi-volume').val()) || 5;
            let k2 = parseFloat($(this).find('.govt-kpi-quality').val()) || 5;
            let k3 = parseFloat($(this).find('.govt-kpi-timeliness').val()) || 5;
            let k4 = parseFloat($(this).find('.govt-kpi-resource').val()) || 5;
            let gw = parseFloat($(this).find('.govt-work-weight').val()) || 0;
            let itemScore = ((k1 * 25) + (k2 * 25) + (k3 * 15) + (k4 * 15)) / 80.0;
            let weighted = (itemScore / 5.0) * gw;
            $(this).find('.govt-row-self-score').text('ระดับ ' + itemScore.toFixed(1));
            $(this).find('.govt-row-weighted').text(weighted.toFixed(2));

            let rounded = Math.round(itemScore);
            for (let s = 1; s <= 5; s++) {
                let cell = $(this).find('.govt-tgt-' + s);
                if (s === rounded) {
                    cell.html('<span class="badge bg-primary">' + s + '</span>');
                } else {
                    cell.html('<span class="text-muted">' + s + '</span>');
                }
            }

            govtTotalWeight += gw;
            govtTotalWeightedScore += weighted;
        });
        $('#govt-total-weight-badge').text(govtTotalWeight + 20);
        $('#govt-footer-total-weight').text(govtTotalWeight);
        $('#govt-main-weighted-sum').text(govtTotalWeightedScore.toFixed(2));

        // GOVT Secondary Work (4 KPIs evaluation like main work)
        let govtSecCount = $('.govt-sec-checkbox:checked').length;
        $('#govt-sec-badge-count, #govt-sec-count-num').text(govtSecCount);

        let sk1 = parseFloat($('#govt-sec-kpi-vol').val()) || 5;
        let sk2 = parseFloat($('#govt-sec-kpi-qua').val()) || 5;
        let sk3 = parseFloat($('#govt-sec-kpi-time').val()) || 5;
        let sk4 = parseFloat($('#govt-sec-kpi-res').val()) || 5;
        let secItemScore = ((sk1 * 25) + (sk2 * 25) + (sk3 * 15) + (sk4 * 15)) / 80.0;
        let govtSecWeighted = (secItemScore / 5.0) * 20.0;

        $('#govt-sec-self-badge').text('ระดับ ' + secItemScore.toFixed(1));
        $('#govt-sec-score-display, #govt-sec-score-sum').text(govtSecWeighted.toFixed(2));

        let secRounded = Math.round(secItemScore);
        for (let s = 1; s <= 5; s++) {
            let cell = $('.govt-sec-tgt-' + s);
            if (s === secRounded) {
                cell.html('<span class="badge bg-primary">' + s + '</span>');
            } else {
                cell.html('<span class="text-muted">' + s + '</span>');
            }
        }

        // Total Performance (Main 80 + Sec 20 = Max 100)
        let govtPerfScore = govtTotalWeightedScore + govtSecWeighted;
        $('#govt-perf-total-sum').text(govtPerfScore.toFixed(2));

        // GOVT Behaviors (5 competencies, max 25)
        let govtBehSum = 0;
        $('.govt-beh-select').each(function() {
            govtBehSum += parseInt($(this).val()) || 3;
        });
        let govtBehScore100 = (govtBehSum / 25.0) * 100.0;
        $('#govt-beh-percent-sum').text(govtBehScore100.toFixed(2));

        // Section 4 Summary Calculation
        let govtPerfWeighted80 = (govtPerfScore / 100.0) * {$perfWeightVal};
        let govtBehWeighted20 = (govtBehSum / 25.0) * {$compWeightVal};
        let govtFinalTotal = govtPerfWeighted80 + govtBehWeighted20;

        $('#govt-summary-main-score').text(govtTotalWeightedScore.toFixed(2));
        $('#govt-summary-sec-score').text(govtSecWeighted.toFixed(2));
        $('#govt-summary-perf-score').text(govtPerfScore.toFixed(2));
        $('#govt-summary-perf-weighted').text(govtPerfWeighted80.toFixed(2));
        $('#govt-summary-beh-score').text(govtBehScore100.toFixed(2));
        $('#govt-summary-beh-weighted').text(govtBehWeighted20.toFixed(2));
        $('#govt-summary-total').text(govtFinalTotal.toFixed(2));

        let govtLevel = 'ต้องปรับปรุง';
        if (govtFinalTotal >= 95.0) govtLevel = 'ดีเด่น';
        else if (govtFinalTotal >= 85.0) govtLevel = 'ดีมาก';
        else if (govtFinalTotal >= 75.0) govtLevel = 'ดี';
        else if (govtFinalTotal >= 65.0) govtLevel = 'พอใช้';
        $('#govt-summary-level').text(govtLevel);

        // SPECIAL Section 1 (55 pts) & Section 2 (45 pts)
        let specSec1DirectSum = 0;
        $('.spec-sec1-score-input').each(function() {
            specSec1DirectSum += parseFloat($(this).val()) || 0;
        });
        $('#spec-sec1-direct-sum').text(specSec1DirectSum.toFixed(2));

        let specSecCount = $('.spec-sec-checkbox:checked').length;
        let specSecPts = 0;
        if (specSecCount >= 6) specSecPts = 5;
        else if (specSecCount === 5) specSecPts = 4;
        else if (specSecCount >= 3) specSecPts = 3;
        else if (specSecCount === 2) specSecPts = 2;
        else if (specSecCount === 1) specSecPts = 1;

        $('#spec-sec-count-badge').text(specSecCount);
        $('#spec-sec-points-badge').text(specSecPts);

        let specSec1Sum = specSec1DirectSum + specSecPts;
        $('#spec-sec1-sum').text(specSec1Sum.toFixed(2));

        let specSec2Sum = 0;
        $('.spec-sec2-score-input').each(function() {
            specSec2Sum += parseFloat($(this).val()) || 0;
        });
        $('#spec-sec2-sum').text(specSec2Sum.toFixed(2));
    }

    // Auto Save Function
    function triggerAutoSave(isManual = false) {
        clearTimeout(autoSaveTimer);
        $('#save-status').html('<span class="text-warning"><i class="bi bi-arrow-repeat spin me-1"></i> กำลังบันทึกข้อมูล...</span>');

        autoSaveTimer = setTimeout(function() {
            saveFormData();
        }, isManual ? 0 : 1500);
    }

    function saveFormData() {
        let answers = {};
        let competencies = {};

        // 1. Civil/Univ Main work rows
        let mainWorkRows = [];
        $('.main-work-row').each(function() {
            let title = $(this).find('.main-work-title').val();
            let kpi = $(this).find('.main-work-kpi').val() || '';
            let score = $(this).find('.main-score-select').val();
            let weight = $(this).find('.main-weight-input').val();
            mainWorkRows.push({
                title: title,
                kpi: kpi,
                self_score: score,
                weight: weight
            });
        });

        let mainItemId = "{$mainItemId}";
        if (mainItemId && mainItemId !== "0" && mainWorkRows.length > 0) {
            answers[mainItemId] = mainWorkRows;
        }

        // 2. GOVT Main work rows
        let govtMainRows = [];
        $('.govt-main-work-row').each(function() {
            let title = $(this).find('.govt-work-title').val();
            let k1 = $(this).find('.govt-kpi-volume').val();
            let k2 = $(this).find('.govt-kpi-quality').val();
            let k3 = $(this).find('.govt-kpi-timeliness').val();
            let k4 = $(this).find('.govt-kpi-resource').val();
            let weight = $(this).find('.govt-work-weight').val();
            govtMainRows.push({
                title: title,
                kpi_volume: k1,
                kpi_quality: k2,
                kpi_timeliness: k3,
                kpi_resource: k4,
                weight: weight
            });
        });

        let govtMainItemId = "{$govtMainItemId}";
        if (govtMainItemId && govtMainItemId !== "0" && govtMainRows.length > 0) {
            answers[govtMainItemId] = govtMainRows;
        }

        // 3. GOVT Secondary Checkboxes & Details
        let govtSecSelected = [];
        let govtSecDetails = {};
        $('.govt-sec-checkbox:checked').each(function() {
            let key = $(this).val();
            govtSecSelected.push(key);
        });

        $('.govt-sec-entries-wrapper').each(function() {
            let key = $(this).data('opt-key');
            let entries = [];
            $(this).find('.govt-sec-entry-item').each(function() {
                let detail = $(this).find('.govt-sec-detail-input').val() || '';
                let fileId = $(this).find('.govt-sec-file-id').val() || '';
                let fileName = $(this).find('.govt-sec-file-name-val').val() || '';
                let fileUrl = $(this).find('.govt-sec-file-url-val').val() || '';
                if (detail.trim() !== '' || fileId !== '') {
                    entries.push({
                        detail: detail,
                        file_id: fileId,
                        file_name: fileName,
                        file_url: fileUrl
                    });
                }
            });
            if (entries.length > 0) {
                govtSecDetails[key] = entries;
            }
        });

        let govtSecItemId = "{$govtSecItemId}";
        if (govtSecItemId && govtSecItemId !== "0") {
            answers[govtSecItemId] = {
                selected: govtSecSelected,
                details: govtSecDetails,
                kpi_volume: $('#govt-sec-kpi-vol').val() || 5,
                kpi_quality: $('#govt-sec-kpi-qua').val() || 5,
                kpi_timeliness: $('#govt-sec-kpi-time').val() || 5,
                kpi_resource: $('#govt-sec-kpi-res').val() || 5
            };
        }

        // 3.1 SPECIAL 1.6 Secondary Checkboxes & Details
        let specSecSelected = [];
        let specSecDetails = {};
        $('.spec-sec-checkbox:checked').each(function() {
            let key = $(this).val();
            specSecSelected.push(key);
        });

        $('.spec-sec-entries-wrapper').each(function() {
            let key = $(this).data('opt-key');
            if (specSecSelected.indexOf(String(key)) === -1 && specSecSelected.indexOf(Number(key)) === -1) {
                return;
            }
            let entries = [];
            $(this).find('.spec-sec-entry-item').each(function() {
                let detail = $(this).find('.spec-sec-detail-input').val() || '';
                let fileId = $(this).find('.spec-sec-file-id').val() || '';
                let fileName = $(this).find('.spec-sec-file-name-val').val() || '';
                let fileUrl = $(this).find('.spec-sec-file-url-val').val() || '';
                if (detail.trim() !== '' || fileId !== '') {
                    entries.push({
                        detail: detail,
                        file_id: fileId,
                        file_name: fileName,
                        file_url: fileUrl
                    });
                }
            });
            if (entries.length > 0) {
                specSecDetails[key] = entries;
            }
        });

        let spec16ItemId = "{$spec16ItemId}";
        if (spec16ItemId && spec16ItemId !== "0") {
            answers[spec16ItemId] = {
                selected: specSecSelected,
                details: specSecDetails
            };
        }

        // 3.2 Other Secondary Checkboxes (Civil)
        $('.secondary-checkbox:checked').each(function() {
            let name = $(this).attr('name');
            let match = name.match(/item_checkbox\\[(\\d+)\\]/);
            if (match) {
                let itemId = match[1];
                if (!answers[itemId]) answers[itemId] = [];
                answers[itemId].push($(this).val());
            }
        });

        // 4. Radio scale
        $('input[type=radio]:checked').each(function() {
            let name = $(this).attr('name');
            let match = name.match(/item_radio\\[(\\d+)\\]/);
            if (match) {
                answers[match[1]] = $(this).val();
            }
        });

        // 5. Other / Spec direct score inputs
        $('.spec-score-input, .other-score-input').each(function() {
            let itemId = $(this).data('item-id');
            if (itemId) {
                answers[itemId] = $(this).val();
            }
        });

        // 6. Competencies
        $('.comp-level-select, .govt-beh-select').each(function() {
            let compId = $(this).data('comp-id');
            let level = $(this).val();
            let tr = $(this).closest('tr');
            let gap = tr.find('.comp-gap-input').val();
            let importance = tr.find('.comp-importance-select').val();
            let idp = tr.find('.comp-idp-input').val();
            competencies[compId] = {
                level: level,
                gap: gap,
                importance: importance,
                idp: idp
            };
        });

        // 7. Form 1 Section 3: IDP rows
        let idpRows = [];
        $('#summary-idp-tbody .summary-idp-row').each(function() {
            let topic = $(this).find('.idp-topic-input').val() || '';
            let method = $(this).find('.idp-method-input').val() || '';
            let timeline = $(this).find('.idp-timeline-input').val() || '';
            if (topic.trim() !== '' || method.trim() !== '' || timeline.trim() !== '') {
                idpRows.push({
                    topic: topic,
                    method: method,
                    timeline: timeline
                });
            }
        });

        $.ajax({
            url: '{$autoSaveUrl}',
            type: 'POST',
            data: {
                evaluation_id: '{$evaluationId}',
                answered_by: 'self',
                answers: answers,
                competencies: competencies,
                idp_rows: idpRows,
                _csrf: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                $('#save-status').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> บันทึกข้อมูลอัตโนมัติแล้ว (' + new Date().toLocaleTimeString('th-TH') + ')</span>');
            },
            error: function() {
                $('#save-status').html('<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i> เกิดข้อผิดพลาดในการบันทึก</span>');
            }
        });
    }

    // Evidence Upload Ajax
    $('#evidence-upload-form').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $('#btn-upload-file').prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i>');

        $.ajax({
            url: '{$uploadUrl}',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#btn-upload-file').prop('disabled', false).html('<i class="bi bi-upload me-1"></i> อัปโหลด');
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.message || 'เกิดข้อผิดพลาดในการอัปโหลด');
                }
            },
            error: function() {
                $('#btn-upload-file').prop('disabled', false).html('<i class="bi bi-upload me-1"></i> อัปโหลด');
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        });
    });

    // Delete Evidence File
    $(document).on('click', '.btn-delete-file', function() {
        if (!confirm('ยืนยันการลบไฟล์หลักฐานนี้?')) return;
        let fileId = $(this).data('file-id');
        let tr = $(this).closest('tr');

        $.ajax({
            url: '{$deleteFileUrl}',
            type: 'POST',
            data: {
                id: fileId,
                _csrf: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if (res.success) {
                    tr.remove();
                }
            }
        });
    });
});
JS;
$this->registerJs($script);
?>
