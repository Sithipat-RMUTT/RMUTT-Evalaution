<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;
use common\models\PersonnelType;

/** @var yii\web\View $this */
/** @var common\models\Evaluation $evaluation */
/** @var common\models\Personnel $personnel */
/** @var common\models\TemplateVersion $templateVersion */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */
/** @var common\models\EvaluationAnswer[] $selfAnswers */
/** @var common\models\EvaluationAnswer[] $supAnswers */
/** @var common\models\EvaluationAnswer[] $l1Answers */
/** @var common\models\EvaluationAnswer[] $l2Answers */
/** @var common\models\EvaluationCompetencyAnswer[] $selfCompAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $supCompAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $l1CompAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $l2CompAnswers */
/** @var common\models\EvidenceFile[] $evidenceFiles */
/** @var common\models\EvaluationResult $result */
/** @var bool $isOwner */

$this->title = 'ผลการประเมิน: ' . $personnel->fullName;
$details = $result ? $result->calculation_details : [];
$typeCode = $personnel->personnelType->code;
$isSpecial = ($typeCode === 'SPECIAL');
$isGovt = ($typeCode === 'GOVT');
$isCivil = ($typeCode === 'CIVIL');
$isUniv = ($typeCode === 'UNIVERSITY');

$toTh = function($str) {
    return str_replace(['0','1','2','3','4','5','6','7','8','9'], ['๐','๑','๒','๓','๔','๕','๖','๗','๘','๙'], (string)$str);
};

$cycleNum = $evaluation->cycle ? intval($evaluation->cycle->cycle_number) : 1;
$fiscalYear = $evaluation->cycle ? intval($evaluation->cycle->fiscal_year) : (intval(date('Y')) + 543);
$prevYear = $fiscalYear - 1;

$docCode1 = $isCivil ? 'แบบสรุป ปร.' : ($isUniv ? 'แบบสรุป ปม.' : ($isGovt ? 'แบบสรุป พรก.' : 'แบบสรุป พรพ.'));
$docCode2 = ($isCivil || $isUniv) ? 'แบบ ป.ผ.' : ($isGovt ? 'ส่วนที่ ๒ (ผลสัมฤทธิ์)' : 'ด้านที่ ๑ (ผลงาน)');
$docCode3 = ($isCivil || $isUniv) ? 'แบบ พม.' : ($isGovt ? 'ส่วนที่ ๓ (พฤติกรรม)' : 'ด้านที่ ๒ (คุณลักษณะ)');

$docTitle1 = $isCivil 
    ? 'แบบสรุปการประเมินผลการปฏิบัติราชการของข้าราชการพลเรือนในสถาบันอุดมศึกษา' 
    : ($isUniv 
        ? 'แบบสรุปการประเมินผลการปฏิบัติราชการของพนักงานมหาวิทยาลัย' 
        : ($isGovt 
            ? 'แบบประเมินผลการปฏิบัติงานพนักงานราชการทั่วไป (แบบสรุปผลการประเมิน)' 
            : 'แบบประเมินผลการปฏิบัติงานพนักงานพิเศษเงินรายได้ (แบบสรุปผลการประเมิน)'));

$docTitle2 = ($isCivil || $isUniv)
    ? 'แบบข้อตกลงการประเมินผลสัมฤทธิ์ของงาน' . ($isUniv ? 'ของพนักงานมหาวิทยาลัย' : 'ของข้าราชการพลเรือน') . ' (แบบ ป.ผ.)'
    : ($isGovt 
        ? 'การประเมินผลสัมฤทธิ์ของงานพนักงานราชการทั่วไป (ส่วนที่ ๒)' 
        : 'ผลการประเมินด้านที่ ๑ ผลสัมฤทธิ์ของงาน (พนักงานพิเศษเงินรายได้)');

$docTitle3 = ($isCivil || $isUniv)
    ? 'แบบข้อตกลงการประเมินพฤติกรรมการปฏิบัติราชการหรือสมรรถนะ (แบบ พม.)'
    : ($isGovt 
        ? 'การประเมินพฤติกรรมการปฏิบัติงาน / สมรรถนะ (ส่วนที่ ๓)' 
        : 'ผลการประเมินด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (พนักงานพิเศษเงินรายได้)');

$evaluatorL1Name = $evaluation->evaluatorL1 ? $evaluation->evaluatorL1->fullName : ($personnel->supervisor ? $personnel->supervisor->fullName : ($evaluation->evaluator ? $evaluation->evaluator->fullName : '-'));
$evaluatorL1Pos = ($evaluation->evaluatorL1 && $evaluation->evaluatorL1->position) ? $evaluation->evaluatorL1->position->name_th : (($personnel->supervisor && $personnel->supervisor->position) ? $personnel->supervisor->position->name_th : '-');

$evaluatorL2Name = $evaluation->evaluatorL2 ? $evaluation->evaluatorL2->fullName : ($personnel->divisionHead ? $personnel->divisionHead->fullName : '...................................................');
$evaluatorL2Pos = ($evaluation->evaluatorL2 && $evaluation->evaluatorL2->position) ? $evaluation->evaluatorL2->position->name_th : (($personnel->divisionHead && $personnel->divisionHead->position) ? $personnel->divisionHead->position->name_th : '...................................................');

// Scores calculation
$isUnivOrGovt = ($isUniv || $isGovt || $isCivil);
$perfWeight = 70.0;
$compWeight = 30.0;
$perfWeightTh = '๗๐%';
$compWeightTh = '๓๐%';

$perfScoreFinal = $result ? floatval($result->supervisor_performance_score) : 0.0;
$perfScore100 = ($perfScoreFinal / $perfWeight) * 100.0;
if ($isSpecial) {
    $perfScore100 = $result ? floatval($result->supervisor_performance_score) : 0.0;
}

$compScoreFinal = $result ? floatval($result->supervisor_competency_score) : 0.0;
$compScore100 = ($compScoreFinal / $compWeight) * 100.0;
if ($isSpecial) {
    $compScore100 = $result ? floatval($result->supervisor_competency_score) : 0.0;
}

$finalScore = $result ? floatval($result->final_percentage) : ($perfScoreFinal + $compScoreFinal);
$perfLevel = $result ? $result->performance_level : '';

$isAcknowledged = !empty($evaluation->acknowledgement_at);
$isL1Evaluated = !empty($evaluation->l1_evaluated_at) || !empty($evaluation->completed_at) || $evaluation->status === Evaluation::STATUS_SUBMITTED_L2 || $evaluation->status === Evaluation::STATUS_COMPLETED;
$isL2Evaluated = !empty($evaluation->l2_evaluated_at) || !empty($evaluation->completed_at) || $evaluation->status === Evaluation::STATUS_COMPLETED;
$isEvaluated = $isL1Evaluated || $isL2Evaluated || ($evaluation->status === Evaluation::STATUS_COMPLETED) || ($evaluation->status === Evaluation::STATUS_ACKNOWLEDGED);
?>

<!-- Embedded Official Thai Government & Print CSS -->
<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap');

    .official-doc {
        font-family: 'Sarabun', 'TH Sarabun New', 'THSarabunPSK', sans-serif;
        color: #000;
        font-size: 15px;
        line-height: 1.45;
    }
    .official-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    .official-table th, .official-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        vertical-align: middle;
    }
    .official-table th {
        background-color: #f1f5f9;
        font-weight: bold;
        text-align: center;
    }
    .official-header-title {
        font-size: 19px;
        font-weight: bold;
    }
    .official-header-sub {
        font-size: 15px;
        font-weight: bold;
    }
    .doc-section-title {
        background-color: #f8fafc;
        border-left: 4px solid #0d6efd;
        padding: 7px 12px;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 12px;
        border-radius: 0 4px 4px 0;
        border-top: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }
    .eval-view-nav .nav-link {
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        background-color: #fff;
        color: #334155;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .eval-view-nav .nav-link:hover {
        background-color: #f1f5f9;
        border-color: #0d6efd;
    }
    .eval-view-nav .nav-link.active {
        background-color: #e0edff;
        border-color: #0d6efd;
        color: #0d6efd;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.15);
    }
    .eval-view-nav .step-badge {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: bold;
    }
    .grade-badge-box {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 6px;
        background-color: #fff;
        transition: all 0.2s ease;
    }
    .grade-badge-box.active-grade {
        border: 2px solid #0d6efd !important;
        background-color: #eff6ff !important;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2);
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            background-color: #fff !important;
            color: #000 !important;
            font-family: 'Sarabun', 'TH Sarabun New', 'THSarabunPSK', sans-serif !important;
            font-size: 13.5px !important;
        }
        .d-print-none, .navbar, .footer, header, footer, #evalViewNavCard, .btn, .alert-dismissible {
            display: none !important;
        }
        .container, .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .card-rmutt, .official-card-wrap {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .official-doc {
            font-size: 13.5px !important;
        }
        .official-table th {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .eval-form-pane {
            display: block !important;
        }
        .form-divider-break {
            display: none !important;
        }
        .page-break {
            page-break-after: always !important;
            break-after: page !important;
        }
        .page-break-avoid {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .grade-badge-box.active-grade {
            border: 2px solid #000 !important;
            background-color: #eee !important;
        }
    }
</style>

<div class="evaluation-view py-3 official-doc">

    <!-- Action Bar (Hidden on Print) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 d-print-none">
        <div class="d-flex align-items-center gap-2">
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i> กลับหน้ารายการ', ['/evaluation/index'], ['class' => 'btn btn-outline-secondary']) ?>
            <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm"><?= Html::encode($personnel->personnelType->name_th) ?></span>
            <?= $evaluation->getStatusBadge('fs-6 px-3 py-2 shadow-sm') ?>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary shadow-sm" onclick="printOfficialForms();">
                <i class="bi bi-printer-fill me-1"></i> พิมพ์แบบประเมินทางการครบทั้ง ๓ แบบฟอร์ม (Print / PDF)
            </button>
            <?php if ($isOwner && $evaluation->status === Evaluation::STATUS_COMPLETED && empty($evaluation->acknowledgement_at)): ?>
                <?= Html::beginForm(['/evaluation/acknowledge', 'id' => $evaluation->id], 'post', ['class' => 'd-inline']) ?>
                    <button type="submit" class="btn btn-success shadow-sm fw-bold" onclick="return confirm('ยืนยันการรับทราบผลการประเมินการปฏิบัติราชการและแผนพัฒนา?');">
                        <i class="bi bi-check2-circle me-1"></i> ข้าพเจ้าได้รับทราบผลการประเมินแล้ว
                    </button>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wizard Tab Navigation Pills (Hidden on Print) -->
    <div class="card card-rmutt shadow-sm mb-4 border-0 d-print-none" id="evalViewNavCard">
        <div class="card-body p-2 bg-light rounded-3 border">
            <ul class="nav nav-pills nav-fill gap-2 eval-view-nav" id="evalViewTabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active eval-view-btn text-start p-3 border shadow-sm w-100" id="tab-btn-1" onclick="switchViewForm(1);">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary text-white rounded-circle me-3 step-badge">๑</span>
                            <div>
                                <div class="fw-bold fs-6 step-title">๑. แบบสรุปผลการประเมิน (<?= $docCode1 ?>)</div>
                                <small class="text-muted d-block">ข้อมูลทั่วไป &bull; สรุปผลคะแนน &bull; แผนพัฒนา (IDP) &bull; ลงนาม</small>
                            </div>
                        </div>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link eval-view-btn text-start p-3 border shadow-sm w-100" id="tab-btn-2" onclick="switchViewForm(2);">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-secondary text-white rounded-circle me-3 step-badge">๒</span>
                            <div>
                                <div class="fw-bold fs-6 step-title">๒. แบบผลสัมฤทธิ์ของงาน (<?= $docCode2 ?>)</div>
                                <small class="text-muted d-block">ภาระงานหลัก &bull; งานนโยบาย &bull; คู่มือปฏิบัติงาน &bull; ความเห็น</small>
                            </div>
                        </div>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link eval-view-btn text-start p-3 border shadow-sm w-100" id="tab-btn-3" onclick="switchViewForm(3);">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-secondary text-white rounded-circle me-3 step-badge">๓</span>
                            <div>
                                <div class="fw-bold fs-6 step-title">๓. แบบประเมินสมรรถนะ (<?= $docCode3 ?>)</div>
                                <small class="text-muted d-block">สมรรถนะหลัก &bull; สายงาน &bull; เอกสารแนบ</small>
                            </div>
                        </div>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link eval-view-btn text-start p-3 border shadow-sm w-100" id="tab-btn-all" onclick="switchViewForm('all');">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-dark text-white rounded-circle me-3 step-badge"><i class="bi bi-file-earmark-text"></i></span>
                            <div>
                                <div class="fw-bold fs-6 step-title">แสดงทั้ง ๓ แบบฟอร์มต่อเนื่อง</div>
                                <small class="text-muted d-block">เรียงแบบฟอร์ม ๑ &rarr; ๒ &rarr; ๓ แบบสมบูรณ์ (พร้อมพิมพ์)</small>
                            </div>
                        </div>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- แบบฟอร์มที่ ๑ : แบบสรุปผลการประเมิน (แบบสรุป ปม. / ปร.)                    -->
    <!-- ========================================================================= -->
    <div class="eval-form-pane" id="form-pane-1">
        <div class="card card-rmutt official-card-wrap p-4 p-md-5 shadow-sm bg-white border mb-4">

            <!-- Form 1 Header -->
            <div class="d-flex justify-content-end mb-2">
                <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm"><?= $docCode1 ?></span>
            </div>
            <div class="text-center mb-4 pb-2 border-bottom border-dark border-2">
                <div class="official-header-title text-uppercase"><?= $docTitle1 ?></div>
                <div class="official-header-sub mt-1">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</div>
                <div class="fw-bold mt-1 text-secondary"><?= Html::encode($evaluation->cycle->name_th) ?></div>
            </div>

            <!-- ส่วนที่ ๑ : ข้อมูลของผู้รับการประเมิน -->
            <div class="mb-4 page-break-avoid">
                <div class="doc-section-title">
                    <i class="bi bi-person-lines-fill me-1 text-primary"></i> ส่วนที่ ๑ : ข้อมูลของผู้รับการประเมินและผู้บังคับบัญชา
                </div>

                <!-- รอบการประเมิน -->
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="fw-bold text-dark mb-2">รอบการประเมิน :</div>
                    <div class="ps-2">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="view_cycle_radio" id="view_cycle_1" <?= $cycleNum == 1 ? 'checked' : '' ?> disabled>
                            <label class="form-check-label fw-bold <?= $cycleNum == 1 ? 'text-primary' : 'text-muted' ?>" for="view_cycle_1">
                                รอบที่ ๑ : ๑ ตุลาคม <?= $toTh($prevYear) ?> ถึง ๓๑ มีนาคม <?= $toTh($fiscalYear) ?>
                                <?= $cycleNum == 1 ? '<span class="badge bg-success ms-2">รอบการประเมินนี้</span>' : '' ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="view_cycle_radio" id="view_cycle_2" <?= $cycleNum == 2 ? 'checked' : '' ?> disabled>
                            <label class="form-check-label fw-bold <?= $cycleNum == 2 ? 'text-primary' : 'text-muted' ?>" for="view_cycle_2">
                                รอบที่ ๒ : ๑ เมษายน <?= $toTh($fiscalYear) ?> ถึง ๓๐ กันยายน <?= $toTh($fiscalYear) ?>
                                <?= $cycleNum == 2 ? '<span class="badge bg-success ms-2">รอบการประเมินนี้</span>' : '' ?>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ตารางข้อมูลบุคลากร -->
                <table class="official-table">
                    <tr>
                        <td style="width: 20%;" class="fw-bold bg-light">ชื่อผู้รับการประเมิน</td>
                        <td style="width: 30%;"><?= Html::encode($personnel->fullName) ?></td>
                        <td style="width: 20%;" class="fw-bold bg-light">ตำแหน่ง / ระดับ</td>
                        <td style="width: 30%;"><?= Html::encode($personnel->position->name_th) ?><?= !empty($personnel->position->level_label) ? ' (' . Html::encode($personnel->position->level_label) . ')' : '' ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">ฝ่าย / สังกัด</td>
                        <td><?= Html::encode($personnel->department->name_th) ?></td>
                        <td class="fw-bold bg-light">ประเภทบุคลากร</td>
                        <td><?= Html::encode($personnel->personnelType->name_th) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">ผู้ประเมินขั้นต้น (หัวหน้างาน L1)</td>
                        <td><?= Html::encode($evaluatorL1Name) ?></td>
                        <td class="fw-bold bg-light">ตำแหน่งผู้ประเมิน L1</td>
                        <td><?= Html::encode($evaluatorL1Pos) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">ผู้ประเมินตัดสิน (หัวหน้าฝ่าย L2)</td>
                        <td><?= Html::encode($evaluatorL2Name) ?></td>
                        <td class="fw-bold bg-light">ตำแหน่งผู้ประเมิน L2</td>
                        <td><?= Html::encode($evaluatorL2Pos) ?></td>
                    </tr>
                </table>
            </div>

            <!-- ส่วนที่ ๒ : การสรุปผลการประเมิน -->
            <div class="mb-4 page-break-avoid">
                <div class="doc-section-title">
                    <i class="bi bi-calculator me-1 text-primary"></i> ส่วนที่ ๒ : การสรุปผลการประเมิน
                </div>

                <?php if ($evaluation->isCompleted() && $result): ?>
                    <?php if ($isSpecial): ?>
                        <table class="official-table text-center">
                            <thead>
                                <tr>
                                    <th style="width: 50%;" class="text-start">ด้านการประเมิน</th>
                                    <th style="width: 25%;">คะแนนเต็ม</th>
                                    <th style="width: 25%;">คะแนนที่ได้</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-start py-2">
                                        <strong>ด้านที่ ๑ : ผลสัมฤทธิ์ของงาน (ผลงาน)</strong>
                                        <div class="small text-muted ps-2">คำนวณจากข้อ ๑.๑ - ๑.๖ (ผลงานหลัก ๕๐ คะแนน + ภาระงานรอง ๕ คะแนน)</div>
                                    </td>
                                    <td class="fw-bold">๕๕</td>
                                    <td class="fw-bold text-primary fs-6"><?= number_format($perfScore100, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-start py-2">
                                        <strong>ด้านที่ ๒ : คุณลักษณะการปฏิบัติงาน</strong>
                                        <div class="small text-muted ps-2">คำนวณจากข้อ ๒.๑ - ๒.๗ (คุณลักษณะ ๗ รายการ รวม ๔๕ คะแนน)</div>
                                    </td>
                                    <td class="fw-bold">๔๕</td>
                                    <td class="fw-bold text-primary fs-6"><?= number_format($compScore100, 2) ?></td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td class="text-end py-2">รวมคะแนนทั้งสิ้น :</td>
                                    <td>๑๐๐</td>
                                    <td class="text-success fs-5"><?= number_format($finalScore, 2) ?> คะแนน</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table class="official-table text-center">
                            <thead>
                                <tr>
                                    <th style="width: 44%;" class="text-start">องค์ประกอบการประเมิน</th>
                                    <th style="width: 18%;">คะแนน (ก)<br><small>(เต็ม ๑๐๐)</small></th>
                                    <th style="width: 18%;">สัดส่วน / ค่าน้ำหนัก (ข)</th>
                                    <th style="width: 20%;">รวมคะแนน<br><small>((ก) &times; (ข)) / ๑๐๐</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-start py-2">
                                        <strong><?= $isGovt ? 'ส่วนที่ ๒ : ผลสัมฤทธิ์ของงาน' : 'องค์ประกอบที่ ๑ : ผลสัมฤทธิ์ของงาน' ?></strong>
                                        <div class="small text-muted ps-2"><?= $isGovt ? 'คำนวณจากภาระงานหลักและภาระงานรอง (เต็ม ๑๐๐ คะแนน)' : 'คำนวณจากแบบที่ ๒ แบบข้อตกลงการประเมินผลสัมฤทธิ์ของงาน (แบบ ป.ผ.)' ?></div>
                                    </td>
                                    <td class="fw-bold"><?= number_format($perfScore100, 2) ?></td>
                                    <td><?= $perfWeightTh ?></td>
                                    <td class="fw-bold text-primary fs-6"><?= number_format($perfScoreFinal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-start py-2">
                                        <strong><?= $isGovt ? 'ส่วนที่ ๓ : พฤติกรรมการปฏิบัติงาน' : 'องค์ประกอบที่ ๒ : พฤติกรรมการปฏิบัติราชการ (สมรรถนะ)' ?></strong>
                                        <div class="small text-muted ps-2"><?= $isGovt ? 'คำนวณจากพฤติกรรมการปฏิบัติงาน ๕ สมรรถนะ (เต็ม ๑๐๐ คะแนน)' : 'คำนวณจากแบบที่ ๓ แบบข้อตกลงการประเมินสมรรถนะ (แบบ พม.)' ?></div>
                                    </td>
                                    <td class="fw-bold"><?= number_format($compScore100, 2) ?></td>
                                    <td><?= $compWeightTh ?></td>
                                    <td class="fw-bold text-primary fs-6"><?= number_format($compScoreFinal, 2) ?></td>
                                </tr>
                                <tr class="text-muted">
                                    <td class="text-start py-2">องค์ประกอบอื่น (ถ้ามี)</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td class="text-end py-2">รวม :</td>
                                    <td>-</td>
                                    <td>๑๐๐%</td>
                                    <td class="text-success fs-5"><?= number_format($finalScore, 2) ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <!-- กล่องระดับผลการประเมิน ๕ ระดับตามระเบียบของแต่ละประเภทบุคลากร -->
                    <?php
                    $isExcellent = false;
                    $isVeryGood = false;
                    $isGood = false;
                    $isFair = false;
                    $isPoor = false;
                    $isFailed = false;

                    if ($isEvaluated && $finalScore > 0) {
                        if ($isSpecial) {
                            $isVeryGood = ($perfLevel === 'ดีมาก' || $finalScore >= 90.0);
                            $isGood = ($perfLevel === 'ดี' || ($finalScore >= 80.0 && $finalScore < 90.0));
                            $isFair = ($perfLevel === 'พอใช้' || ($finalScore >= 70.0 && $finalScore < 80.0));
                            $isPoor = ($perfLevel === 'ปรับปรุง' || ($finalScore >= 60.0 && $finalScore < 70.0));
                            $isFailed = ($perfLevel === 'ไม่ผ่าน' || $finalScore < 60.0);
                        } elseif ($isGovt) {
                            $isExcellent = ($perfLevel === 'ดีเด่น' || $finalScore >= 95.0);
                            $isVeryGood = ($perfLevel === 'ดีมาก' || ($finalScore >= 85.0 && $finalScore < 95.0));
                            $isGood = ($perfLevel === 'ดี' || ($finalScore >= 75.0 && $finalScore < 85.0));
                            $isFair = ($perfLevel === 'พอใช้' || ($finalScore >= 65.0 && $finalScore < 75.0));
                            $isPoor = ($perfLevel === 'ต้องปรับปรุง' || $finalScore < 65.0);
                        } else {
                            $isExcellent = ($perfLevel === 'ดีเด่น' || $finalScore >= 90.0);
                            $isVeryGood = ($perfLevel === 'ดีมาก' || ($finalScore >= 80.0 && $finalScore < 90.0));
                            $isGood = ($perfLevel === 'ดี' || ($finalScore >= 70.0 && $finalScore < 80.0));
                            $isFair = ($perfLevel === 'พอใช้' || ($finalScore >= 60.0 && $finalScore < 70.0));
                            $isPoor = ($perfLevel === 'ต้องปรับปรุง' || $finalScore < 60.0);
                        }
                    }
                    ?>
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-dark"><i class="bi bi-award me-1 text-warning"></i> ระดับผลการประเมิน (Performance Level) :</div>
                            <?php if (!$isEvaluated): ?>
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock-history me-1"></i> อยู่ระหว่างกระบวนการประเมินโดยผู้บังคับบัญชา</span>
                            <?php endif; ?>
                        </div>
                        <div class="row g-2 text-center">
                            <?php if ($isSpecial): ?>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isVeryGood ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isVeryGood ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดีมาก
                                        </div>
                                        <small class="text-muted">(๙๐.๐๐ - ๑๐๐)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isGood ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isGood ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดี
                                        </div>
                                        <small class="text-muted">(๘๐.๐๐ - ๘๙.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isFair ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isFair ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> พอใช้
                                        </div>
                                        <small class="text-muted">(๗๐.๐๐ - ๗๙.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isPoor ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isPoor ? '<span class="text-warning fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ปรับปรุง
                                        </div>
                                        <small class="text-muted">(๖๐.๐๐ - ๖๙.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isFailed ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isFailed ? '<span class="text-danger fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ไม่ผ่าน
                                        </div>
                                        <small class="text-muted">(ต่ำกว่า ๖๐.๐๐)</small>
                                    </div>
                                </div>
                            <?php elseif ($isGovt): ?>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isExcellent ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isExcellent ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดีเด่น
                                        </div>
                                        <small class="text-muted">(๙๕.๐๐ - ๑๐๐)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isVeryGood ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isVeryGood ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดีมาก
                                        </div>
                                        <small class="text-muted">(๘๕.๐๐ - ๙๔.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isGood ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isGood ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดี
                                        </div>
                                        <small class="text-muted">(๗๕.๐๐ - ๘๔.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isFair ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isFair ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> พอใช้
                                        </div>
                                        <small class="text-muted">(๖๕.๐๐ - ๗๔.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isPoor ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isPoor ? '<span class="text-danger fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ต้องปรับปรุง
                                        </div>
                                        <small class="text-muted">(ต่ำกว่า ๖๕.๐๐)</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isExcellent ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isExcellent ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดีเด่น
                                        </div>
                                        <small class="text-muted">(๙๐.๐๐ - ๑๐๐)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isVeryGood ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isVeryGood ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดีมาก
                                        </div>
                                        <small class="text-muted">(๘๐.๐๐ - ๘๙.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isGood ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isGood ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ดี
                                        </div>
                                        <small class="text-muted">(๗๐.๐๐ - ๗๙.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isFair ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isFair ? '<span class="text-success fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> พอใช้
                                        </div>
                                        <small class="text-muted">(๖๐.๐๐ - ๖๙.๙๙)</small>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="grade-badge-box <?= $isPoor ? 'active-grade' : '' ?>">
                                        <div class="fw-bold text-dark">
                                            <?= $isPoor ? '<span class="text-danger fw-bold fs-5">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?> ต้องปรับปรุง
                                        </div>
                                        <small class="text-muted">(ต่ำกว่า ๖๐.๐๐)</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isSpecial): 
                        $rec = $evaluation->employment_recommendation;
                    ?>
                        <!-- ความเห็นเกี่ยวกับการจ้างต่อสำหรับพนักงานพิเศษเงินรายได้ -->
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="fw-bold text-dark mb-2"><i class="bi bi-briefcase me-1 text-primary"></i> ความเห็นเกี่ยวกับการจ้าง (พนักงานพิเศษเงินรายได้) :</div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-white <?= $rec === 'continue_standard' ? 'border-success bg-success-subtle' : '' ?>">
                                        <?= $rec === 'continue_standard' ? '<span class="text-success fw-bold">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?>
                                        <strong>ควรให้จ้างต่อไป</strong>
                                        <div class="small text-muted ps-3">(ผลประเมิน ๗๐% ขึ้นไป)</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-white <?= $rec === 'continue_improve' ? 'border-warning bg-warning-subtle' : '' ?>">
                                        <?= $rec === 'continue_improve' ? '<span class="text-warning fw-bold">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?>
                                        <strong>ควรให้จ้างต่อไป โดยปรับปรุงแก้ไข</strong>
                                        <div class="small text-muted ps-3">(ผลประเมิน ๖๐% ขึ้นไป)</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-white <?= $rec === 'terminate' ? 'border-danger bg-danger-subtle' : '' ?>">
                                        <?= $rec === 'terminate' ? '<span class="text-danger fw-bold">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?>
                                        <strong class="text-danger">ควรให้เลิกจ้าง</strong>
                                        <div class="small text-muted ps-3">(ผลประเมินต่ำกว่า ๖๐%)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning py-3 px-4 rounded-3 border-start border-warning border-4 shadow-sm mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-hourglass-split fs-3 me-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">อยู่ระหว่างกระบวนการประเมินผลการปฏิบัติราชการ</h6>
                                <p class="mb-0 text-muted small">
                                    แบบประเมินนี้อยู่ในขั้นตอน: <strong><?= strip_tags($evaluation->statusLabel) ?></strong> 
                                    (ผลคะแนนสรุป ส่วนที่ ๒ และระดับผลการประเมิน/เกรด จะแสดงเมื่อการประเมินเสร็จสมบูรณ์เรียบร้อยแล้วเท่านั้น)
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ส่วนที่ ๓ : แผนพัฒนาการปฏิบัติราชการรายบุคคล (IDP) -->
            <?php
            $savedIdp = [];
            if (!empty($evaluation->idp_data)) {
                $savedIdp = is_array($evaluation->idp_data) ? $evaluation->idp_data : json_decode($evaluation->idp_data, true);
            }
            // Filter out purely empty entries
            $hasIdp = false;
            if (!empty($savedIdp) && is_array($savedIdp)) {
                foreach ($savedIdp as $item) {
                    if (!empty(trim($item['topic'] ?? '')) || !empty(trim($item['method'] ?? '')) || !empty(trim($item['timeline'] ?? ''))) {
                        $hasIdp = true;
                        break;
                    }
                }
            }
            ?>
            <div class="mb-4 page-break-avoid">
                <div class="doc-section-title">
                    <i class="bi bi-graph-up-arrow me-1 text-primary"></i> ส่วนที่ ๓ : แผนพัฒนาการปฏิบัติราชการรายบุคคล (Individual Development Plan - IDP)
                </div>
                <table class="official-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;" class="text-center">#</th>
                            <th style="width: 38%;">ความรู้ / ทักษะ / สมรรถนะ ที่ต้องได้รับการพัฒนา</th>
                            <th style="width: 38%;">วิธีการพัฒนา</th>
                            <th style="width: 20%;">ช่วงเวลาที่ต้องการการพัฒนา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hasIdp): ?>
                            <?php foreach ($savedIdp as $idpIdx => $idpRow): 
                                if (empty(trim($idpRow['topic'] ?? '')) && empty(trim($idpRow['method'] ?? '')) && empty(trim($idpRow['timeline'] ?? ''))) continue;
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $toTh($idpIdx + 1) ?></td>
                                    <td><?= Html::encode($idpRow['topic'] ?? '-') ?></td>
                                    <td><?= Html::encode($idpRow['method'] ?? '-') ?></td>
                                    <td><?= Html::encode($idpRow['timeline'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="text-center">๑</td>
                                <td class="text-muted">(ไม่ได้ระบุแผนพัฒนาเพิ่มเติม)</td>
                                <td class="text-muted">(ไม่ได้ระบุ)</td>
                                <td class="text-muted">(ไม่ได้ระบุ)</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ส่วนที่ ๔ : การรับทราบผลการประเมิน -->
            <div class="mb-4 page-break-avoid">
                <div class="doc-section-title">
                    <i class="bi bi-pen me-1 text-primary"></i> ส่วนที่ ๔ : การรับทราบผลการประเมิน
                </div>
                <table class="official-table" style="background-color: #fff;">
                    <tr>
                        <!-- ผู้รับการประเมิน -->
                        <td style="width: 50%; vertical-align: top;" class="p-3">
                            <div class="fw-bold text-dark mb-2">ผู้รับการประเมิน :</div>
                            <div class="mb-3">
                                <?= $isAcknowledged ? '<span class="text-success fw-bold">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?>
                                <span class="<?= $isAcknowledged ? 'fw-bold text-dark' : 'text-muted' ?>">ได้รับทราบผลการประเมินและแผนพัฒนาการปฏิบัติราชการรายบุคคลแล้ว</span>
                            </div>
                            <div class="text-center mt-3 pt-2 border-top">
                                <p class="mb-3">ลงชื่อ: <span class="fw-bold text-dark border-bottom pb-1 px-2"><?= $isAcknowledged ? Html::encode($personnel->fullName) : '...................................................' ?></span></p>
                                <p class="mb-1"><strong>( <?= Html::encode($personnel->fullName) ?> )</strong></p>
                                <p class="small text-muted mb-0">
                                    วันที่: <?= $evaluation->acknowledgement_at ? Yii::$app->formatter->asDate($evaluation->acknowledgement_at, 'php:d F Y') : '....... / ................... / .......' ?>
                                </p>
                            </div>
                        </td>
                        <!-- ผู้ประเมิน L1 -->
                        <td style="width: 50%; vertical-align: top;" class="p-3">
                            <div class="fw-bold text-dark mb-2">ผู้ประเมิน (หัวหน้างาน L1) :</div>
                            <div class="mb-3">
                                <?= $isL1Evaluated ? '<span class="text-success fw-bold">[✓]</span>' : '<span class="text-muted">[ &nbsp; ]</span>' ?>
                                <span class="<?= $isL1Evaluated ? 'fw-bold text-dark' : 'text-muted' ?>">ได้แจ้งผลการประเมินและผู้รับการประเมินได้ลงนามรับทราบแล้ว</span>
                            </div>
                            <div class="text-center mt-3 pt-2 border-top">
                                <p class="mb-3">ลงชื่อ: <span class="fw-bold text-dark border-bottom pb-1 px-2"><?= $isL1Evaluated ? Html::encode($evaluatorL1Name) : '...................................................' ?></span></p>
                                <p class="mb-1"><strong>( <?= Html::encode($evaluatorL1Name) ?> )</strong></p>
                                <p class="small text-muted mb-0">
                                    วันที่: <?= $evaluation->l1_evaluated_at ? Yii::$app->formatter->asDate($evaluation->l1_evaluated_at, 'php:d F Y') : '....... / ................... / .......' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ส่วนที่ ๕ : ความเห็นของผู้บังคับบัญชาเหนือขึ้นไป -->
            <div class="mb-3 page-break-avoid">
                <div class="doc-section-title">
                    <i class="bi bi-chat-left-quote me-1 text-primary"></i> ส่วนที่ ๕ : ความเห็นของผู้บังคับบัญชาเหนือขึ้นไป
                </div>

                <!-- ๕.๑ ความเห็นของหัวหน้าฝ่าย (ผู้ประเมินตัดสิน L2) -->
                <div class="p-3 border rounded mb-3 bg-light">
                    <div class="fw-bold text-dark mb-2">
                        ความเห็นของหัวหน้าฝ่าย (ผู้ประเมินตัดสินขั้นสุดท้าย L2: <?= Html::encode($evaluatorL2Name) ?>)
                    </div>
                    <table class="official-table mb-2 bg-white">
                        <tr>
                            <td class="bg-light fw-bold" style="width: 32%;">๑) จุดเด่น และ/หรือ สิ่งที่ทำได้ดี:</td>
                            <td><?= nl2br(Html::encode(($evaluation->l2_comment_strength ?: $evaluation->supervisor_comment_strength) ?: '-')) ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light fw-bold">๒) จุดที่ควรปรับปรุงและแก้ไข:</td>
                            <td><?= nl2br(Html::encode(($evaluation->l2_comment_improvement ?: $evaluation->supervisor_comment_improvement) ?: '-')) ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light fw-bold">๓) ข้อเสนอแนะเกี่ยวกับวิธีส่งเสริมและพัฒนา:</td>
                            <td><?= nl2br(Html::encode(($evaluation->l2_comment_suggestion ?: $evaluation->supervisor_comment_suggestion) ?: '-')) ?></td>
                        </tr>
                    </table>
                    <div class="text-center pt-2">
                        <p class="mb-2">ลงชื่อ: <span class="fw-bold text-dark border-bottom pb-1 px-2"><?= $isL2Evaluated ? Html::encode($evaluatorL2Name) : '...................................................' ?></span></p>
                        <p class="mb-1"><strong>( <?= Html::encode($evaluatorL2Name) ?> )</strong></p>
                        <p class="small text-muted mb-0">
                            หัวหน้าฝ่าย / ผู้ประเมินชั้นที่ ๒ (L2) | วันที่: <?= $evaluation->l2_evaluated_at ? Yii::$app->formatter->asDate($evaluation->l2_evaluated_at, 'php:d F Y') : '....... / ................... / .......' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation for Form 1 -->
            <div class="text-end mt-4 pt-3 border-top d-print-none">
                <button type="button" class="btn btn-primary btn-lg shadow-sm px-4 fw-bold" onclick="switchViewForm(2);">
                    ถัดไป: แบบฟอร์มที่ ๒ (<?= $docCode2 ?> ผลสัมฤทธิ์ของงาน) <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- Page Break for Print between Form 1 & Form 2 -->
    <div class="page-break"></div>
    <div class="form-divider-break d-print-none my-4" style="display: none;">
        <div class="border-top border-3 border-dark text-center py-2 bg-light fw-bold text-muted">
            <i class="bi bi-arrow-down-circle me-1"></i> สิ้นสุดแบบฟอร์มที่ ๑ &bull; เริ่มต้นแบบฟอร์มที่ ๒
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- แบบฟอร์มที่ ๒ : แบบผลสัมฤทธิ์ของงาน (แบบ ป.ผ.)                              -->
    <!-- ========================================================================= -->
    <div class="eval-form-pane" id="form-pane-2" style="display: none;">
        <div class="card card-rmutt official-card-wrap p-4 p-md-5 shadow-sm bg-white border mb-4">

            <!-- Form 2 Header -->
            <div class="d-flex justify-content-end mb-2">
                <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm"><?= $docCode2 ?></span>
            </div>
            <div class="text-center mb-4 pb-2 border-bottom border-dark border-2">
                <div class="official-header-title text-uppercase"><?= $docTitle2 ?></div>
                <div class="official-header-sub mt-1">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</div>
                <div class="fw-bold mt-1 text-secondary"><?= Html::encode($evaluation->cycle->name_th) ?></div>
            </div>

            <!-- ส่วนข้อมูลผู้รับการประเมินสังเขป -->
            <div class="mb-3 page-break-avoid">
                <table class="official-table">
                    <tr>
                        <td style="width: 20%;" class="fw-bold bg-light">ชื่อผู้รับการประเมิน</td>
                        <td style="width: 30%;"><?= Html::encode($personnel->fullName) ?></td>
                        <td style="width: 20%;" class="fw-bold bg-light">ตำแหน่ง / สังกัด</td>
                        <td style="width: 30%;"><?= Html::encode($personnel->position->name_th) ?> / <?= Html::encode($personnel->department->name_th) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">ผู้ประเมินขั้นต้น (L1)</td>
                        <td><?= Html::encode($evaluatorL1Name) ?></td>
                        <td class="fw-bold bg-light">ผู้ประเมินตัดสิน (L2)</td>
                        <td><?= Html::encode($evaluatorL2Name) ?></td>
                    </tr>
                </table>
            </div>

            <?php if (!$isGovt && !$isSpecial): 
                $secMain = null;
                $secPolicy = null;
                $secAcad = null;
                if (!empty($sections)) {
                    foreach ($sections as $s) {
                        if ($s->section_code === 'MAIN_WORK') $secMain = $s;
                        elseif ($s->section_code === 'SECONDARY_POLICY') $secPolicy = $s;
                        elseif ($s->section_code === 'SECONDARY_ACADEMIC') $secAcad = $s;
                    }
                }
                $mainWorkDetails = $details['form2']['main_work']['items'] ?? [];
                if (empty($mainWorkDetails) && $secMain && !empty($secMain->items)) {
                    $mItem = $secMain->items[0];
                    $ans = $supAnswers[$mItem->id] ?? $selfAnswers[$mItem->id] ?? null;
                    $rawRows = ($ans && $ans->json_value) ? (is_string($ans->json_value) ? json_decode($ans->json_value, true) : $ans->json_value) : [];
                    foreach ($rawRows as $r) {
                        $sc = floatval($r['self_score'] ?? 5);
                        $supSc = floatval($r['supervisor_score'] ?? $sc);
                        $w = floatval($r['weight'] ?? 0);
                        $mainWorkDetails[] = [
                            'title' => $r['title'] ?? '',
                            'kpi' => $r['kpi'] ?? '',
                            'self_pdca' => $sc,
                            'sup_pdca' => $supSc,
                            'weight' => $w,
                            'sup_weighted' => ($supSc / 5.0) * $w,
                        ];
                    }
                }

                $policyItem = ($secPolicy && !empty($secPolicy->items)) ? $secPolicy->items[0] : null;
                $selfPolicyAns = $policyItem ? ($selfAnswers[$policyItem->id] ?? null) : null;
                $supPolicyAns = $policyItem ? ($supAnswers[$policyItem->id] ?? $selfPolicyAns) : null;
                $selfPolicyChecked = is_array($selfPolicyAns->json_value ?? null) ? $selfPolicyAns->json_value : [];
                $supPolicyChecked = is_array($supPolicyAns->json_value ?? null) ? $supPolicyAns->json_value : $selfPolicyChecked;

                $selfPolicyCount = count($selfPolicyChecked);
                $selfPolicyScore = ($selfPolicyCount >= 3) ? 5 : (($selfPolicyCount === 2) ? 3 : (($selfPolicyCount === 1) ? 1 : 0));
                $supPolicyCount = count($supPolicyChecked);
                $supPolicyScore = ($supPolicyCount >= 3) ? 5 : (($supPolicyCount === 2) ? 3 : (($supPolicyCount === 1) ? 1 : 0));
                $supPolicyWeighted = ($supPolicyScore / 5.0) * 15.0;

                $acadItem = ($secAcad && !empty($secAcad->items)) ? $secAcad->items[0] : null;
                $selfAcadAns = $acadItem ? ($selfAnswers[$acadItem->id] ?? null) : null;
                $supAcadAns = $acadItem ? ($supAnswers[$acadItem->id] ?? $selfAcadAns) : null;
                $selfAcadVal = $selfAcadAns ? intval($selfAcadAns->numeric_value ?? $selfAcadAns->text_value ?? 0) : 0;
                $supAcadVal = $supAcadAns ? intval($supAcadAns->numeric_value ?? $supAcadAns->text_value ?? $selfAcadVal) : $selfAcadVal;
                $supAcadWeighted = ($supAcadVal / 5.0) * 5.0;

                $policyList = [
                    '1' => 'งานบริการวิชาการหารายได้ตั้งแต่ ๑๐,๐๐๐.- บาทขึ้นไป (สะสมใน ๑ ปี)',
                    '2' => 'นวัตกรรม/สร้างสรรค์ โดยเป็นผู้ดำเนินการหลักหรือผู้ร่วมซึ่งมีส่วนร่วม ร้อยละ ๓๐ ขึ้นไป โดยใช้แบบฟอร์มการแสดงการมีส่วนร่วม',
                    '3' => 'การพัฒนาตนเองด้านภาษาต่างประเทศ โดยมีใบรับรองตามมาตรฐาน เช่น RT-TEP ๓.๕/ IELTS ๕.๕ /TOEFL ๔๐๐ หรือกิจกรรมด้านภาษาที่คณะกรรมการรับรอง / วิชาชีพเฉพาะทาง โดยมีใบรับรองตามมาตรฐาน เช่น ใบ Certificate จากระบบ Certiport (ภายในปีงบประมาณ หรือย้อนหลัง ๑ ปี **๑ Certificate ใช้ได้ ๒ รอบประเมิน)',
                    '4' => 'การเข้าร่วมกิจกรรมของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๔ ครั้งขึ้นไป/รอบการประเมิน (ดังเอกสารแนบ)',
                    '5' => 'คณะกรรมการการดำเนินงานด้านต่าง ๆ ของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๓ งาน/โครงการขึ้นไป (**สามารถสะสมได้ภายใน ๑ ปี)',
                    '6' => 'ปฏิบัติหน้าที่หัวหน้าฝ่าย (เท่ากับ ๒ ข้อ)',
                    '7' => 'ปฏิบัติหน้าที่หัวหน้างาน (เท่ากับ ๑ ข้อ)',
                ];
                $acadLevels = [
                    '0' => 'ระดับ ๐: ไม่มีการจัดทำ / ไม่เข้าเกณฑ์ (๐ คะแนน)',
                    '1' => 'ระดับ ๑: ยื่นผลงานให้ผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญพิจารณา (แนบเอกสารขอความอนุเคราะห์/ คำสั่งแต่งตั้ง)',
                    '2' => 'ระดับ ๒: ผ่านการพิจารณาจากผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญในงานที่เกี่ยวข้อง ตรวจเบื้องต้น (แนบแบบประเมินผลงาน)',
                    '3' => 'ระดับ ๓: ผ่านการพิจารณาผู้บังคับบัญชาภายในหน่วยงาน ส่งไปยัง กบค. (แนบบันทึกข้อความ)',
                    '4' => 'ระดับ ๔: อยู่ระหว่างการพิจารณาจาก กบค. (ใช้หลักฐานสถานะการดำเนินการจาก กบค.)',
                    '5' => 'ระดับ ๕: เผยแพร่ผลงานทางวิชาการ เป็นตำรา หนังสือบทความ และหรือ ได้ตำแหน่งที่สูงขึ้น (แนบคำสั่งแต่งตั้ง หรือหลักฐาน)',
                ];

                $mainWeightTotal = 0;
                $mainWeightedTotal = 0;
            ?>
                <!-- Form 2 Main Table -->
                <div class="mb-4 page-break-avoid">
                    <table class="official-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 40px;" class="text-center">#</th>
                                <th rowspan="2" style="width: 25%;">(๑) กิจกรรม/โครงการ/งาน</th>
                                <th rowspan="2" style="width: 28%;">(๒) ตัวชี้วัด</th>
                                <th rowspan="2" style="width: 75px;" class="text-center">บุคลากร<br>ประเมินตนเอง</th>
                                <th rowspan="2" style="width: 80px;" class="text-center">หัวหน้างาน<br>ประเมิน</th>
                                <th colspan="5" class="text-center py-1">(๓) ระดับค่าเป้าหมาย</th>
                                <th rowspan="2" style="width: 50px;" class="text-center">(๔)<br>ค่าคะแนน<br>ที่ได้</th>
                                <th rowspan="2" style="width: 60px;" class="text-center">(๕)<br>น้ำหนัก<br>(%)</th>
                                <th rowspan="2" style="width: 80px;" class="text-center">(๖)<br>คะแนนถ่วงน้ำหนัก<br><small>((๔)X(๕))/๑๐๐</small></th>
                            </tr>
                            <tr>
                                <th style="width: 24px;" class="text-center py-1">๑</th>
                                <th style="width: 24px;" class="text-center py-1">๒</th>
                                <th style="width: 24px;" class="text-center py-1">๓</th>
                                <th style="width: 24px;" class="text-center py-1">๔</th>
                                <th style="width: 24px;" class="text-center py-1">๕</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. ภาระงานหลัก -->
                            <tr class="fw-bold bg-light">
                                <td colspan="13">ภาระงานหลัก (ค่าน้ำหนัก ๖๐ - ๘๐ ข้อละไม่เกิน ๓๐) ระบุงานที่ปฏิบัติ (รายละเอียดข้อมูล)</td>
                            </tr>
                            <?php if (empty($mainWorkDetails)): ?>
                                <tr><td colspan="13" class="text-center text-muted py-2">- ไม่มีข้อมูลภาระงานหลัก -</td></tr>
                            <?php else: ?>
                                <?php foreach ($mainWorkDetails as $mIdx => $mItem): 
                                    $selfSc = round(floatval($mItem['self_pdca'] ?? 5), 1);
                                    $supSc = round(floatval($mItem['sup_pdca'] ?? $selfSc), 1);
                                    $supFloor = intval(floor($supSc));
                                    $wt = floatval($mItem['weight'] ?? 0);
                                    $wtd = floatval($mItem['sup_weighted'] ?? (($supSc / 5.0) * $wt));
                                    $mainWeightTotal += $wt;
                                    $mainWeightedTotal += $wtd;
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $mIdx + 1 ?></td>
                                        <td><strong><?= Html::encode($mItem['title']) ?></strong></td>
                                        <td>
                                            <div class="small text-muted" style="line-height: 1.35;">
                                                ระดับความสำเร็จในการจัดทำ (ตามวงจร PDCA):<br>
                                                <span class="<?= $supFloor == 1 ? 'fw-bold text-success' : '' ?>">ระดับ ๑ (Plan): มีแผน/แนวทาง</span><br>
                                                <span class="<?= $supFloor == 2 ? 'fw-bold text-success' : '' ?>">ระดับ ๒ (Do): ดำเนินการตามแผน</span><br>
                                                <span class="<?= $supFloor == 3 ? 'fw-bold text-success' : '' ?>">ระดับ ๓ (Check): ทบทวน/ประเมินผล</span><br>
                                                <span class="<?= $supFloor == 4 ? 'fw-bold text-success' : '' ?>">ระดับ ๔ (Act): นำผลมาปรับปรุง</span><br>
                                                <span class="<?= $supFloor == 5 ? 'fw-bold text-success' : '' ?>">ระดับ ๕ (Impact): ปรับปรุงต่อเนื่อง/มีนวัตกรรม</span>
                                            </div>
                                        </td>
                                        <td class="text-center">ระดับ <?= (round($selfSc, 1) == intval($selfSc)) ? intval($selfSc) : number_format($selfSc, 1) ?></td>
                                        <td class="text-center fw-bold">ระดับ <?= (round($supSc, 1) == intval($supSc)) ? intval($supSc) : number_format($supSc, 1) ?></td>
                                        <td class="text-center <?= $supFloor == 1 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supFloor == 1 ? '✓' : '๑' ?></td>
                                        <td class="text-center <?= $supFloor == 2 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supFloor == 2 ? '✓' : '๒' ?></td>
                                        <td class="text-center <?= $supFloor == 3 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supFloor == 3 ? '✓' : '๓' ?></td>
                                        <td class="text-center <?= $supFloor == 4 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supFloor == 4 ? '✓' : '๔' ?></td>
                                        <td class="text-center <?= $supFloor == 5 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supFloor == 5 ? '✓' : '๕' ?></td>
                                        <td class="text-center fw-bold"><?= (round($supSc, 1) == intval($supSc)) ? intval($supSc) : number_format($supSc, 1) ?></td>
                                        <td class="text-center"><?= $wt ?>%</td>
                                        <td class="text-center fw-bold text-primary"><?= number_format($wtd, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- 2. งานอื่น ๆ ตามที่ได้รับมอบหมาย -->
                            <tr class="fw-bold bg-light">
                                <td colspan="13" class="py-2 px-3">
                                    ๕. งานอื่น ๆ ตามที่ได้รับมอบหมาย (ค่าน้ำหนักรวม ๒๐%)
                                </td>
                            </tr>

                            <!-- ๕.๑ ภาระงานนโยบาย -->
                            <tr class="fw-bold bg-light">
                                <td colspan="13" class="py-2 px-3 text-start border-top">
                                    <strong>๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายของมหาวิทยาลัยและสำนักฯ (ค่าน้ำหนัก ๑๕)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="align-top">
                                    <div class="small">
                                        <div class="fw-bold text-dark mb-1">รายการนโยบายที่มีการดำเนินงาน (ระบุรายละเอียดหัวข้อที่ดำเนิน):</div>
                                        <?php foreach ($policyList as $pKey => $pLabel): 
                                            $isDone = in_array($pKey, $supPolicyChecked);
                                        ?>
                                            <div class="mb-1 <?= $isDone ? 'fw-bold text-dark' : 'text-muted text-decoration-line-through' ?>">
                                                <?= $isDone ? '<span class="text-success fw-bold">[✓]</span>' : '<span class="text-muted">[ ]</span>' ?> <?= $pKey ?>. <?= Html::encode($pLabel) ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="mt-1 pt-1 border-top text-muted" style="font-size: 0.8rem;">
                                            เกณฑ์: 3-5 ข้อ = 5 คะแนน, 2 ข้อ = 3 คะแนน, 1 ข้อ = 1 คะแนน (ตรวจรับ <?= $supPolicyCount ?>/7 ข้อ)
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle">ระดับ <?= $selfPolicyScore ?></td>
                                <td class="text-center align-middle fw-bold">ระดับ <?= $supPolicyScore ?></td>
                                <td class="text-center align-middle <?= $supPolicyScore == 1 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supPolicyScore == 1 ? '✓' : '๑' ?></td>
                                <td class="text-center align-middle <?= $supPolicyScore == 2 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supPolicyScore == 2 ? '✓' : '๒' ?></td>
                                <td class="text-center align-middle <?= $supPolicyScore == 3 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supPolicyScore == 3 ? '✓' : '๓' ?></td>
                                <td class="text-center align-middle <?= $supPolicyScore == 4 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supPolicyScore == 4 ? '✓' : '๔' ?></td>
                                <td class="text-center align-middle <?= $supPolicyScore == 5 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supPolicyScore == 5 ? '✓' : '๕' ?></td>
                                <td class="text-center align-middle fw-bold"><?= $supPolicyScore ?></td>
                                <td class="text-center align-middle">15%</td>
                                <td class="text-center align-middle fw-bold text-primary"><?= number_format($supPolicyWeighted, 2) ?></td>
                            </tr>

                            <!-- ๕.๒ คู่มือปฏิบัติงาน -->
                            <tr class="fw-bold bg-light">
                                <td colspan="13" class="py-2 px-3 text-start border-top">
                                    <strong>๕.๒ มีการจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย ตำรา หนังสือ งานแปลตำราหรือหนังสือ งานวิเคราะห์หรืองานพัฒนา บทความทางวิชาการ เอกสารกรณีศึกษา ที่เสร็จสมบูรณ์ (ค่าน้ำหนัก ๕)</strong>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="align-top">
                                    <div class="small">
                                        <div class="text-muted mb-1"><strong>มีการดำเนินงาน:</strong> คู่มือปฏิบัติงาน/แผนปฏิบัติราชการ/งานวิจัย/ตำรา/งานสร้างสรรค์/หนังสือ/งานแปล/งานวิเคราะห์</div>
                                        <div class="p-1 rounded bg-light border">
                                            <strong class="text-dark">เกณฑ์ที่ผ่านการประเมิน:</strong><br>
                                            <span class="text-primary fw-bold">ระดับ <?= $supAcadVal ?>:</span> <?= Html::encode($acadLevels[strval($supAcadVal)] ?? '-') ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle">ระดับ <?= $selfAcadVal ?></td>
                                <td class="text-center align-middle fw-bold">ระดับ <?= $supAcadVal ?></td>
                                <td class="text-center align-middle <?= $supAcadVal == 1 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supAcadVal == 1 ? '✓' : '๑' ?></td>
                                <td class="text-center align-middle <?= $supAcadVal == 2 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supAcadVal == 2 ? '✓' : '๒' ?></td>
                                <td class="text-center align-middle <?= $supAcadVal == 3 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supAcadVal == 3 ? '✓' : '๓' ?></td>
                                <td class="text-center align-middle <?= $supAcadVal == 4 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supAcadVal == 4 ? '✓' : '๔' ?></td>
                                <td class="text-center align-middle <?= $supAcadVal == 5 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $supAcadVal == 5 ? '✓' : '๕' ?></td>
                                <td class="text-center align-middle fw-bold"><?= $supAcadVal ?></td>
                                <td class="text-center align-middle">5%</td>
                                <td class="text-center align-middle fw-bold text-primary"><?= number_format($supAcadWeighted, 2) ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <?php 
                            $secWeightedTotal = $supPolicyWeighted + $supAcadWeighted;
                            $totalForm2Weighted = $mainWeightedTotal + $secWeightedTotal;
                            $totalForm2Weight = $mainWeightTotal + 20;
                            $totalForm2ScoreWeighted = ($totalForm2Weighted / 100.0) * $perfWeight;
                            ?>
                            <tr class="fw-bold bg-light">
                                <td colspan="4" class="text-end">รวมน้ำหนักภาระงานหลัก (น้ำหนัก ๘๐%):</td>
                                <td colspan="6"></td>
                                <td class="text-end">รวม:</td>
                                <td class="text-center"><?= $mainWeightTotal ?>%</td>
                                <td class="text-center text-primary"><?= number_format($mainWeightedTotal, 2) ?></td>
                            </tr>
                            <tr class="fw-bold bg-light">
                                <td colspan="4" class="text-end">รวมงานอื่น ๆ ตามที่ได้รับมอบหมาย (ข้อ ๕.๑ ๑๕% + ข้อ ๕.๒ ๕% รวม ๒๐%):</td>
                                <td colspan="6"></td>
                                <td class="text-end">รวม:</td>
                                <td class="text-center">20%</td>
                                <td class="text-center text-primary"><?= number_format($secWeightedTotal, 2) ?></td>
                            </tr>
                            <tr class="fw-bold bg-warning-subtle">
                                <td colspan="4" class="text-end">(๗) ผลรวมส่วนผลสัมฤทธิ์ของงาน (แบบที่ ๒ เต็ม ๑๐๐ คะแนน):</td>
                                <td colspan="6"></td>
                                <td class="text-end">รวมน้ำหนัก:</td>
                                <td class="text-center"><?= $totalForm2Weight ?>%</td>
                                <td class="text-center text-primary fs-6"><?= number_format($totalForm2Weighted, 2) ?></td>
                            </tr>
                            <tr class="fw-bold bg-success-subtle">
                                <td colspan="4" class="text-end text-success">(๘) สรุปคะแนนส่วนผลสัมฤทธิ์ของงาน ถ่วงน้ำหนัก <?= $perfWeightTh ?> ในภาพรวม ((๗) &times; <?= $toTh((int)$perfWeight) ?> / ๑๐๐):</td>
                                <td colspan="7"></td>
                                <td class="text-center text-success fs-6"><?= number_format($totalForm2ScoreWeighted, 2) ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ความเห็นของผู้บังคับบัญชาต่อผลสัมฤทธิ์ของงาน -->
                <div class="mb-4 page-break-avoid">
                    <div class="doc-section-title">
                        <i class="bi bi-chat-left-text me-1 text-primary"></i> ความเห็นและข้อเสนอแนะของผู้บังคับบัญชาต่อผลสัมฤทธิ์ของงาน
                    </div>
                    <?php if ($evaluation->evaluatorL1 && ($evaluation->l1_comment_strength || $evaluation->l1_comment_improvement || $evaluation->l1_comment_suggestion)): ?>
                        <div class="mb-3">
                            <div class="fw-bold small text-muted mb-1">ความเห็นของหัวหน้างาน (ผู้ประเมินขั้นต้น L1: <?= Html::encode($evaluation->evaluatorL1->fullName) ?>)</div>
                            <table class="official-table mb-2">
                                <tr>
                                    <td class="bg-light fw-bold" style="width: 30%;">จุดเด่น / ข้อดี:</td>
                                    <td><?= nl2br(Html::encode($evaluation->l1_comment_strength ?: '-')) ?></td>
                                </tr>
                                <tr>
                                    <td class="bg-light fw-bold">จุดที่ควรปรับปรุง:</td>
                                    <td><?= nl2br(Html::encode($evaluation->l1_comment_improvement ?: '-')) ?></td>
                                </tr>
                                <tr>
                                    <td class="bg-light fw-bold">ข้อเสนอแนะในการพัฒนา:</td>
                                    <td><?= nl2br(Html::encode($evaluation->l1_comment_suggestion ?: '-')) ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mb-2">
                        <div class="fw-bold small text-muted mb-1">
                            ความเห็นของหัวหน้าฝ่าย (ผู้ประเมินตัดสิน L2: <?= $evaluation->evaluatorL2 ? Html::encode($evaluation->evaluatorL2->fullName) : '-' ?>)
                        </div>
                        <table class="official-table">
                            <tr>
                                <td class="bg-light fw-bold" style="width: 30%;">๑) จุดเด่น และ/หรือ สิ่งที่ทำได้ดี:</td>
                                <td><?= nl2br(Html::encode(($evaluation->l2_comment_strength ?: $evaluation->supervisor_comment_strength) ?: '-')) ?></td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">๒) จุดที่ควรปรับปรุงและแก้ไข:</td>
                                <td><?= nl2br(Html::encode(($evaluation->l2_comment_improvement ?: $evaluation->supervisor_comment_improvement) ?: '-')) ?></td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">๓) ข้อเสนอแนะเกี่ยวกับวิธีส่งเสริมและพัฒนา:</td>
                                <td><?= nl2br(Html::encode(($evaluation->l2_comment_suggestion ?: $evaluation->supervisor_comment_suggestion) ?: '-')) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

            <?php elseif ($isGovt): 
                $secGovtMain = null;
                $secGovtSec = null;
                foreach ($sections as $s) {
                    if ($s->section_code === 'GOVT_MAIN_WORK') $secGovtMain = $s;
                    elseif ($s->section_code === 'GOVT_SECONDARY_WORK') $secGovtSec = $s;
                }
                $govtMainItem = ($secGovtMain && !empty($secGovtMain->items)) ? $secGovtMain->items[0] : null;
                $govtSecItem = ($secGovtSec && !empty($secGovtSec->items)) ? $secGovtSec->items[0] : null;

                $selfGovtMainAns = $govtMainItem ? ($selfAnswers[$govtMainItem->id] ?? null) : null;
                $supGovtMainAns = $govtMainItem ? ($supAnswers[$govtMainItem->id] ?? $selfGovtMainAns) : null;

                $defaultGovtRows = [
                    ['title' => 'ปฏิบัติงานด้านธุรการ สารบรรณ และการจัดทำเอกสารราชการ', 'kpi_volume' => 5, 'kpi_quality' => 5, 'kpi_timeliness' => 5, 'kpi_resource' => 5, 'weight' => 40],
                    ['title' => 'ประสานงานการจัดประชุม สัมมนา และกิจกรรมของสำนักวิทยบริการฯ', 'kpi_volume' => 4, 'kpi_quality' => 5, 'kpi_timeliness' => 4, 'kpi_resource' => 4, 'weight' => 40],
                ];

                $selfGovtRows = ($selfGovtMainAns && !empty($selfGovtMainAns->json_value)) 
                    ? (is_string($selfGovtMainAns->json_value) ? json_decode($selfGovtMainAns->json_value, true) : $selfGovtMainAns->json_value) 
                    : $defaultGovtRows;
                if (empty($selfGovtRows)) $selfGovtRows = $defaultGovtRows;

                $supGovtRows = ($supGovtMainAns && !empty($supGovtMainAns->json_value)) 
                    ? (is_string($supGovtMainAns->json_value) ? json_decode($supGovtMainAns->json_value, true) : $supGovtMainAns->json_value) 
                    : $selfGovtRows;
                if (empty($supGovtRows)) $supGovtRows = $selfGovtRows;

                $selfGovtSecAns = $govtSecItem ? ($selfAnswers[$govtSecItem->id] ?? null) : null;
                $supGovtSecAns = $govtSecItem ? ($supAnswers[$govtSecItem->id] ?? $selfGovtSecAns) : null;

                $supGovtSecRaw = ($supGovtSecAns && !empty($supGovtSecAns->json_value)) 
                    ? (is_string($supGovtSecAns->json_value) ? json_decode($supGovtSecAns->json_value, true) : $supGovtSecAns->json_value) 
                    : [];
                $govtSecSelected = [];
                $govtSecDetails = [];
                if (isset($supGovtSecRaw['selected']) && is_array($supGovtSecRaw['selected'])) {
                    $govtSecSelected = $supGovtSecRaw['selected'];
                    $govtSecDetails = $supGovtSecRaw['details'] ?? [];
                } elseif (is_array($supGovtSecRaw)) {
                    $govtSecSelected = $supGovtSecRaw;
                }

                $govt10Options = [
                    '1' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย (ระบุชื่อกิจกรรม/โครงการ พร้อมแนบหลักฐาน)',
                    '2' => 'ดำเนินงานผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ของสำนักฯ (พร้อมแนบหลักฐาน)',
                    '3' => 'เป็นคณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / งาน EdPEx (พร้อมแนบหลักฐาน)',
                    '4' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน / การทำ LEAN / Kaizen (พร้อมแนบหลักฐาน)',
                    '5' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์ (พร้อมแนบหลักฐาน)',
                    '6' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก (พร้อมแนบหลักฐาน)',
                    '7' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (พร้อมแนบหลักฐาน)',
                    '8' => 'งานวิจัย / งานส่งเสริมความเป็นนานาชาติ / บริการวิชาการ / ทำนุบำรุงศิลปวัฒนธรรม (พร้อมแนบหลักฐาน)',
                    '9' => 'การหารายได้เข้าสำนักฯ (พร้อมแนบหลักฐาน)',
                    '10' => 'อื่น ๆ ที่ได้รับมอบหมาย (พร้อมแนบหลักฐาน)',
                ];
            ?>
                <!-- ตารางผลสัมฤทธิ์ของงานพนักงานราชการ (ส่วนที่ ๒) -->
                <div class="mb-4">
                    <div class="doc-section-title">
                        <i class="bi bi-table me-1 text-primary"></i> การประเมินผลสัมฤทธิ์ของงานพนักงานราชการทั่วไป (น้ำหนัก ๗๐%)
                    </div>
                    <table class="official-table text-center">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 40px;">ลำดับ</th>
                                <th rowspan="2" class="text-start" style="min-width: 250px;">หน้าที่ / ภารกิจ (ตัวชี้วัด)</th>
                                <th rowspan="2" style="width: 90px;">ตนเองให้<br><small>(เฉลี่ย)</small></th>
                                <th colspan="4">ผู้บังคับบัญชาประเมิน: ๔ ปัจจัย (๘๐ คะแนน)</th>
                                <th colspan="5">ระดับค่าเป้าหมาย (ก)</th>
                                <th rowspan="2" style="width: 80px;">น้ำหนัก(%)<br>(ข)</th>
                                <th rowspan="2" style="width: 95px;">คะแนน (ค)<br><small>(ก&times;ข)/๕</small></th>
                            </tr>
                            <tr>
                                <th style="width: 70px;"><small>ปริมาณ<br>(25)</small></th>
                                <th style="width: 70px;"><small>คุณภาพ<br>(25)</small></th>
                                <th style="width: 70px;"><small>ตรงเวลา<br>(15)</small></th>
                                <th style="width: 70px;"><small>คุ้มค่า<br>(15)</small></th>
                                <th style="width: 28px;">๑</th>
                                <th style="width: 28px;">๒</th>
                                <th style="width: 28px;">๓</th>
                                <th style="width: 28px;">๔</th>
                                <th style="width: 28px;">๕</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="fw-bold bg-light">
                                <td colspan="14" class="text-start py-2">
                                    <strong>ภาระงานหลัก (ค่าน้ำหนัก ๘๐% ในส่วนที่ ๒)</strong>
                                </td>
                            </tr>
                            <?php 
                            $totalGovtMainScore = 0.0;
                            $totalGovtMainWeight = 0.0;
                            foreach ($supGovtRows as $gIdx => $gRow): 
                                $selfRow = $selfGovtRows[$gIdx] ?? $gRow;
                                $sk1 = floatval($selfRow['kpi_volume'] ?? 5);
                                $sk2 = floatval($selfRow['kpi_quality'] ?? 5);
                                $sk3 = floatval($selfRow['kpi_timeliness'] ?? 5);
                                $sk4 = floatval($selfRow['kpi_resource'] ?? 5);
                                $selfAvg = (($sk1 * 25) + ($sk2 * 25) + ($sk3 * 15) + ($sk4 * 15)) / 80.0;

                                $k1 = floatval($gRow['kpi_volume'] ?? $sk1);
                                $k2 = floatval($gRow['kpi_quality'] ?? $sk2);
                                $k3 = floatval($gRow['kpi_timeliness'] ?? $sk3);
                                $k4 = floatval($gRow['kpi_resource'] ?? $sk4);
                                $gw = floatval($gRow['weight'] ?? 40);
                                $supAvg = (($k1 * 25) + ($k2 * 25) + ($k3 * 15) + ($k4 * 15)) / 80.0;
                                $weighted = ($supAvg / 5.0) * $gw;
                                $roundedSup = (int)round($supAvg);

                                $totalGovtMainScore += $weighted;
                                $totalGovtMainWeight += $gw;
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= $gIdx + 1 ?></td>
                                    <td class="text-start">
                                        <strong><?= Html::encode($gRow['title'] ?? '') ?></strong>
                                    </td>
                                    <td>ระดับ <?= number_format($selfAvg, 1) ?></td>
                                    <td><?= $k1 ?></td>
                                    <td><?= $k2 ?></td>
                                    <td><?= $k3 ?></td>
                                    <td><?= $k4 ?></td>
                                    <td class="<?= $roundedSup == 1 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSup == 1 ? '✓' : '๑' ?></td>
                                    <td class="<?= $roundedSup == 2 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSup == 2 ? '✓' : '๒' ?></td>
                                    <td class="<?= $roundedSup == 3 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSup == 3 ? '✓' : '๓' ?></td>
                                    <td class="<?= $roundedSup == 4 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSup == 4 ? '✓' : '๔' ?></td>
                                    <td class="<?= $roundedSup == 5 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSup == 5 ? '✓' : '๕' ?></td>
                                    <td><?= $gw ?>%</td>
                                    <td class="fw-bold text-primary"><?= number_format($weighted, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- ภาระงานรอง ๑๐ ข้อ -->
                            <tr class="fw-bold bg-light">
                                <td colspan="14" class="text-start py-2 border-top">
                                    <strong>ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐% ในส่วนที่ ๒)</strong>
                                </td>
                            </tr>
                            <?php
                            $supSecCount = count($govtSecSelected);
                            $supSecPts = \common\services\EvaluationCalculatorService::gradeGovtSecondaryCount($supSecCount);
                            $supSecKpiVol = floatval($supGovtSecRaw['kpi_volume'] ?? $supSecPts);
                            $supSecKpiQua = floatval($supGovtSecRaw['kpi_quality'] ?? $supSecPts);
                            $supSecKpiTime = floatval($supGovtSecRaw['kpi_timeliness'] ?? $supSecPts);
                            $supSecKpiRes = floatval($supGovtSecRaw['kpi_resource'] ?? $supSecPts);
                            $supSecAvg = (($supSecKpiVol * 25) + ($supSecKpiQua * 25) + ($supSecKpiTime * 15) + ($supSecKpiRes * 15)) / 80.0;
                            $supSecWeighted = ($supSecAvg / 5.0) * 20.0;
                            $roundedSupSec = (int)round($supSecAvg);
                            ?>
                            <tr>
                                <td>-</td>
                                <td class="text-start align-top">
                                    <div class="small">
                                        <strong>รายการภาระงานรองที่ดำเนินการ (<?= $supSecCount ?>/๑๐ ข้อ):</strong>
                                        <ul class="mb-1 ps-3 mt-1 text-muted">
                                            <?php foreach ($govt10Options as $optKey => $optLabel): 
                                                $isChecked = in_array((string)$optKey, array_map('strval', $govtSecSelected));
                                                if ($isChecked):
                                            ?>
                                                <li>
                                                    <span class="text-success fw-bold">[✓]</span> <?= Html::encode($optLabel) ?>
                                                    <?php if (!empty($govtSecDetails[$optKey])): ?>
                                                        <div class="text-primary fst-italic ps-2">&bull; รายละเอียด: <?= Html::encode($govtSecDetails[$optKey]) ?></div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endif; endforeach; ?>
                                        </ul>
                                        <div class="text-muted border-top pt-1">
                                            เกณฑ์: 6-10 ข้อ=5, 5 ข้อ=4, 3-4 ข้อ=3, 2 ข้อ=2, 1 ข้อ=1 คะแนน
                                        </div>
                                    </div>
                                </td>
                                <td>ระดับ <?= $supSecPts ?></td>
                                <td><?= $supSecKpiVol ?></td>
                                <td><?= $supSecKpiQua ?></td>
                                <td><?= $supSecKpiTime ?></td>
                                <td><?= $supSecKpiRes ?></td>
                                <td class="<?= $roundedSupSec == 1 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSupSec == 1 ? '✓' : '๑' ?></td>
                                <td class="<?= $roundedSupSec == 2 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSupSec == 2 ? '✓' : '๒' ?></td>
                                <td class="<?= $roundedSupSec == 3 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSupSec == 3 ? '✓' : '๓' ?></td>
                                <td class="<?= $roundedSupSec == 4 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSupSec == 4 ? '✓' : '๔' ?></td>
                                <td class="<?= $roundedSupSec == 5 ? 'fw-bold text-success bg-light' : 'text-muted' ?>"><?= $roundedSupSec == 5 ? '✓' : '๕' ?></td>
                                <td>20%</td>
                                <td class="fw-bold text-primary"><?= number_format($supSecWeighted, 2) ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <?php 
                            $govtTotalPerf100 = $totalGovtMainScore + $supSecWeighted;
                            $govtPerfWeighted = ($govtTotalPerf100 / 100.0) * 70.0;
                            ?>
                            <tr class="fw-bold bg-light">
                                <td colspan="7" class="text-end">รวมผลการประเมินด้านผลสัมฤทธิ์ของงาน (เต็ม ๑๐๐ คะแนน):</td>
                                <td colspan="5"></td>
                                <td>๑๐๐%</td>
                                <td class="text-primary fs-6"><?= number_format($govtTotalPerf100, 2) ?></td>
                            </tr>
                            <tr class="fw-bold bg-success-subtle">
                                <td colspan="7" class="text-end text-success">คะแนนผลสัมฤทธิ์ของงาน คิดถ่วงน้ำหนัก ๗๐% ในภาพรวม:</td>
                                <td colspan="6"></td>
                                <td class="text-success fs-6"><?= number_format($govtPerfWeighted, 2) ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ความเห็นของผู้บังคับบัญชา (GOVT) -->
                <div class="mb-4 page-break-avoid">
                    <div class="doc-section-title">
                        <i class="bi bi-chat-left-text me-1 text-primary"></i> ความคิดเห็นเพิ่มเติมของผู้ประเมิน
                    </div>
                    <table class="official-table">
                        <tr>
                            <td class="bg-light fw-bold" style="width: 25%;">ข้อคิดเห็น / ข้อเสนอแนะ:</td>
                            <td><?= nl2br(Html::encode($evaluation->supervisor_comment_strength ?: ($evaluation->l1_comment_strength ?: '-'))) ?></td>
                        </tr>
                    </table>
                </div>

            <?php elseif ($isSpecial): 
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

                $selfSpec16Ans = $spec16Item ? ($selfAnswers[$spec16Item->id] ?? null) : null;
                $supSpec16Ans = $spec16Item ? ($supAnswers[$spec16Item->id] ?? $selfSpec16Ans) : null;

                $supSpec16Raw = ($supSpec16Ans && !empty($supSpec16Ans->json_value)) 
                    ? (is_string($supSpec16Ans->json_value) ? json_decode($supSpec16Ans->json_value, true) : $supSpec16Ans->json_value) 
                    : [];
                $spec16Selected = [];
                $spec16Details = [];
                if (isset($supSpec16Raw['selected']) && is_array($supSpec16Raw['selected'])) {
                    $spec16Selected = $supSpec16Raw['selected'];
                    $spec16Details = $supSpec16Raw['details'] ?? [];
                } elseif (is_array($supSpec16Raw)) {
                    $spec16Selected = $supSpec16Raw;
                }

                $spec10Options = [
                    '1' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย (พร้อมแนบหลักฐาน)',
                    '2' => 'ดำเนินการขับเคลื่อนผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ (พร้อมแนบหลักฐาน)',
                    '3' => 'คณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / EdPEx (พร้อมแนบหลักฐาน)',
                    '4' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน / LEAN / Kaizen (พร้อมแนบหลักฐาน)',
                    '5' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์ (พร้อมแนบหลักฐาน)',
                    '6' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก (พร้อมแนบหลักฐาน)',
                    '7' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (พร้อมแนบหลักฐาน)',
                    '8' => 'งานส่งเสริมความเป็นนานาชาติ / บริการวิชาการ / ทำนุบำรุงศิลปวัฒนธรรม (พร้อมแนบหลักฐาน)',
                    '9' => 'การหารายได้เข้าสำนักฯ (พร้อมแนบหลักฐาน)',
                    '10' => 'อื่น ๆ ที่ได้รับมอบหมาย (พร้อมแนบหลักฐาน)',
                ];
            ?>
                <!-- ตารางผลสัมฤทธิ์ของงานพนักงานพิเศษเงินรายได้ (ด้านที่ ๑: ๕๕ คะแนน) -->
                <div class="mb-4">
                    <div class="doc-section-title">
                        <i class="bi bi-table me-1 text-primary"></i> ด้านที่ ๑ ผลงาน / ผลสัมฤทธิ์ของงาน (๕๕ คะแนน)
                    </div>
                    <table class="official-table text-center">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th class="text-start">รายการประเมิน</th>
                                <th style="width: 90px;">คะแนนเต็ม</th>
                                <th style="width: 100px;">ตนเองให้</th>
                                <th style="width: 120px;">หัวหน้าประเมิน</th>
                                <th style="width: 120px;">ระดับผล</th>
                                <th style="width: 100px;">คะแนนที่ได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $specTotalPart1 = 0.0;
                            foreach ($specDirectItems as $sIdx => $item): 
                                $sAns = $selfAnswers[$item->id] ?? null;
                                $supAns = $supAnswers[$item->id] ?? $sAns;
                                $selfVal = $sAns ? floatval($sAns->numeric_value ?? 0) : 0;
                                $supVal = $supAns ? floatval($supAns->numeric_value ?? $selfVal) : floatval($item->max_score);
                                $specTotalPart1 += $supVal;

                                $pct = ($item->max_score > 0) ? ($supVal / $item->max_score) * 100 : 0;
                                $gradeText = ($pct >= 90) ? 'ดีมาก' : (($pct >= 80) ? 'ดี' : (($pct >= 70) ? 'พอใช้' : (($pct >= 60) ? 'ปรับปรุง' : 'ไม่ผ่าน')));
                            ?>
                                <tr>
                                    <td class="fw-bold">๑.<?= $sIdx + 1 ?></td>
                                    <td class="text-start">
                                        <strong><?= Html::encode($item->name_th) ?></strong>
                                        <?php if ($item->description): ?>
                                            <div class="small text-muted"><?= nl2br(Html::encode($item->description)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item->max_score ?></td>
                                    <td><?= number_format($selfVal, 2) ?></td>
                                    <td class="fw-bold text-dark"><?= number_format($supVal, 2) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $gradeText ?></span></td>
                                    <td class="fw-bold text-primary"><?= number_format($supVal, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- 1.6 องค์ประกอบอื่น -->
                            <?php
                            $spec16Count = count($spec16Selected);
                            $spec16Score = \common\services\EvaluationCalculatorService::gradeGovtSecondaryCount($spec16Count);
                            $specTotalPart1 += $spec16Score;
                            ?>
                            <tr>
                                <td class="fw-bold">๑.๖</td>
                                <td class="text-start align-top">
                                    <strong>องค์ประกอบอื่น ๆ (ภาระงานอื่น หรืองานที่ได้รับมอบหมาย - ๕ คะแนน)</strong>
                                    <div class="small text-muted mt-1">
                                        ดำเนินการ <?= $spec16Count ?>/๑๐ ข้อ (เกณฑ์: ๖-๑๐ ข้อ=๕, ๕ ข้อ=๔, ๓-๔ ข้อ=๓, ๒ ข้อ=๒, ๑ ข้อ=๑ คะแนน)
                                    </div>
                                    <ul class="small ps-3 mb-0 mt-1 text-muted">
                                        <?php foreach ($spec10Options as $optKey => $optLabel): 
                                            if (in_array((string)$optKey, array_map('strval', $spec16Selected))):
                                        ?>
                                            <li><span class="text-success fw-bold">[✓]</span> <?= Html::encode($optLabel) ?></li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </td>
                                <td>5</td>
                                <td><?= $spec16Score ?></td>
                                <td class="fw-bold text-dark"><?= $spec16Score ?></td>
                                <td><span class="badge bg-light text-dark border">ระดับ <?= $spec16Score ?></span></td>
                                <td class="fw-bold text-primary"><?= number_format($spec16Score, 2) ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-success-subtle">
                                <td colspan="2" class="text-end text-success">รวมคะแนนผลงาน (ด้านที่ ๑ เต็ม ๕๕ คะแนน):</td>
                                <td>๕๕</td>
                                <td colspan="3"></td>
                                <td class="text-success fs-5"><?= number_format($specTotalPart1, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ความเห็นของผู้บังคับบัญชา (SPECIAL) -->
                <div class="mb-4 page-break-avoid">
                    <div class="doc-section-title">
                        <i class="bi bi-chat-left-text me-1 text-primary"></i> ความคิดเห็นเพิ่มเติมของผู้ประเมิน
                    </div>
                    <table class="official-table">
                        <tr>
                            <td class="bg-light fw-bold" style="width: 25%;">จุดเด่น / ความถนัด:</td>
                            <td><?= nl2br(Html::encode($evaluation->supervisor_comment_strength ?: ($evaluation->l1_comment_strength ?: '-'))) ?></td>
                        </tr>
                        <tr>
                            <td class="bg-light fw-bold">จุดที่ควรพัฒนา / แก้ไข:</td>
                            <td><?= nl2br(Html::encode($evaluation->supervisor_comment_improvement ?: ($evaluation->l1_comment_improvement ?: '-'))) ?></td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Bottom Navigation for Form 2 -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top d-print-none">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="switchViewForm(1);">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ: แบบฟอร์มที่ ๑ (แบบสรุปผล)
                </button>
                <button type="button" class="btn btn-primary btn-lg shadow-sm px-4 fw-bold" onclick="switchViewForm(3);">
                    ถัดไป: แบบฟอร์มที่ ๓ (<?= $docCode3 ?> สมรรถนะ) <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- Page Break for Print between Form 2 & Form 3 -->
    <div class="page-break"></div>
    <div class="form-divider-break d-print-none my-4" style="display: none;">
        <div class="border-top border-3 border-dark text-center py-2 bg-light fw-bold text-muted">
            <i class="bi bi-arrow-down-circle me-1"></i> สิ้นสุดแบบฟอร์มที่ ๒ &bull; เริ่มต้นแบบฟอร์มที่ ๓
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- แบบฟอร์มที่ ๓ : แบบประเมินสมรรถนะ (แบบ พม.)                               -->
    <!-- ========================================================================= -->
    <div class="eval-form-pane" id="form-pane-3" style="display: none;">
        <div class="card card-rmutt official-card-wrap p-4 p-md-5 shadow-sm bg-white border mb-4">

            <!-- Form 3 Header -->
            <div class="d-flex justify-content-end mb-2">
                <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm"><?= $docCode3 ?></span>
            </div>
            <div class="text-center mb-4 pb-2 border-bottom border-dark border-2">
                <div class="official-header-title text-uppercase"><?= $docTitle3 ?></div>
                <div class="official-header-sub mt-1">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</div>
                <div class="fw-bold mt-1 text-secondary"><?= Html::encode($evaluation->cycle->name_th) ?></div>
            </div>

            <!-- ส่วนข้อมูลผู้รับการประเมินสังเขป -->
            <div class="mb-3 page-break-avoid">
                <table class="official-table">
                    <tr>
                        <td style="width: 20%;" class="fw-bold bg-light">ชื่อผู้รับการประเมิน</td>
                        <td style="width: 30%;"><?= Html::encode($personnel->fullName) ?></td>
                        <td style="width: 20%;" class="fw-bold bg-light">ตำแหน่ง / สังกัด</td>
                        <td style="width: 30%;"><?= Html::encode($personnel->position->name_th) ?> / <?= Html::encode($personnel->department->name_th) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">ผู้ประเมินขั้นต้น (L1)</td>
                        <td><?= Html::encode($evaluatorL1Name) ?></td>
                        <td class="fw-bold bg-light">ผู้ประเมินตัดสิน (L2)</td>
                        <td><?= Html::encode($evaluatorL2Name) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Competencies / Behavior / Characteristics Table -->
            <?php if ($isSpecial): 
                $spec2Items = [];
                foreach ($sections as $sec) {
                    foreach ($sec->items as $it) {
                        if (str_starts_with($it->item_code, 'SPEC_2_') || strpos($it->item_code, 'SPEC_2_') === 0) {
                            $spec2Items[] = $it;
                        }
                    }
                }
            ?>
                <!-- ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (พนักงานพิเศษเงินรายได้: ๔๕ คะแนน) -->
                <div class="mb-4 page-break-avoid">
                    <div class="doc-section-title">
                        <i class="bi bi-star me-1 text-primary"></i> ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (๔๕ คะแนน)
                    </div>
                    <table class="official-table text-center">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th class="text-start">รายการประเมินคุณลักษณะ</th>
                                <th style="width: 80px;">คะแนนเต็ม</th>
                                <th style="width: 100px;">ตนเองให้</th>
                                <th style="width: 120px;">หัวหน้าประเมิน</th>
                                <th style="width: 100px;">ระดับผล</th>
                                <th style="width: 100px;">คะแนนที่ได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $specTotalPart2 = 0.0;
                            foreach ($spec2Items as $idx => $item):
                                $sAns = $selfAnswers[$item->id] ?? null;
                                $supAns = $supAnswers[$item->id] ?? $sAns;
                                $selfVal = $sAns ? floatval($sAns->numeric_value ?? 0) : 0;
                                $supVal = $supAns ? floatval($supAns->numeric_value ?? $selfVal) : floatval($item->max_score);
                                $specTotalPart2 += $supVal;

                                $pct = ($item->max_score > 0) ? ($supVal / $item->max_score) * 100 : 0;
                                $gradeText = ($pct >= 90) ? 'ดีมาก' : (($pct >= 80) ? 'ดี' : (($pct >= 70) ? 'พอใช้' : (($pct >= 60) ? 'ปรับปรุง' : 'ไม่ผ่าน')));
                            ?>
                                <tr>
                                    <td class="fw-bold">๒.<?= $idx + 1 ?></td>
                                    <td class="text-start">
                                        <strong><?= Html::encode($item->name_th) ?></strong>
                                        <?php if ($item->description): ?>
                                            <div class="small text-muted"><?= nl2br(Html::encode($item->description)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item->max_score ?></td>
                                    <td><?= number_format($selfVal, 2) ?></td>
                                    <td class="fw-bold text-dark"><?= number_format($supVal, 2) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $gradeText ?></span></td>
                                    <td class="fw-bold text-primary"><?= number_format($supVal, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-success-subtle">
                                <td colspan="2" class="text-end text-success">รวมคะแนนคุณลักษณะการปฏิบัติงาน (ด้านที่ ๒ เต็ม ๔๕ คะแนน):</td>
                                <td>๔๕</td>
                                <td colspan="3"></td>
                                <td class="text-success fs-5"><?= number_format($specTotalPart2, 2) ?></td>
                            </tr>
                            <tr class="fw-bold bg-light">
                                <td colspan="2" class="text-end text-primary">สรุปรวมผลคะแนนทั้ง ๒ ด้าน (ด้านที่ ๑ [๕๕] + ด้านที่ ๒ [๔๕] = ๑๐๐ คะแนน):</td>
                                <td>๑๐๐</td>
                                <td colspan="3"></td>
                                <td class="text-primary fs-5"><?= number_format(($specTotalPart1 ?? 0) + $specTotalPart2, 2) ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ส่วนพิจารณาการจ้างงานต่อเนื่อง (Employment Recommendation Review) -->
                <div class="mb-4 page-break-avoid">
                    <div class="doc-section-title">
                        <i class="bi bi-person-check me-1 text-primary"></i> ความเห็นและข้อเสนอแนะในการจ้างงานต่อเนื่อง
                    </div>
                    <table class="official-table">
                        <tr>
                            <td style="width: 25%;" class="bg-light fw-bold">ข้อเสนอแนะของผู้ประเมิน:</td>
                            <td>
                                <?php
                                $rec = $evaluation->employment_recommendation;
                                $recLabels = [
                                    'continue' => 'เห็นควรให้จ้างต่อไป',
                                    'continue_conditional' => 'เห็นควรให้จ้างต่อไป โดยมีเงื่อนไขปรับปรุงการปฏิบัติงาน',
                                    'terminate' => 'ไม่เห็นควรให้จ้างต่อไป (เลิกจ้าง)',
                                ];
                                ?>
                                <strong class="text-primary fs-6"><?= Html::encode($recLabels[$rec] ?? ($rec ?: 'เห็นควรให้จ้างต่อไป')) ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="bg-light fw-bold">ความเห็น / แผนพัฒนาเพิ่มเติม:</td>
                            <td><?= nl2br(Html::encode($evaluation->supervisor_comment_suggestion ?: ($evaluation->l1_comment_suggestion ?: ($evaluation->l2_comment_suggestion ?: '-')))) ?></td>
                        </tr>
                    </table>
                </div>

            <?php elseif ($isGovt): ?>
                <!-- ส่วนที่ ๓ การประเมินพฤติกรรมการปฏิบัติงาน (พนักงานราชการ: สมรรถนะ ๕ ด้าน) -->
                <div class="mb-4 page-break-avoid">
                    <div class="doc-section-title">
                        <i class="bi bi-star me-1 text-primary"></i> ส่วนที่ ๓ การประเมินพฤติกรรมการปฏิบัติงาน (สมรรถนะ ๕ ด้าน)
                    </div>
                    <table class="official-table text-center">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ลำดับ</th>
                                <th class="text-start">รายการพฤติกรรม / สมรรถนะ</th>
                                <th style="width: 100px;">(๑)<br>ระดับคาดหวัง</th>
                                <th style="width: 110px;">ระดับที่<br>ตนเองประเมิน</th>
                                <th style="width: 120px;">ระดับที่<br>หัวหน้าประเมิน</th>
                                <th style="width: 100px;">คะแนนเต็ม</th>
                                <th style="width: 110px;">(๒)<br>คะแนนที่ได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $govtBehSum = 0.0;
                            foreach ($competencies as $cIdx => $comp):
                                $sAns = $selfCompAnswers[$comp->id] ?? null;
                                $sLvl = $sAns ? floatval($sAns->level_value ?? 0) : 0;
                                $supAns = $supCompAnswers[$comp->id] ?? $sAns;
                                $supLvl = $supAns ? floatval($supAns->level_value ?? $sLvl) : floatval($comp->expected_level ?? 3);
                                $govtBehSum += $supLvl;
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= $cIdx + 1 ?></td>
                                    <td class="text-start">
                                        <strong><?= Html::encode($comp->name_th) ?></strong>
                                        <?php if ($comp->name_en): ?>
                                            <small class="text-muted d-block">(<?= Html::encode($comp->name_en) ?>)</small>
                                        <?php endif; ?>
                                        <?php if ($comp->definition): ?>
                                            <div class="small text-muted mt-1"><?= Html::encode($comp->definition) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>ระดับ <?= $comp->expected_level ?? 3 ?></td>
                                    <td><?= $sLvl > 0 ? 'ระดับ ' . $sLvl : '-' ?></td>
                                    <td class="fw-bold text-dark"><?= $supLvl > 0 ? 'ระดับ ' . $supLvl : '-' ?></td>
                                    <td>๕</td>
                                    <td class="fw-bold text-primary"><?= number_format($supLvl, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php 
                            $govtBehPct = ($govtBehSum / 25.0) * 100.0;
                            $govtBehWeighted = ($govtBehSum / 25.0) * $compWeight;
                            ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="2" class="text-end">รวมระดับพฤติกรรม (๕ ด้าน เต็ม ๒๕ คะแนน):</td>
                                <td colspan="3"></td>
                                <td>๒๕</td>
                                <td class="text-dark fs-6"><?= number_format($govtBehSum, 2) ?></td>
                            </tr>
                            <tr class="fw-bold bg-light">
                                <td colspan="2" class="text-end">แปลงเป็นร้อยละ (คะแนนรวม ÷ ๒๕ × ๑๐๐):</td>
                                <td colspan="4"></td>
                                <td class="text-primary fs-6"><?= number_format($govtBehPct, 2) ?>%</td>
                            </tr>
                            <tr class="fw-bold bg-success-subtle">
                                <td colspan="2" class="text-end text-success">คะแนนพฤติกรรม คิดถ่วงน้ำหนัก <?= $compWeightTh ?> ในภาพรวม (ร้อยละ × <?= $compWeightTh ?>):</td>
                                <td colspan="4"></td>
                                <td class="text-success fs-5"><?= number_format($compScoreFinal, 2) ?> / <?= $isUnivOrGovt ? '๓๐' : '๒๐' ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php elseif (!empty($competencies)): ?>
                <!-- ตารางสมรรถนะข้าราชการพลเรือน / พนักงานมหาวิทยาลัย (สมรรถนะหลัก ๔ ด้าน + ประจำสายงาน ๓ ด้าน) -->
                <div class="mb-4 page-break-avoid">
                    <table class="official-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">ลำดับ</th>
                                <th>รายการสมรรถนะ</th>
                                <th style="width: 120px;">(๑)<br>ระดับคาดหวัง</th>
                                <th style="width: 130px;">ระดับที่ตนเองประเมิน</th>
                                <th style="width: 160px;">(๒)<br>ระดับที่ผู้ประเมินให้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $coreComps = [];
                            $funcComps = [];
                            foreach ($competencies as $c) {
                                if (strtoupper((string)$c->competency_type) === 'FUNCTIONAL') {
                                    $funcComps[] = $c;
                                } else {
                                    $coreComps[] = $c;
                                }
                            }
                            ?>
                            <!-- 1. สมรรถนะหลัก -->
                            <tr class="fw-bold bg-light">
                                <td colspan="5">สมรรถนะหลัก (Core Competencies) ๔ ด้าน</td>
                            </tr>
                            <?php foreach ($coreComps as $cIdx => $comp): 
                                $sAns = $selfCompAnswers[$comp->id] ?? null;
                                $sLvl = $sAns ? $sAns->level_value : '-';
                                $supAns = $supCompAnswers[$comp->id] ?? $sAns;
                                $supLvl = $supAns ? $supAns->level_value : $sLvl;
                            ?>
                                <tr>
                                    <td class="text-center"><?= $cIdx + 1 ?></td>
                                    <td>
                                        <strong><?= Html::encode($comp->name_th) ?></strong>
                                        <?php if ($comp->name_en): ?>
                                            <small class="text-muted d-block">(<?= Html::encode($comp->name_en) ?>)</small>
                                        <?php endif; ?>
                                        <?php if ($comp->definition): ?>
                                            <div class="small text-muted mt-1"><?= Html::encode($comp->definition) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">ระดับ <?= $comp->expected_level ?? 3 ?></td>
                                    <td class="text-center">ระดับ <?= $sLvl ?></td>
                                    <td class="text-center fw-bold text-primary">ระดับ <?= $supLvl ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- 2. สมรรถนะประจำสายงาน -->
                            <?php if (!empty($funcComps)): ?>
                                <tr class="fw-bold bg-light">
                                    <td colspan="5">สมรรถนะประจำสายงาน (Functional Competencies) ๓ ด้าน</td>
                                </tr>
                                <?php foreach ($funcComps as $fIdx => $comp): 
                                    $sAns = $selfCompAnswers[$comp->id] ?? null;
                                    $sLvl = $sAns ? $sAns->level_value : '-';
                                    $supAns = $supCompAnswers[$comp->id] ?? $sAns;
                                    $supLvl = $supAns ? $supAns->level_value : $sLvl;
                                ?>
                                    <tr>
                                        <td class="text-center"><?= count($coreComps) + $fIdx + 1 ?></td>
                                        <td>
                                            <strong><?= Html::encode($comp->name_th) ?></strong>
                                            <?php if ($comp->name_en): ?>
                                                <small class="text-muted d-block">(<?= Html::encode($comp->name_en) ?>)</small>
                                            <?php endif; ?>
                                            <?php if ($comp->definition): ?>
                                                <div class="small text-muted mt-1"><?= Html::encode($comp->definition) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">ระดับ <?= $comp->expected_level ?? 3 ?></td>
                                        <td class="text-center">ระดับ <?= $sLvl ?></td>
                                        <td class="text-center fw-bold text-primary">ระดับ <?= $supLvl ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="4" class="text-end">ผลรวมคะแนนสรุปส่วนสมรรถนะ คิดถ่วงน้ำหนัก <?= $compWeightTh ?> ในภาพรวม:</td>
                                <td class="text-center text-success fs-5"><?= number_format($compScoreFinal, 2) ?> / <?= $isUnivOrGovt ? '๓๐' : '๒๐' ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

            <!-- เอกสารหลักฐานประกอบการประเมินที่แนบ (Evidence Files) -->
            <div class="mb-4 page-break-avoid">
                <div class="doc-section-title">
                    <i class="bi bi-paperclip me-1 text-primary"></i> เอกสารหลักฐานประกอบการประเมินที่แนบ (Evidence Files)
                </div>
                <?php if (!empty($evidenceFiles)): ?>
                    <table class="official-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">ลำดับ</th>
                                <th>คำอธิบายหลักฐาน</th>
                                <th>ชื่อไฟล์เอกสาร</th>
                                <th style="width: 120px;" class="text-center">ขนาดไฟล์</th>
                                <th style="width: 160px;" class="text-center d-print-none">ดูไฟล์เอกสาร</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evidenceFiles as $fIdx => $file): ?>
                                <tr>
                                    <td class="text-center"><?= $fIdx + 1 ?></td>
                                    <td><?= Html::encode($file->description ?: 'เอกสารประกอบ') ?></td>
                                    <td><?= Html::encode($file->original_name) ?></td>
                                    <td class="text-center"><?= $file->formattedSize ?></td>
                                    <td class="text-center d-print-none">
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
                                        <a href="<?= $file->fileUrl ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="ดาวน์โหลด">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-3 bg-light border rounded text-muted text-center fst-italic">
                        - ไม่มีไฟล์เอกสารหลักฐานแนบเพิ่มเติม -
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bottom Navigation for Form 3 -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top d-print-none">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="switchViewForm(2);">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ: แบบฟอร์มที่ ๒ (ผลสัมฤทธิ์)
                </button>
                <button type="button" class="btn btn-success btn-lg shadow-sm px-4 fw-bold" onclick="switchViewForm('all'); window.scrollTo({top: 0, behavior: 'smooth'});">
                    <i class="bi bi-eye me-1"></i> ดูทั้ง ๓ แบบฟอร์มต่อเนื่อง (สำหรับตรวจสอบทั้งหมด / สั่งพิมพ์)
                </button>
            </div>

        </div>
    </div>

</div>

<!-- Modal Preview Evidence -->
<div class="modal fade d-print-none" id="previewEvidenceModal" tabindex="-1" aria-hidden="true">
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
function switchViewForm(formNum) {
    // Buttons state
    $('.eval-view-btn').removeClass('active');
    $('.eval-view-btn .step-badge').removeClass('bg-primary bg-dark').addClass('bg-secondary');

    if (formNum === 'all') {
        $('.eval-form-pane').show();
        $('#tab-btn-all').addClass('active');
        $('#tab-btn-all .step-badge').removeClass('bg-secondary').addClass('bg-dark');
        $('.form-divider-break').show();
    } else {
        $('.eval-form-pane').hide();
        $('#form-pane-' + formNum).fadeIn(200);
        $('#tab-btn-' + formNum).addClass('active');
        $('#tab-btn-' + formNum + ' .step-badge').removeClass('bg-secondary').addClass('bg-primary');
        $('.form-divider-break').hide();
    }
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function printOfficialForms() {
    // Temporarily switch to all forms and print
    $('.eval-form-pane').show();
    window.print();
}

function previewEvidenceFile(btn) {
    var url = btn.getAttribute('data-url');
    var name = btn.getAttribute('data-name');
    var isImage = btn.getAttribute('data-is-image') === '1';
    var isPdf = btn.getAttribute('data-is-pdf') === '1';

    var titleEl = document.getElementById('previewModalTitle');
    var linkEl = document.getElementById('previewOpenNewTab');
    var container = document.getElementById('previewModalContainer');

    if (titleEl) titleEl.innerHTML = '<i class="bi bi-file-earmark me-1 text-warning"></i> พรีวิวเอกสาร: ' + name;
    if (linkEl) linkEl.setAttribute('href', url);

    if (container) {
        if (isImage) {
            container.innerHTML = '<div class="p-3"><img src="' + url + '" class="img-fluid rounded shadow-sm d-block mx-auto" style="max-height: 75vh;" alt="' + name + '"></div>';
        } else if (isPdf) {
            container.innerHTML = '<iframe src="' + url + '" style="width:100%; height:75vh; border:none;"></iframe>';
        } else {
            container.innerHTML = '<div class="p-5 text-center"><i class="bi bi-file-earmark-zip display-1 text-muted d-block mb-3"></i><p class="fs-5 text-dark">ไฟล์ประเภทนี้ไม่สามารถแสดงพรีวิวแบบ Inline ได้</p><a href="' + url + '" target="_blank" class="btn btn-primary px-4"><i class="bi bi-download me-1"></i> ดาวน์โหลดไฟล์เอกสาร</a></div>';
        }
    }
}
</script>
