<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;
use common\models\EvaluationItem;

/** @var yii\web\View $this */
/** @var common\models\Evaluation $evaluation */
/** @var common\models\Personnel $personnel */
/** @var common\models\TemplateVersion $templateVersion */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */
/** @var common\models\EvaluationAnswer[] $selfAnswers */
/** @var common\models\EvaluationAnswer[] $supAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $selfCompAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $supCompAnswers */
/** @var common\models\EvidenceFile[] $evidenceFiles */
/** @var string $evaluatorTier 'l1' or 'l2' */
$evaluatorTier = $evaluatorTier ?? 'l1';

$this->title = ($evaluatorTier === 'l2' ? 'ประเมินตัดสิน (L2): ' : 'ตรวจประเมินขั้นต้น (L1): ') . $personnel->fullName;
$personnelType = $personnel->personnelType->code;
$isCivil = ($personnelType === 'CIVIL');
$isUniv = ($personnelType === 'UNIVERSITY');
$isGovt = ($personnelType === 'GOVT');
$isSpecial = ($personnelType === 'SPECIAL');
$perfWeight = 70.0;
$compWeight = 30.0;
$perfWeightTh = '๗๐%';
$compWeightTh = '๓๐%';
?>

<style>
.sup-score-select::-webkit-inner-spin-button,
.sup-score-select::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.sup-score-select {
    -moz-appearance: textfield;
}
</style>

<div class="supervisor-assess-view py-3">

    <!-- Top Floating Header / Status Bar -->
    <div class="card card-rmutt shadow-sm mb-4 sticky-top bg-white border-warning border-2" style="top: 75px; z-index: 1020;">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <?= Html::a('<i class="bi bi-arrow-left me-1"></i> แผงควบคุม', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    <div>
                        <strong class="text-dark">
                            <?php if ($evaluatorTier === 'l2'): ?>
                                <i class="bi bi-shield-fill-check text-primary me-1"></i> ตรวจประเมินตัดสินขั้นสุดท้าย (หัวหน้าฝ่าย L2)
                            <?php else: ?>
                                <i class="bi bi-award-fill text-warning me-1"></i> ตรวจประเมินขั้นต้น (หัวหน้างาน L1)
                            <?php endif; ?>
                        </strong>
                        <span class="badge bg-light text-dark border ms-1"><?= Html::encode($personnel->fullName) ?></span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div id="save-status" class="small text-muted">
                        <i class="bi bi-check-circle-fill text-success me-1"></i> พร้อมบันทึกคะแนน
                    </div>

                    <?php if ($isEditable): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-manual-save">
                            <i class="bi bi-cloud-arrow-up me-1"></i> บันทึกร่างคะแนน
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#returnModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> ส่งกลับแก้ไข
                        </button>
                        <?php if ($evaluatorTier === 'l2'): ?>
                            <button type="button" class="btn btn-sm btn-success px-3 shadow-sm" id="btn-submit-supervisor-eval" onclick="if(confirm('คุณต้องการยืนยันการอนุมัติและบันทึกผลการประเมินขั้นสุดท้าย (L2) ใช่หรือไม่?\n\nระบบจะทำการคำนวณคะแนนสุทธิและเปิดให้ผู้รับการประเมินลงนามรับทราบผล')){ var f=document.getElementById('supervisor-eval-form'); if(f) f.submit(); } return false;">
                                <i class="bi bi-shield-check me-1"></i> อนุมัติ & ยืนยันผลการประเมินขั้นสุดท้าย
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" id="btn-submit-supervisor-eval" onclick="if(confirm('คุณต้องการยืนยันการบันทึกผลการประเมินขั้นต้น (L1) และส่งต่อให้หัวหน้าฝ่าย (L2) ใช่หรือไม่?')){ var f=document.getElementById('supervisor-eval-form'); if(f) f.submit(); } return false;">
                                <i class="bi bi-send-check me-1"></i> บันทึก & ส่งต่อหัวหน้าฝ่าย (L2)
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-success">ประเมินเสร็จสมบูรณ์แล้ว</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Header Info Box -->
    <div class="card card-rmutt p-4 shadow-sm mb-4 bg-light border-0">
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-1 text-dark">แบบประเมินผลการปฏิบัติราชการ (สำหรับผู้บังคับบัญชา)</h5>
            <div class="text-muted small">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</div>
            <div class="fw-bold text-primary mt-1"><?= Html::encode($evaluation->cycle->name_th) ?></div>
        </div>
        <div class="row g-2 pt-2 border-top">
            <div class="col-md-4">
                <strong>ผู้รับการประเมิน:</strong> <?= Html::encode($personnel->fullName) ?> (<?= Html::encode($personnel->employee_code ?: '-') ?>)
            </div>
            <div class="col-md-4">
                <strong>ตำแหน่ง / ระดับ:</strong> <?= Html::encode($personnel->position->name_th) ?>
            </div>
            <div class="col-md-4">
                <strong>ฝ่าย / สังกัด:</strong> <?= Html::encode($personnel->department->name_th) ?>
            </div>
            <div class="col-md-4">
                <strong>ประเภทบุคลากร:</strong> <span class="badge bg-light text-dark border"><?= Html::encode($personnel->personnelType->name_th) ?></span>
            </div>
            <div class="col-md-4">
                <strong>สายการประเมิน:</strong> 
                <span class="text-muted small">
                    L1: <?= $evaluation->evaluatorL1 ? Html::encode($evaluation->evaluatorL1->fullName) : '-' ?> | 
                    L2: <?= $evaluation->evaluatorL2 ? Html::encode($evaluation->evaluatorL2->fullName) : '-' ?>
                </span>
            </div>
            <div class="col-md-4">
                <strong>สถานะ:</strong> <?= $evaluation->statusLabel ?>
            </div>
        </div>
    </div>

<?php
$csrfParam = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf';
$csrfToken = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '';
?>
    <form id="supervisor-eval-form" action="<?= Url::to(['submit-supervisor', 'id' => $evaluation->id]) ?>" method="post">
        <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
        <input type="hidden" name="evaluator_tier" value="<?= $evaluatorTier ?>">

        <!-- ========================================================================= -->
        <!-- CIVIL & UNIVERSITY EMPLOYEE: Form 2 & Form 3                              -->
        <!-- ========================================================================= -->
        <?php if (in_array($personnelType, ['CIVIL', 'UNIVERSITY'])): ?>

            <!-- FORM 2: ผลสัมฤทธิ์ของงาน (80%) -->
            <div class="card card-rmutt shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-journal-check me-2"></i> แบบ ป.ผ. : ผลสัมฤทธิ์ของงาน (ค่าน้ำหนักร้อยละ <?= ($isUniv || $isGovt) ? '๗๐' : '๘๐' ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    
                    <?php 
                    $secMain = null;
                    $secPolicy = null;
                    $secAcad = null;
                    foreach ($sections as $sec) {
                        if ($sec->section_code === 'MAIN_WORK') $secMain = $sec;
                        if ($sec->section_code === 'SECONDARY_POLICY') $secPolicy = $sec;
                        if ($sec->section_code === 'SECONDARY_ACADEMIC') $secAcad = $sec;
                    }
                    $mainItem = ($secMain && !empty($secMain->items)) ? $secMain->items[0] : null;
                    $mainAnswer = $mainItem ? ($supAnswers[$mainItem->id] ?? $selfAnswers[$mainItem->id] ?? null) : null;
                    $mainRows = $mainAnswer && $mainAnswer->json_value ? $mainAnswer->json_value : [];

                    $policyItem = ($secPolicy && !empty($secPolicy->items)) ? $secPolicy->items[0] : null;
                    $selfPolicyAns = $policyItem ? ($selfAnswers[$policyItem->id] ?? null) : null;
                    $supPolicyAns = $policyItem ? ($supAnswers[$policyItem->id] ?? $selfPolicyAns) : null;
                    $selfPolicyChecked = is_array($selfPolicyAns->json_value ?? null) ? $selfPolicyAns->json_value : [];
                    $supPolicyChecked = is_array($supPolicyAns->json_value ?? null) ? $supPolicyAns->json_value : $selfPolicyChecked;
                    
                    $selfPolicyCount = count($selfPolicyChecked);
                    $selfPolicyScore = 0;
                    if ($selfPolicyCount >= 3) $selfPolicyScore = 5;
                    elseif ($selfPolicyCount === 2) $selfPolicyScore = 3;
                    elseif ($selfPolicyCount === 1) $selfPolicyScore = 1;

                    $supPolicyCount = count($supPolicyChecked);
                    $supPolicyScore = 0;
                    if ($supPolicyCount >= 3) $supPolicyScore = 5;
                    elseif ($supPolicyCount === 2) $supPolicyScore = 3;
                    elseif ($supPolicyCount === 1) $supPolicyScore = 1;
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
                        '1' => 'ระดับ ๑: ยื่นผลงานให้ผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญพิจารณา (แนบเอกสารขอความอนุเคราะห์/ คำสั่งแต่งตั้ง) (๑ คะแนน)',
                        '2' => 'ระดับ ๒: ผ่านการพิจารณาจากผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญในงานที่เกี่ยวข้อง ตรวจเบื้องต้น (แนบแบบประเมินผลงาน) (๒ คะแนน)',
                        '3' => 'ระดับ ๓: ผ่านการพิจารณาผู้บังคับบัญชาภายในหน่วยงาน ส่งไปยัง กบค. (แนบบันทึกข้อความ) (๓ คะแนน)',
                        '4' => 'ระดับ ๔: อยู่ระหว่างการพิจารณาจาก กบค. (ใช้หลักฐานสถานะการดำเนินการจาก กบค.) (๔ คะแนน)',
                        '5' => 'ระดับ ๕: เผยแพร่ผลงานทางวิชาการ เป็นตำรา หนังสือบทความ และหรือ ได้ตำแหน่งที่สูงขึ้น (แนบคำสั่งแต่งตั้ง หรือหลักฐาน) (๕ คะแนน)',
                    ];
                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="sup-univ-table">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th rowspan="2" style="width: 45px;">#</th>
                                    <th rowspan="2" style="min-width: 220px;">(๑) กิจกรรม/โครงการ/งาน</th>
                                    <th rowspan="2" style="min-width: 320px;">(๒) ตัวชี้วัด</th>
                                    <th rowspan="2" style="width: 95px;">บุคลากร<br>ประเมินตนเอง</th>
                                    <th rowspan="2" style="width: 140px;" class="bg-warning-subtle text-dark">(๔) * ประเมินโดย<br>ผู้บังคับบัญชา</th>
                                    <th colspan="5" class="text-center py-1">(๓) ระดับค่าเป้าหมาย</th>
                                    <th rowspan="2" style="width: 70px;">(๔)<br>ค่าคะแนน<br>ที่ได้</th>
                                    <th rowspan="2" style="width: 90px;">(๕)<br>น้ำหนัก<br>(%)</th>
                                    <th rowspan="2" style="width: 105px;">(๖)<br>คะแนนถ่วงน้ำหนัก<br><small class="text-muted">((๔)X(๕))/๑๐๐</small></th>
                                </tr>
                                <tr>
                                    <th style="width: 30px;" class="text-center py-1">๑</th>
                                    <th style="width: 30px;" class="text-center py-1">๒</th>
                                    <th style="width: 30px;" class="text-center py-1">๓</th>
                                    <th style="width: 30px;" class="text-center py-1">๔</th>
                                    <th style="width: 30px;" class="text-center py-1">๕</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 1. ภาระงานหลัก -->
                                <tr class="table-light fw-bold">
                                    <td colspan="13" class="py-2 bg-light text-start">
                                        <i class="bi bi-briefcase me-1 text-primary"></i> ภาระงานหลัก (ค่าน้ำหนัก ๖๐ - ๘๐ ข้อละไม่เกิน ๓๐) ระบุงานที่ปฏิบัติ (รายละเอียดข้อมูล)
                                    </td>
                                </tr>
                                <?php if (empty($mainRows)): ?>
                                    <tr><td colspan="13" class="text-center text-muted py-3">ผู้รับการประเมินยังไม่ได้ระบุภาระงานหลัก</td></tr>
                                <?php else: ?>
                                    <?php foreach ($mainRows as $idx => $row): 
                                        $selfScore = round(floatval($row['self_score'] ?? 5), 1);
                                        $supScore = round(floatval($row['supervisor_score'] ?? $selfScore), 1);
                                        $supFloor = intval(floor($supScore));
                                        $weight = floatval($row['weight'] ?? 0);
                                        $weightedScore = ($supScore / 5.0) * $weight;
                                    ?>
                                        <tr class="sup-main-work-row">
                                            <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                                            <td>
                                                <strong class="text-dark"><?= nl2br(Html::encode($row['title'] ?? '')) ?></strong>
                                                <input type="hidden" name="main_work[<?= $idx ?>][title]" value="<?= Html::encode($row['title'] ?? '') ?>">
                                                <input type="hidden" name="main_work[<?= $idx ?>][kpi]" value="<?= Html::encode($row['kpi'] ?? '') ?>">
                                                <input type="hidden" name="main_work[<?= $idx ?>][self_score]" value="<?= Html::encode($selfScore) ?>">
                                                <input type="hidden" name="main_work[<?= $idx ?>][weight]" value="<?= Html::encode($weight) ?>">
                                            </td>
                                            <td>
                                                <div class="p-2 bg-light rounded border text-start pdca-info-box" style="font-size: 0.76rem; line-height: 1.35;">
                                                    <strong class="text-primary d-block mb-1"><i class="bi bi-info-circle me-1"></i> เกณฑ์ระดับความสำเร็จ (PDCA):</strong>
                                                    <span class="d-block mb-1 px-1 rounded pdca-line <?= $supFloor == 1 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="1"><strong>ระดับ ๑ (Plan):</strong> มีแผนการดำเนินงาน/แนวทาง</span>
                                                    <span class="d-block mb-1 px-1 rounded pdca-line <?= $supFloor == 2 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="2"><strong>ระดับ ๒ (Do):</strong> ดำเนินการตามแผน/แนวทาง</span>
                                                    <span class="d-block mb-1 px-1 rounded pdca-line <?= $supFloor == 3 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="3"><strong>ระดับ ๓ (Check):</strong> ทบทวน ตรวจสอบ ประเมินผล</span>
                                                    <span class="d-block mb-1 px-1 rounded pdca-line <?= $supFloor == 4 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="4"><strong>ระดับ ๔ (Act):</strong> แก้ไขปรับปรุงกระบวนการ</span>
                                                    <span class="d-block px-1 rounded pdca-line <?= $supFloor == 5 ? 'bg-success-subtle text-success fw-bold border border-success-subtle' : 'text-muted' ?>" data-level="5"><strong>ระดับ ๕ (Impact):</strong> ปรับปรุงต่อเนื่อง/มีนวัตกรรม</span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-light text-dark border">ระดับ <?= (round($selfScore, 1) == intval($selfScore)) ? intval($selfScore) : number_format($selfScore, 1) ?></span>
                                            </td>
                                            <td class="bg-warning-subtle text-center align-middle">
                                                <div class="input-group input-group-sm justify-content-center" style="min-width: 95px; max-width: 120px; margin: 0 auto;">
                                                    <span class="input-group-text px-1 text-muted small">ระดับ</span>
                                                    <input type="number" step="0.1" min="1" max="5" class="form-control form-control-sm text-center auto-save-field sup-score-select fw-bold border-warning" name="main_work[<?= $idx ?>][supervisor_score]" value="<?= $supScore ?>" placeholder="1-5" required <?= !$isEditable ? 'readonly' : '' ?>>
                                                </div>
                                            </td>
                                            <td class="text-center sup-main-tgt-1"><span class="<?= $supFloor == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                            <td class="text-center sup-main-tgt-2"><span class="<?= $supFloor == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                            <td class="text-center sup-main-tgt-3"><span class="<?= $supFloor == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                            <td class="text-center sup-main-tgt-4"><span class="<?= $supFloor == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                            <td class="text-center sup-main-tgt-5"><span class="<?= $supFloor == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                            <td class="text-center fw-bold text-dark sup-main-raw-score"><?= $supScore ?></td>
                                            <td class="text-center fw-bold sup-main-weight-val"><?= $weight ?>%</td>
                                            <td class="text-center fw-bold text-primary sup-row-weighted"><?= number_format($weightedScore, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- 2. งานอื่น ๆ ตามที่ได้รับมอบหมาย (๕.๑ และ ๕.๒) -->
                                <tr class="table-secondary fw-bold">
                                    <td colspan="13" class="py-2 px-3 bg-secondary-subtle text-dark text-start">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-folder2-open me-1 text-primary"></i> ๕. งานอื่น ๆ ตามที่ได้รับมอบหมาย</span>
                                            <span class="badge bg-secondary">ค่าน้ำหนักรวม ๒๐% (ข้อ ๕.๑ ๑๕% + ข้อ ๕.๒ ๕%)</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- แถบด้านบน: ๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายฯ -->
                                <tr class="fw-bold" style="background-color: #f0f4ff;">
                                    <td colspan="13" class="py-2 px-3 text-start border-top border-primary-subtle">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-primary fs-6">
                                                <i class="bi bi-bookmark-check-fill me-1"></i> <strong>๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายของมหาวิทยาลัยและสำนักฯ (ค่าน้ำหนัก ๑๕)</strong>
                                            </span>
                                            <span class="badge bg-primary">ค่าน้ำหนัก ๑๕%</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- 5.1 Policy Content Row -->
                                <tr class="sup-policy-row bg-white">
                                    <td colspan="3" class="align-top py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="text-dark small"><i class="bi bi-list-check text-primary me-1"></i> รายการนโยบายที่มีการดำเนินงาน (ระบุรายละเอียดหัวข้อที่ดำเนิน):</strong>
                                            <span class="badge bg-light text-dark border">ตรวจรับ: <span id="sup-policy-count-badge"><?= $supPolicyCount ?></span> / 7 ข้อ</span>
                                        </div>
                                        <div class="policy-checklist-box small">
                                            <?php foreach ($policyList as $pKey => $pLabel): 
                                                $isChecked = in_array($pKey, $supPolicyChecked);
                                            ?>
                                                <div class="form-check mb-1">
                                                    <input type="checkbox" class="form-check-input secondary-checkbox auto-save-field sup-policy-check" name="item_checkbox[<?= $policyItem ? $policyItem->id : 0 ?>][]" value="<?= $pKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="cursor: pointer;">
                                                    <label class="form-check-label text-dark" style="cursor: pointer;">
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
                                        <span class="badge bg-light text-dark border">ระดับ <?= $selfPolicyScore ?></span>
                                    </td>
                                    <td class="bg-warning-subtle text-center align-middle">
                                        <span class="badge bg-primary fs-6" id="sup-policy-badge">ระดับ <span id="sup-policy-score-badge"><?= $supPolicyScore ?></span></span>
                                    </td>
                                    <td class="text-center align-middle sup-policy-tgt-1"><span class="<?= $supPolicyScore == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                    <td class="text-center align-middle sup-policy-tgt-2"><span class="<?= $supPolicyScore == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                    <td class="text-center align-middle sup-policy-tgt-3"><span class="<?= $supPolicyScore == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                    <td class="text-center align-middle sup-policy-tgt-4"><span class="<?= $supPolicyScore == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                    <td class="text-center align-middle sup-policy-tgt-5"><span class="<?= $supPolicyScore == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                    <td class="text-center align-middle fw-bold text-dark" id="sup-policy-score-num"><?= $supPolicyScore ?></td>
                                    <td class="text-center align-middle fw-bold">15%</td>
                                    <td class="text-center align-middle fw-bold text-primary fs-6" id="sup-policy-weighted-num"><?= number_format($supPolicyWeighted, 2) ?></td>
                                </tr>

                                <!-- แถบด้านบน: ๕.๒ มีการจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย... -->
                                <tr class="fw-bold" style="background-color: #f0f4ff;">
                                    <td colspan="13" class="py-2 px-3 text-start border-top border-primary-subtle">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-primary fs-6">
                                                <i class="bi bi-book-half me-1"></i> <strong>๕.๒ มีการจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย ตำรา หนังสือ งานแปลตำราหรือหนังสือ งานวิเคราะห์หรืองานพัฒนา บทความทางวิชาการ เอกสารกรณีศึกษา ที่เสร็จสมบูรณ์ (ค่าน้ำหนัก ๕)</strong>
                                            </span>
                                            <span class="badge bg-primary">ค่าน้ำหนัก ๕%</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- 5.2 Academic Content Row -->
                                <tr class="sup-acad-row bg-white">
                                    <td colspan="3" class="align-top py-3">
                                        <div class="small text-muted mb-2"><strong>มีการดำเนินงาน:</strong> คู่มือปฏิบัติงาน/แผนปฏิบัติราชการ/งานวิจัย/ตำรา/งานสร้างสรรค์/หนังสือ/งานแปลตำราหรือหนังสือ/งานวิเคราะห์ ที่เข้าสู่กระบวนการขอตำแหน่งที่สูงขึ้นของมหาวิทยาลัยฯ</div>
                                        <div class="acad-radio-box small">
                                            <?php foreach ($acadLevels as $aLvl => $aText): 
                                                $isRadioChecked = strval($supAcadVal) === strval($aLvl);
                                            ?>
                                                <div class="form-check mb-1">
                                                    <input type="radio" class="form-check-input auto-save-field sup-acad-radio" name="item_radio[<?= $acadItem ? $acadItem->id : 0 ?>]" value="<?= $aLvl ?>" <?= $isRadioChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="cursor: pointer;">
                                                    <label class="form-check-label text-dark" style="cursor: pointer;">
                                                        <span class="badge bg-light text-primary border me-1">ระดับ <?= $aLvl ?></span> <?= Html::encode($aText) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="alert alert-info py-1 px-2 mt-2 mb-0 small" style="font-size: 0.75rem;">
                                            <i class="bi bi-info-circle me-1"></i> <strong>หมายเหตุ:</strong> สำหรับบุคลากรระดับชำนาญการพิเศษ จะต้องมีหลักฐานยืนยันการถ่ายทอดผลงานให้ผู้อื่น หรือช่วยเหลือสังคม (พี่เลี้ยง/ที่ปรึกษา) = ๕ คะแนน
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark border">ระดับ <?= $selfAcadVal ?></span>
                                    </td>
                                    <td class="bg-warning-subtle text-center align-middle">
                                        <span class="badge bg-primary fs-6" id="sup-acad-badge">ระดับ <span id="sup-acad-score-badge"><?= $supAcadVal ?></span></span>
                                    </td>
                                    <td class="text-center align-middle sup-acad-tgt-1"><span class="<?= $supAcadVal == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                    <td class="text-center align-middle sup-acad-tgt-2"><span class="<?= $supAcadVal == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                    <td class="text-center align-middle sup-acad-tgt-3"><span class="<?= $supAcadVal == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                    <td class="text-center align-middle sup-acad-tgt-4"><span class="<?= $supAcadVal == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                    <td class="text-center align-middle sup-acad-tgt-5"><span class="<?= $supAcadVal == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                    <td class="text-center align-middle fw-bold text-dark" id="sup-acad-score-num"><?= $supAcadVal ?></td>
                                    <td class="text-center align-middle fw-bold">5%</td>
                                    <td class="text-center align-middle fw-bold text-primary fs-6" id="sup-acad-weighted-num"><?= number_format($supAcadWeighted, 2) ?></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">รวมน้ำหนักภาระงานหลัก (น้ำหนัก ๘๐%):</td>
                                    <td colspan="6"></td>
                                    <td class="text-end fw-bold">รวม:</td>
                                    <td class="text-center fw-bold text-primary"><span id="sup-footer-main-weight">80</span>%</td>
                                    <td class="text-center fw-bold text-primary fs-6"><span id="sup-footer-main-weighted-sum">0.00</span></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">รวมงานอื่น ๆ ตามที่ได้รับมอบหมาย (ข้อ ๕.๑ ๑๕% + ข้อ ๕.๒ ๕% รวม ๒๐%):</td>
                                    <td colspan="6"></td>
                                    <td class="text-end fw-bold">รวม:</td>
                                    <td class="text-center fw-bold text-primary">20%</td>
                                    <td class="text-center fw-bold text-primary fs-6"><span id="sup-footer-sec-weighted-sum">0.00</span></td>
                                </tr>
                                <tr class="table-warning">
                                    <td colspan="4" class="text-end fw-bold text-dark">(๗) ผลรวมส่วนผลสัมฤทธิ์ของงาน (แบบที่ ๒ เต็ม ๑๐๐ คะแนน):</td>
                                    <td colspan="6"></td>
                                    <td class="text-end fw-bold text-dark">รวมน้ำหนัก:</td>
                                    <td class="text-center fw-bold text-dark fs-6"><span id="sup-footer-total-weight">100</span>%</td>
                                    <td class="text-center fw-bold text-primary fs-5"><span id="sup-footer-total-perf-sum">0.00</span></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="4" class="text-end fw-bold text-success">(๘) สรุปคะแนนผลสัมฤทธิ์ของงาน ถ่วงน้ำหนัก <?= $perfWeightTh ?> ในภาพรวม ((๗) &times; <?= $perfWeightTh ?> / ๑๐๐):</td>
                                    <td colspan="7"></td>
                                    <td class="text-center fw-bold text-success fs-5"><span id="sup-footer-total-perf-80">0.00</span>%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <script>
            function recalculateSupervisorLive() {
                var rows = document.querySelectorAll('.sup-main-work-row');
                var mainWeightSum = 0;
                var mainScoreSum = 0;
                rows.forEach(function(row) {
                    var input = row.querySelector('.sup-score-select');
                    var weightInput = row.querySelector('input[name*="[weight]"]');
                    var rawScore = input ? parseFloat(input.value) : 0;
                    var score = isNaN(rawScore) ? 0 : rawScore;
                    var w = weightInput ? (parseFloat(weightInput.value) || 0) : 0;
                    var weighted = (score / 5.0) * w;

                    var rawScoreCell = row.querySelector('.sup-main-raw-score');
                    if (rawScoreCell) rawScoreCell.textContent = score > 0 ? (Math.round(score * 10) / 10) : '-';

                    var weightedCell = row.querySelector('.sup-row-weighted');
                    if (weightedCell) weightedCell.textContent = weighted.toFixed(2);

                    mainWeightSum += w;
                    mainScoreSum += weighted;

                    var tgtFloor = Math.min(5, Math.max(1, Math.floor(score)));
                    for (var s = 1; s <= 5; s++) {
                        var cell = row.querySelector('.sup-main-tgt-' + s);
                        if (cell) {
                            if (score > 0 && s === tgtFloor) {
                                cell.innerHTML = '<span class="badge bg-primary">' + s + '</span>';
                            } else {
                                cell.innerHTML = '<span class="text-muted">' + s + '</span>';
                            }
                        }
                    }

                    var pdcaLines = row.querySelectorAll('.pdca-line');
                    pdcaLines.forEach(function(line) {
                        var lvl = parseInt(line.getAttribute('data-level'));
                        if (score > 0 && lvl === tgtFloor) {
                            line.className = 'd-block mb-1 px-1 rounded pdca-line bg-success-subtle text-success fw-bold border border-success-subtle';
                        } else {
                            line.className = 'd-block mb-1 px-1 rounded pdca-line text-muted';
                        }
                    });
                });

                var mainWeightSpan = document.getElementById('sup-footer-main-weight');
                if (mainWeightSpan) mainWeightSpan.textContent = mainWeightSum;

                var mainWeightedSpan = document.getElementById('sup-footer-main-weighted-sum');
                if (mainWeightedSpan) mainWeightedSpan.textContent = mainScoreSum.toFixed(2);

                // 5.1 Policy Checklist
                var policyChecks = document.querySelectorAll('.sup-policy-check:checked');
                var policyCheckedCount = policyChecks.length;
                var policyScore = 0;
                if (policyCheckedCount >= 3) policyScore = 5;
                else if (policyCheckedCount === 2) policyScore = 3;
                else if (policyCheckedCount === 1) policyScore = 1;
                var policyWeighted = (policyScore / 5.0) * 15.0;

                var policyCountBadge = document.getElementById('sup-policy-count-badge');
                if (policyCountBadge) policyCountBadge.textContent = policyCheckedCount;

                var policyScoreBadge = document.getElementById('sup-policy-score-badge');
                if (policyScoreBadge) policyScoreBadge.textContent = policyScore;

                var policyScoreNum = document.getElementById('sup-policy-score-num');
                if (policyScoreNum) policyScoreNum.textContent = policyScore;

                var policyWeightedNum = document.getElementById('sup-policy-weighted-num');
                if (policyWeightedNum) policyWeightedNum.textContent = policyWeighted.toFixed(2);

                for (var s1 = 1; s1 <= 5; s1++) {
                    var cell1 = document.querySelector('.sup-policy-tgt-' + s1);
                    if (cell1) {
                        if (policyScore > 0 && s1 === policyScore) {
                            cell1.innerHTML = '<span class="badge bg-primary">' + s1 + '</span>';
                        } else {
                            cell1.innerHTML = '<span class="text-muted">' + s1 + '</span>';
                        }
                    }
                }

                // 5.2 Academic Progress
                var acadChecked = document.querySelector('.sup-acad-radio:checked');
                var acadVal = acadChecked ? parseInt(acadChecked.value) : 0;
                var acadWeighted = (acadVal / 5.0) * 5.0;

                var acadScoreBadge = document.getElementById('sup-acad-score-badge');
                if (acadScoreBadge) acadScoreBadge.textContent = acadVal;

                var acadScoreNum = document.getElementById('sup-acad-score-num');
                if (acadScoreNum) acadScoreNum.textContent = acadVal;

                var acadWeightedNum = document.getElementById('sup-acad-weighted-num');
                if (acadWeightedNum) acadWeightedNum.textContent = acadWeighted.toFixed(2);

                for (var s2 = 1; s2 <= 5; s2++) {
                    var cell2 = document.querySelector('.sup-acad-tgt-' + s2);
                    if (cell2) {
                        if (acadVal > 0 && s2 === acadVal) {
                            cell2.innerHTML = '<span class="badge bg-primary">' + s2 + '</span>';
                        } else {
                            cell2.innerHTML = '<span class="text-muted">' + s2 + '</span>';
                        }
                    }
                }

                // Footers
                var secSum = policyWeighted + acadWeighted;
                var totalPerf = mainScoreSum + secSum;
                var totalWeight = mainWeightSum + 20;
                var perf80 = (totalPerf / 100.0) * <?= (float)$perfWeight ?>;

                var secWeightedSpan = document.getElementById('sup-footer-sec-weighted-sum');
                if (secWeightedSpan) secWeightedSpan.textContent = secSum.toFixed(2);

                var totalWeightSpan = document.getElementById('sup-footer-total-weight');
                if (totalWeightSpan) totalWeightSpan.textContent = totalWeight;

                var totalPerfSumSpan = document.getElementById('sup-footer-total-perf-sum');
                if (totalPerfSumSpan) totalPerfSumSpan.textContent = totalPerf.toFixed(2);

                var totalPerf80Span = document.getElementById('sup-footer-total-perf-80');
                if (totalPerf80Span) totalPerf80Span.textContent = perf80.toFixed(2);
            }

            document.addEventListener('input', function(e) {
                if (e.target && (e.target.classList.contains('sup-score-select') || e.target.classList.contains('sup-policy-check') || e.target.classList.contains('sup-acad-radio') || e.target.classList.contains('auto-save-field'))) {
                    recalculateSupervisorLive();
                }
            });
            document.addEventListener('change', function(e) {
                if (e.target && (e.target.classList.contains('sup-score-select') || e.target.classList.contains('sup-policy-check') || e.target.classList.contains('sup-acad-radio') || e.target.classList.contains('auto-save-field'))) {
                    recalculateSupervisorLive();
                }
            });
            document.addEventListener('DOMContentLoaded', recalculateSupervisorLive);
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                recalculateSupervisorLive();
            }
            </script>

            <!-- FORM 3: สมรรถนะ (20%) -->
            <div class="card card-rmutt shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-award-fill me-2"></i> แบบข้อตกลงการประเมินขีดความสามารถ/สมรรถนะ (แบบที่ ๓)
                    </h5>
                    <small class="text-white-50">น้ำหนักรวม <?= $compWeightTh ?> (สมรรถนะหลัก ๔ ด้าน + สมรรถนะประจำสายงาน ๓ ด้าน)</small>
                </div>
                <div class="alert alert-info py-2 px-3 mb-0 rounded-0 border-0 border-bottom small">
                    <i class="bi bi-info-circle-fill me-1"></i> <strong>หมายเหตุ:</strong> แบบที่ ๓ (สมรรถนะ ค่าน้ำหนัก <?= $compWeightTh ?>) จะเว้นไว้สำหรับให้ผู้บริหารนำเอกสารไปประเมินนอกระบบ
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="min-width: 200px;">สมรรถนะ</th>
                                    <th style="width: 100px;">(๑)<br>ระดับคาดหวัง</th>
                                    <th style="width: 110px;">ตนเองให้</th>
                                    <th style="width: 150px;" class="bg-light text-muted">(๒)<br>ผู้บริหารประเมิน</th>
                                    <th style="min-width: 160px;">สรุป GAP/ปัญหา</th>
                                    <th style="width: 100px;">ความสำคัญ</th>
                                    <th style="min-width: 200px;">แผน IDP (ของตนเอง)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($competencies as $idx => $comp): 
                                    $selfCAns = $selfCompAnswers[$comp->id] ?? null;
                                    $selfLvl = $selfCAns ? $selfCAns->level_value : '-';
                                    $gap = $selfCAns ? $selfCAns->gap_summary : '';
                                    $importance = $selfCAns ? $selfCAns->importance : 'กลาง';
                                    $idp = $selfCAns ? $selfCAns->idp_plan : '';
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                                        <td>
                                            <strong class="text-dark d-block"><?= Html::encode($comp->name_th) ?></strong>
                                            <?php if ($comp->name_en): ?>
                                                <small class="text-primary d-block">(<?= Html::encode($comp->name_en) ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary fs-6">ระดับ <?= $comp->expected_level ?? 3 ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">ระดับ <?= $selfLvl ?></span>
                                        </td>
                                        <td class="bg-light text-center text-muted align-middle">
                                            <span class="small text-muted">-</span>
                                        </td>
                                        <td class="small">
                                            <?= $gap ? Html::encode($gap) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= Html::encode($importance ?: 'กลาง') ?></span>
                                        </td>
                                        <td class="small">
                                            <?= $idp ? Html::encode($idp) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-light border-top text-center text-muted small">
                        <i class="bi bi-info-circle me-1"></i> ส่วนการประเมินและคิดคะแนนสมรรถนะ (GAP ค่าน้ำหนัก <?= $compWeightTh ?>) รวมถึงการสรุปผลคะแนนรวม ๑๐๐% จะดำเนินการโดยผู้บริหาร
                    </div>

                </div>
            </div>

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

        $selfGovtMainAns = $govtMainItem ? ($selfAnswers[$govtMainItem->id] ?? null) : null;
        $supGovtMainAns = $govtMainItem ? ($supAnswers[$govtMainItem->id] ?? $selfGovtMainAns) : null;
        
        $defaultGovtRows = [
            ['title' => 'ปฏิบัติงานด้านธุรการ สารบรรณ และการจัดทำเอกสารราชการ', 'kpi_volume' => 5, 'kpi_quality' => 5, 'kpi_timeliness' => 5, 'kpi_resource' => 5, 'weight' => 40],
            ['title' => 'ประสานงานการจัดประชุม สัมมนา และกิจกรรมของสำนักวิทยบริการฯ', 'kpi_volume' => 4, 'kpi_quality' => 5, 'kpi_timeliness' => 4, 'kpi_resource' => 4, 'weight' => 40],
        ];

        $selfGovtRows = ($selfGovtMainAns && !empty($selfGovtMainAns->json_value)) 
            ? (is_string($selfGovtMainAns->json_value) ? json_decode($selfGovtMainAns->json_value, true) : $selfGovtMainAns->json_value) 
            : $defaultGovtRows;

        if (empty($selfGovtRows)) {
            $selfGovtRows = $defaultGovtRows;
        }

        $supGovtRows = ($supGovtMainAns && !empty($supGovtMainAns->json_value)) 
            ? (is_string($supGovtMainAns->json_value) ? json_decode($supGovtMainAns->json_value, true) : $supGovtMainAns->json_value) 
            : $selfGovtRows;

        if (empty($supGovtRows)) {
            $supGovtRows = $selfGovtRows;
        }

        $selfGovtSecAns = $govtSecItem ? ($selfAnswers[$govtSecItem->id] ?? null) : null;
        $supGovtSecAns = $govtSecItem ? ($supAnswers[$govtSecItem->id] ?? $selfGovtSecAns) : null;

        $selfGovtSecRaw = ($selfGovtSecAns && !empty($selfGovtSecAns->json_value)) 
            ? (is_string($selfGovtSecAns->json_value) ? json_decode($selfGovtSecAns->json_value, true) : $selfGovtSecAns->json_value) 
            : [];
        $selfGovtSecSelected = [];
        $selfGovtSecDetails = [];
        if (isset($selfGovtSecRaw['selected']) && is_array($selfGovtSecRaw['selected'])) {
            $selfGovtSecSelected = $selfGovtSecRaw['selected'];
            $selfGovtSecDetails = $selfGovtSecRaw['details'] ?? [];
        } elseif (is_array($selfGovtSecRaw)) {
            $selfGovtSecSelected = $selfGovtSecRaw;
            $selfGovtSecDetails = [];
        }

        $supGovtSecRaw = ($supGovtSecAns && !empty($supGovtSecAns->json_value)) 
            ? (is_string($supGovtSecAns->json_value) ? json_decode($supGovtSecAns->json_value, true) : $supGovtSecAns->json_value) 
            : $selfGovtSecRaw;
        $govtSecSelected = [];
        $govtSecDetails = [];
        if (isset($supGovtSecRaw['selected']) && is_array($supGovtSecRaw['selected'])) {
            $govtSecSelected = $supGovtSecRaw['selected'];
            $govtSecDetails = $supGovtSecRaw['details'] ?? $selfGovtSecDetails;
        } elseif (is_array($supGovtSecRaw)) {
            $govtSecSelected = $supGovtSecRaw;
            $govtSecDetails = $selfGovtSecDetails;
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
    ?>

        <!-- UNIFIED GOVT SECTION 2: การประเมินผลสัมฤทธิ์ของงาน (น้ำหนักรวม ๑๐๐) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">ส่วนที่ ๒ การประเมินผลสัมฤทธิ์ของงาน (น้ำหนักรวม ๑๐๐%)</h5>
                    <small class="text-white-50">ภาระงานหลัก (ค่าน้ำหนัก ๘๐%) และ ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐%)</small>
                </div>
                <div class="badge bg-warning text-dark fs-6">
                    น้ำหนักรวม: 100% / 100%
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="sup-govt-perf-unified-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th rowspan="2" style="width: 40px;">#</th>
                                <th rowspan="2" style="min-width: 280px;">หน้าที่ / ภารกิจ</th>
                                <th rowspan="2" style="width: 95px;">บุคลากร<br>ประเมินตนเอง</th>
                                <th colspan="4" class="text-center bg-warning-subtle text-dark">ผู้บังคับบัญชาประเมิน: ๔ ปัจจัย (๘๐ คะแนน)</th>
                                <th colspan="5" class="text-center">ระดับค่าเป้าหมาย (ก)</th>
                                <th rowspan="2" style="width: 85px;">น้ำหนัก(%)<br>(ข)</th>
                                <th rowspan="2" style="width: 100px;">คะแนน (ค)<br><small>(กxข)/๑๐๐</small></th>
                            </tr>
                            <tr>
                                <th style="width: 92px;" class="bg-warning-subtle text-dark"><small>ปริมาณ (25)</small></th>
                                <th style="width: 92px;" class="bg-warning-subtle text-dark"><small>คุณภาพ (25)</small></th>
                                <th style="width: 92px;" class="bg-warning-subtle text-dark"><small>ตรงเวลา (15)</small></th>
                                <th style="width: 92px;" class="bg-warning-subtle text-dark"><small>คุ้มค่า (15)</small></th>
                                <th style="width: 35px;" class="small">1</th>
                                <th style="width: 35px;" class="small">2</th>
                                <th style="width: 35px;" class="small">3</th>
                                <th style="width: 35px;" class="small">4</th>
                                <th style="width: 35px;" class="small">5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Main Work Header -->
                            <tr class="table-secondary fw-bold">
                                <td colspan="14" class="py-2">
                                    <i class="bi bi-briefcase me-1 text-primary"></i> ภาระงานหลัก (ค่าน้ำหนัก ๘๐) รายละเอียดภาระงานที่ปฏิบัติ (เอกสารแนบ)
                                </td>
                            </tr>
                            <?php 
                            $supTotalMainScore = 0;
                            $supTotalMainWeight = 0;
                            foreach ($supGovtRows as $gIdx => $gRow): 
                                $selfRow = $selfGovtRows[$gIdx] ?? $gRow;
                                $sk1 = floatval($selfRow['kpi_volume'] ?? 5);
                                $sk2 = floatval($selfRow['kpi_quality'] ?? 5);
                                $sk3 = floatval($selfRow['kpi_timeliness'] ?? 5);
                                $sk4 = floatval($selfRow['kpi_resource'] ?? 5);
                                $selfAvg = (($sk1 * 25) + ($sk2 * 25) + ($sk3 * 15) + ($sk4 * 15)) / 100.0;

                                $k1 = floatval($gRow['kpi_volume'] ?? $sk1);
                                $k2 = floatval($gRow['kpi_quality'] ?? $sk2);
                                $k3 = floatval($gRow['kpi_timeliness'] ?? $sk3);
                                $k4 = floatval($gRow['kpi_resource'] ?? $sk4);
                                $gw = floatval($gRow['weight'] ?? 40);
                                $supAvg = (($k1 * 25) + ($k2 * 25) + ($k3 * 15) + ($k4 * 15)) / 80.0;
                                $weighted = ($supAvg / 5.0) * $gw;
                                $roundedSup = (int)round($supAvg);

                                $supTotalMainScore += $weighted;
                                $supTotalMainWeight += $gw;
                            ?>
                                <tr class="sup-govt-main-row">
                                    <td class="text-center fw-bold"><?= $gIdx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= Html::encode($gRow['title'] ?? '') ?></strong>
                                        <input type="hidden" name="govt_main[<?= $gIdx ?>][title]" value="<?= Html::encode($gRow['title'] ?? '') ?>">
                                        <input type="hidden" class="sup-govt-work-weight" name="govt_main[<?= $gIdx ?>][weight]" value="<?= $gw ?>">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">ระดับ <?= number_format($selfAvg, 1) ?></span>
                                    </td>
                                    <td class="bg-warning-subtle text-center">
                                        <select class="form-select form-select-sm auto-save-field sup-govt-kpi sup-govt-kpi-vol" name="govt_main[<?= $gIdx ?>][kpi_volume]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k1 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="bg-warning-subtle text-center">
                                        <select class="form-select form-select-sm auto-save-field sup-govt-kpi sup-govt-kpi-qua" name="govt_main[<?= $gIdx ?>][kpi_quality]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k2 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="bg-warning-subtle text-center">
                                        <select class="form-select form-select-sm auto-save-field sup-govt-kpi sup-govt-kpi-time" name="govt_main[<?= $gIdx ?>][kpi_timeliness]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k3 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="bg-warning-subtle text-center">
                                        <select class="form-select form-select-sm auto-save-field sup-govt-kpi sup-govt-kpi-res" name="govt_main[<?= $gIdx ?>][kpi_resource]" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= $k4 == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center sup-govt-tgt-col sup-govt-tgt-1"><span class="<?= $roundedSup == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                    <td class="text-center sup-govt-tgt-col sup-govt-tgt-2"><span class="<?= $roundedSup == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                    <td class="text-center sup-govt-tgt-col sup-govt-tgt-3"><span class="<?= $roundedSup == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                    <td class="text-center sup-govt-tgt-col sup-govt-tgt-4"><span class="<?= $roundedSup == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                    <td class="text-center sup-govt-tgt-col sup-govt-tgt-5"><span class="<?= $roundedSup == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                    <td class="text-center fw-bold"><?= $gw ?>%</td>
                                    <td class="text-center fw-bold text-primary sup-govt-row-weighted"><?= number_format($weighted, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Secondary Work Header -->
                            <tr class="table-light fw-bold">
                                <td colspan="14" class="py-2 bg-light">
                                    <i class="bi bi-check2-square me-1 text-primary"></i> ๖. ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐)
                                </td>
                            </tr>
                            <?php 
                            $govtSecItemId = $govtSecItem ? $govtSecItem->id : 0;
                            $selfSecCount = count($selfGovtSecSelected);
                            $selfSecPts = \common\services\EvaluationCalculatorService::gradeGovtSecondaryCount($selfSecCount);
                            $selfSecAvg = (($selfGovtSecRaw['kpi_volume'] ?? $selfSecPts) * 25 + ($selfGovtSecRaw['kpi_quality'] ?? $selfSecPts) * 25 + ($selfGovtSecRaw['kpi_timeliness'] ?? $selfSecPts) * 15 + ($selfGovtSecRaw['kpi_resource'] ?? $selfSecPts) * 15) / 80.0;
                            
                            $supSecCount = count($govtSecSelected);
                            $supSecPts = \common\services\EvaluationCalculatorService::gradeGovtSecondaryCount($supSecCount);
                            $supSecKpiVol = floatval($supGovtSecRaw['kpi_volume'] ?? ($selfGovtSecRaw['kpi_volume'] ?? $supSecPts));
                            $supSecKpiQua = floatval($supGovtSecRaw['kpi_quality'] ?? ($selfGovtSecRaw['kpi_quality'] ?? $supSecPts));
                            $supSecKpiTime = floatval($supGovtSecRaw['kpi_timeliness'] ?? ($selfGovtSecRaw['kpi_timeliness'] ?? $supSecPts));
                            $supSecKpiRes = floatval($supGovtSecRaw['kpi_resource'] ?? ($selfGovtSecRaw['kpi_resource'] ?? $supSecPts));
                            $supSecAvg = (($supSecKpiVol * 25) + ($supSecKpiQua * 25) + ($supSecKpiTime * 15) + ($supSecKpiRes * 15)) / 80.0;
                            $supSecWeighted = ($supSecAvg / 5.0) * 20.0;
                            $roundedSupSec = (int)round($supSecAvg);
                            ?>
                            <tr class="sup-govt-sec-row bg-white">
                                <td class="text-center fw-bold align-middle">6</td>
                                <td>
                                    <div class="fw-bold text-dark">๖. ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐)</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-check-circle text-success me-1"></i>เลือกปฏิบัติ <?= $supSecCount ?> / 10 ข้อ
                                        </span>
                                        <a href="#sup-govt-sec-evidence-card" class="btn btn-xs btn-outline-primary shadow-sm">
                                            <i class="bi bi-folder-check me-1"></i> ตรวจสอบรายละเอียดและเอกสารหลักฐานแนบ
                                        </a>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-dark border">ระดับ <?= number_format($selfSecAvg, 1) ?></span>
                                </td>
                                <td class="bg-warning-subtle text-center">
                                    <select class="form-select form-select-sm auto-save-field sup-govt-sec-kpi" name="sup_govt_sec[kpi_volume]" id="sup-govt-sec-kpi-vol" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $supSecKpiVol == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="bg-warning-subtle text-center">
                                    <select class="form-select form-select-sm auto-save-field sup-govt-sec-kpi" name="sup_govt_sec[kpi_quality]" id="sup-govt-sec-kpi-qua" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $supSecKpiQua == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="bg-warning-subtle text-center">
                                    <select class="form-select form-select-sm auto-save-field sup-govt-sec-kpi" name="sup_govt_sec[kpi_timeliness]" id="sup-govt-sec-kpi-time" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $supSecKpiTime == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="bg-warning-subtle text-center">
                                    <select class="form-select form-select-sm auto-save-field sup-govt-sec-kpi" name="sup_govt_sec[kpi_resource]" id="sup-govt-sec-kpi-res" <?= !$isEditable ? 'disabled' : '' ?>>
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <option value="<?= $s ?>" <?= $supSecKpiRes == $s ? 'selected' : '' ?>>ระดับ <?= $s ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="text-center align-middle sup-govt-sec-tgt-col sup-govt-sec-tgt-1"><span class="<?= $roundedSupSec == 1 ? 'badge bg-primary' : 'text-muted' ?>">1</span></td>
                                <td class="text-center align-middle sup-govt-sec-tgt-col sup-govt-sec-tgt-2"><span class="<?= $roundedSupSec == 2 ? 'badge bg-primary' : 'text-muted' ?>">2</span></td>
                                <td class="text-center align-middle sup-govt-sec-tgt-col sup-govt-sec-tgt-3"><span class="<?= $roundedSupSec == 3 ? 'badge bg-primary' : 'text-muted' ?>">3</span></td>
                                <td class="text-center align-middle sup-govt-sec-tgt-col sup-govt-sec-tgt-4"><span class="<?= $roundedSupSec == 4 ? 'badge bg-primary' : 'text-muted' ?>">4</span></td>
                                <td class="text-center align-middle sup-govt-sec-tgt-col sup-govt-sec-tgt-5"><span class="<?= $roundedSupSec == 5 ? 'badge bg-primary' : 'text-muted' ?>">5</span></td>
                                <td class="text-center fw-bold fs-6 align-middle">20%</td>
                                <td class="text-center fw-bold text-primary fs-6 align-middle" id="sup-govt-sec-row-score"><?= number_format($supSecWeighted, 2) ?></td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="11" class="text-end fw-bold">รวมคะแนนภาระงานหลัก (น้ำหนัก ๘๐%):</td>
                                <td class="text-center fw-bold"><?= $supTotalMainWeight ?>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="sup-govt-main-weighted-sum"><?= number_format($supTotalMainScore, 2) ?></span></td>
                            </tr>
                            <tr>
                                <td colspan="11" class="text-end fw-bold">รวมคะแนนภาระงานรอง (น้ำหนัก ๒๐%):</td>
                                <td class="text-center fw-bold">20%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="sup-govt-sec-weighted-sum"><?= number_format($supSecWeighted, 2) ?></span></td>
                            </tr>
                            <tr class="table-warning">
                                <td colspan="11" class="text-end fw-bold">รวมคะแนนด้านผลสัมฤทธิ์ของงานทั้งหมด (ส่วนที่ ๒ เต็ม ๑๐๐%):</td>
                                <td class="text-center fw-bold text-dark">100%</td>
                                <td class="text-center fw-bold text-success fs-5"><span id="sup-govt-perf-total-sum"><?= number_format($supTotalMainScore + $supSecWeighted, 2) ?></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- SEPARATE EVIDENCE REVIEW CARD FOR GOVT SECONDARY (10 ITEMS) -->
        <div class="card card-rmutt shadow-sm mb-4" id="sup-govt-sec-evidence-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-folder-check me-2"></i>รายละเอียดและเอกสารหลักฐานแนบภาระงานรองหรืองานที่ได้รับมอบหมาย (ข้อ ๖)</h5>
                    <small class="text-muted">ค่าน้ำหนัก ๒๐% (ตรวจสอบรายละเอียดและไฟล์หลักฐานแนบของผู้รับการประเมิน)</small>
                </div>
                <span class="badge bg-primary fs-6 px-3 py-2" id="sup-govt-sec-badge-pill">
                    เลือกปฏิบัติ <?= $supSecCount ?> / 10 ข้อ
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

                <div class="sup-govt-sec-checklist">
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
                    ?>
                        <div class="card mb-3 border rounded shadow-sm <?= $isChecked ? 'border-primary' : '' ?>">
                            <div class="card-header bg-light py-2 d-flex align-items-center gap-2">
                                <input class="form-check-input auto-save-field sup-govt-sec-checkbox" type="checkbox" name="govt_sec_check[]" value="<?= $optKey ?>" id="sup_govt_sec_<?= $optKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark flex-grow-1 mb-0" for="sup_govt_sec_<?= $optKey ?>" style="cursor: pointer;">
                                    (<?= $optKey ?>) <?= Html::encode($optLabel) ?>
                                </label>
                                <?php if ($isChecked): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">ปฏิบัติ</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($optEntries) && $isChecked): ?>
                                <div class="card-body p-3">
                                    <?php foreach ($optEntries as $eIdx => $entry): 
                                        $hasFile = !empty($entry['file_id']) || !empty($entry['file_url']);
                                        $fileName = $entry['file_name'] ?? '';
                                        $fileUrl = $entry['file_url'] ?? '';
                                        $isImage = (bool)preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fileName);
                                        $isPdf = (bool)preg_match('/\.pdf$/i', $fileName);
                                    ?>
                                        <div class="p-2 border rounded bg-white shadow-sm mb-2">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div>
                                                    <span class="badge bg-light text-muted border me-1">#<?= $eIdx + 1 ?></span>
                                                    <strong class="text-dark"><?= Html::encode($entry['detail'] ?: '(ไม่ได้ระบุรายละเอียด)') ?></strong>
                                                </div>
                                                <div>
                                                    <?php if ($hasFile): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-xs btn-outline-info shadow-sm" 
                                                                    data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                                                    onclick="previewEvidenceFile(this)"
                                                                    data-url="<?= Html::encode($fileUrl) ?>" 
                                                                    data-name="<?= Html::encode($fileName) ?>"
                                                                    data-is-image="<?= $isImage ? '1' : '0' ?>"
                                                                    data-is-pdf="<?= $isPdf ? '1' : '0' ?>"
                                                                    title="พรีวิวหลักฐาน">
                                                                <i class="bi bi-eye me-1"></i> พรีวิว: <?= Html::encode(mb_strimwidth($fileName, 0, 25, '...')) ?>
                                                            </button>
                                                            <a href="<?= Html::encode($fileUrl ?: '#') ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="ดาวน์โหลด">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i> ไม่มีหลักฐานแนบ</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 3. GOVT BEHAVIOR COMPETENCIES (20%) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0 text-white">ส่วนที่ ๓ การประเมินพฤติกรรมการปฏิบัติงาน (ค่าน้ำหนัก <?= $compWeightTh ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="min-width: 200px;">สมรรถนะ / พฤติกรรมที่ประเมิน</th>
                                <th style="width: 130px;">ระดับที่คาดหวัง</th>
                                <th style="width: 140px;">ตนเองให้</th>
                                <th style="width: 220px;" class="bg-warning-subtle text-dark">* หัวหน้าประเมิน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competencies as $cIdx => $comp): 
                                $sAns = $selfCompAnswers[$comp->id] ?? null;
                                $supAns = $supCompAnswers[$comp->id] ?? $sAns;
                                $selfLvl = $sAns ? $sAns->level_value : 3;
                                $supLvl = $supAns ? $supAns->level_value : $selfLvl;
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $cIdx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= Html::encode($comp->name_th) ?></strong>
                                        <small class="text-muted d-block lh-sm"><?= nl2br(Html::encode($comp->definition)) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">ระดับ <?= $comp->expected_level ?? 3 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">ระดับ <?= $selfLvl ?></span>
                                    </td>
                                    <td class="bg-warning-subtle text-center">
                                        <select class="form-select form-select-sm auto-save-field comp-level-select fw-bold border-warning" name="comp[<?= $comp->id ?>][level]" data-comp-id="<?= $comp->id ?>" <?= !$isEditable ? 'disabled' : '' ?>>
                                            <option value="5" <?= $supLvl == 5 ? 'selected' : '' ?>>ระดับ ๕ (เกินกว่าที่กำหนดมาก)</option>
                                            <option value="4" <?= $supLvl == 4 ? 'selected' : '' ?>>ระดับ ๔ (เกินกว่าที่กำหนด)</option>
                                            <option value="3" <?= $supLvl == 3 ? 'selected' : '' ?>>ระดับ ๓ (ตามกำหนด)</option>
                                            <option value="2" <?= $supLvl == 2 ? 'selected' : '' ?>>ระดับ ๒ (ต่ำกว่ากำหนด)</option>
                                            <option value="1" <?= $supLvl == 1 ? 'selected' : '' ?>>ระดับ ๑ (ต่ำกว่ากำหนดมาก)</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. GOVT EVALUATION SUMMARY (ส่วนที่ ๔ การสรุปผลการประเมิน) -->
        <div class="card card-rmutt shadow-sm mb-4 border-success">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white"><i class="bi bi-calculator me-2"></i>ส่วนที่ ๔ การสรุปผลการประเมิน (สำหรับผู้บังคับบัญชา)</h5>
                    <small class="text-white-50">สรุปคะแนนตามแบบฟอร์ม: ผลสัมฤทธิ์ของงาน (น้ำหนัก <?= $perfWeightTh ?>) + พฤติกรรมการปฏิบัติงาน (น้ำหนัก <?= $compWeightTh ?>) รวม ๑๐๐%</small>
                </div>
                <div>
                    <span class="badge bg-light text-dark fs-6">ระดับผลการประเมิน: <span id="sup-govt-summary-level" class="text-success fw-bold">ดีเด่น</span></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>องค์ประกอบการประเมิน</th>
                                <th style="width: 140px;">คะแนนตนเอง</th>
                                <th style="width: 160px;" class="bg-warning-subtle text-dark">* คะแนนหัวหน้า (ก)<br><small class="text-muted">(เต็ม ๑๐๐)</small></th>
                                <th style="width: 120px;">น้ำหนัก (ข)</th>
                                <th style="width: 200px;">รวมคะแนน (ก x ข) / ๑๐๐</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>๑. ผลการประเมินด้านผลสัมฤทธิ์ของงาน</strong>
                                    <div class="small text-muted ps-3">
                                        - ภาระงานหลัก (น้ำหนัก ๘๐%): <span id="sup-govt-summary-main-score">0.00</span> คะแนน<br>
                                        - ภาระงานรอง (น้ำหนัก ๒๐%): <span id="sup-govt-summary-sec-score">0.00</span> คะแนน
                                    </div>
                                </td>
                                <td class="text-center text-muted"><span id="sup-govt-self-perf-score">0.00</span></td>
                                <td class="text-center fw-bold bg-warning-subtle fs-6"><span id="sup-govt-summary-perf-score">0.00</span> / 100</td>
                                <td class="text-center fw-bold"><?= $perfWeight ?>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="sup-govt-summary-perf-weighted">0.00</span> / <?= $perfWeight ?>%</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>๒. ผลการประเมินด้านพฤติกรรมการปฏิบัติงาน</strong>
                                    <div class="small text-muted ps-3">
                                        - สมรรถนะ ๕ ด้าน (คะแนนเฉลี่ยระดับ ๑ - ๕)
                                    </div>
                                </td>
                                <td class="text-center text-muted"><span id="sup-govt-self-beh-score">0.00</span></td>
                                <td class="text-center fw-bold bg-warning-subtle fs-6"><span id="sup-govt-summary-beh-score">0.00</span> / 100</td>
                                <td class="text-center fw-bold"><?= $compWeight ?>%</td>
                                <td class="text-center fw-bold text-primary fs-6"><span id="sup-govt-summary-beh-weighted">0.00</span> / <?= $compWeight ?>%</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-success table-hover">
                            <tr class="fw-bold fs-6">
                                <td class="text-end">รวมคะแนนสุทธิทั้งสิ้น:</td>
                                <td class="text-center text-muted"><span id="sup-govt-self-total">0.00</span>%</td>
                                <td class="text-center bg-warning-subtle text-muted">-</td>
                                <td class="text-center">100%</td>
                                <td class="text-center text-success fs-5"><span id="sup-govt-summary-total">0.00</span>%</td>
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
                        <span class="text-dark fw-bold">สรุปผลการประเมินโดยผู้บังคับบัญชา</span>
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

        $selfSpec16Ans = $spec16Item ? ($selfAnswers[$spec16Item->id] ?? null) : null;
        $supSpec16Ans = $spec16Item ? ($supAnswers[$spec16Item->id] ?? $selfSpec16Ans) : null;

        $selfSpec16Raw = ($selfSpec16Ans && !empty($selfSpec16Ans->json_value)) 
            ? (is_string($selfSpec16Ans->json_value) ? json_decode($selfSpec16Ans->json_value, true) : $selfSpec16Ans->json_value) 
            : [];
        $selfSpec16Selected = [];
        $selfSpec16Details = [];
        if (isset($selfSpec16Raw['selected']) && is_array($selfSpec16Raw['selected'])) {
            $selfSpec16Selected = $selfSpec16Raw['selected'];
            $selfSpec16Details = $selfSpec16Raw['details'] ?? [];
        } elseif (is_array($selfSpec16Raw)) {
            $selfSpec16Selected = $selfSpec16Raw;
            $selfSpec16Details = [];
        }

        $supSpec16Raw = ($supSpec16Ans && !empty($supSpec16Ans->json_value)) 
            ? (is_string($supSpec16Ans->json_value) ? json_decode($supSpec16Ans->json_value, true) : $supSpec16Ans->json_value) 
            : $selfSpec16Raw;
        $spec16Selected = [];
        $spec16Details = [];
        if (isset($supSpec16Raw['selected']) && is_array($supSpec16Raw['selected'])) {
            $spec16Selected = $supSpec16Raw['selected'];
            $spec16Details = $supSpec16Raw['details'] ?? $selfSpec16Details;
        } elseif (is_array($supSpec16Raw)) {
            $spec16Selected = $supSpec16Raw;
            $spec16Details = $selfSpec16Details;
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
            <div class="card-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0 text-white">ด้านที่ ๑ ผลสัมฤทธิ์ของงาน / ผลงาน (ข้อ ๑.๑ - ๑.๕: ๕๐ คะแนน)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th>รายการประเมิน</th>
                                <th style="width: 100px;">คะแนนเต็ม</th>
                                <th style="width: 140px;">ตนเองให้</th>
                                <th style="width: 180px;" class="bg-warning-subtle text-dark">* หัวหน้าประเมิน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($specDirectItems as $sIdx => $item): 
                                $sAns = $selfAnswers[$item->id] ?? null;
                                $supAns = $supAnswers[$item->id] ?? $sAns;
                                $selfVal = $sAns ? ($sAns->numeric_value ?? $sAns->text_value) : '-';
                                $supVal = $supAns ? ($supAns->numeric_value ?? $supAns->text_value) : $item->max_score;
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
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $selfVal ?></span>
                                    </td>
                                    <td class="bg-warning-subtle text-center">
                                        <input type="number" step="0.5" min="0" max="<?= $item->max_score ?>" name="item_score[<?= $item->id ?>]" class="form-control form-control-sm text-center auto-save-field other-score-input fw-bold border-warning" data-item-id="<?= $item->id ?>" value="<?= Html::encode($supVal) ?>" <?= !$isEditable ? 'readonly' : '' ?>>
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
            <div class="card-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0 text-white">๑.๖ องค์ประกอบอื่น ๆ: ภาระงานรองหรืองานที่ได้รับมอบหมาย (คะแนนเต็ม ๕ คะแนน)</h5>
                <small class="text-white-50">เกณฑ์: 6-10 ข้อ = 5 คะแนน, 5 ข้อ = 4 คะแนน, 3-4 ข้อ = 3 คะแนน, 2 ข้อ = 2 คะแนน, 1 ข้อ = 1 คะแนน</small>
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
                            ?>
                                <tr>
                                    <td class="text-center align-top pt-3">
                                        <input class="form-check-input auto-save-field sup-spec-sec-checkbox" type="checkbox" name="spec_sec_check[]" value="<?= $optKey ?>" id="sup_spec_sec_<?= $optKey ?>" <?= $isChecked ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                    </td>
                                    <td class="text-center fw-bold text-muted align-top pt-3"><?= $optKey ?></td>
                                    <td>
                                        <label class="form-check-label fw-bold text-dark mb-2 d-block" for="sup_spec_sec_<?= $optKey ?>" style="cursor: pointer;">
                                            <?= Html::encode($optLabel) ?>
                                        </label>

                                        <div class="sup-spec-evidence-container <?= !$isChecked ? 'd-none' : '' ?>">
                                            <?php if (!empty($optEntries)): ?>
                                                <div class="sup-spec-entries-list mb-2">
                                                    <?php foreach ($optEntries as $eIdx => $entry): 
                                                        $hasFile = !empty($entry['file_id']) || !empty($entry['file_url']);
                                                        $fileName = $entry['file_name'] ?? '';
                                                        $fileUrl = $entry['file_url'] ?? '';
                                                        $isImage = (bool)preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fileName);
                                                        $isPdf = (bool)preg_match('/\.pdf$/i', $fileName);
                                                    ?>
                                                        <div class="p-2 border rounded bg-white shadow-sm mb-2">
                                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                                <div>
                                                                    <span class="badge bg-primary me-1">#<?= $eIdx + 1 ?></span>
                                                                    <strong><?= Html::encode($entry['detail'] ?: '(ไม่ได้ระบุรายละเอียด)') ?></strong>
                                                                </div>
                                                                <div>
                                                                    <?php if ($hasFile): ?>
                                                                        <div class="btn-group btn-group-sm">
                                                                            <button type="button" class="btn btn-xs btn-outline-info shadow-sm" 
                                                                                    data-bs-toggle="modal" data-bs-target="#previewEvidenceModal"
                                                                                    onclick="previewEvidenceFile(this)"
                                                                                    data-url="<?= Html::encode($fileUrl) ?>" 
                                                                                    data-name="<?= Html::encode($fileName) ?>"
                                                                                    data-is-image="<?= $isImage ? '1' : '0' ?>"
                                                                                    data-is-pdf="<?= $isPdf ? '1' : '0' ?>"
                                                                                    title="พรีวิวหลักฐาน">
                                                                                <i class="bi bi-eye"></i> พรีวิว: <?= Html::encode(mb_strimwidth($fileName, 0, 25, '...')) ?>
                                                                            </button>
                                                                            <a href="<?= Html::encode($fileUrl ?: '#') ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="ดาวน์โหลด">
                                                                                <i class="bi bi-download"></i>
                                                                            </a>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i> ไม่มีหลักฐานแนบ</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted small fst-italic p-1 mb-1">
                                                    <i class="bi bi-dash-circle me-1"></i> ไม่มีรายละเอียดและหลักฐานที่ระบุ
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SPECIAL SECTION 2: CHARACTERISTICS (45 POINTS) -->
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0 text-white">ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (๔๕ คะแนน)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th>รายการประเมิน</th>
                                <th style="width: 100px;">คะแนนเต็ม</th>
                                <th style="width: 140px;">ตนเองให้</th>
                                <th style="width: 180px;" class="bg-warning-subtle text-dark">* หัวหน้าประเมิน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($secSpec2):
                                foreach ($secSpec2->items as $sIdx => $item): 
                                    $sAns = $selfAnswers[$item->id] ?? null;
                                    $supAns = $supAnswers[$item->id] ?? $sAns;
                                    $selfVal = $sAns ? ($sAns->numeric_value ?? $sAns->text_value) : '-';
                                    $supVal = $supAns ? ($supAns->numeric_value ?? $supAns->text_value) : $item->max_score;
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
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= $selfVal ?></span>
                                        </td>
                                        <td class="bg-warning-subtle text-center">
                                            <input type="number" step="0.5" min="0" max="<?= $item->max_score ?>" name="item_score[<?= $item->id ?>]" class="form-control form-control-sm text-center auto-save-field other-score-input fw-bold border-warning" data-item-id="<?= $item->id ?>" value="<?= Html::encode($supVal) ?>" <?= !$isEditable ? 'readonly' : '' ?>>
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

        <!-- ==================== EVIDENCE ATTACHMENTS VIEW ==================== -->
        <?php if (!empty($evidenceFiles)): ?>
            <div class="card card-rmutt shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-paperclip me-1 text-primary"></i> เอกสารหลักฐานที่ผู้รับการประเมินแนบ (Evidence Files)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>คำอธิบายหลักฐาน</th>
                                <th>ชื่อไฟล์</th>
                                <th style="width: 120px;" class="text-center">ขนาด</th>
                                <th style="width: 170px;" class="text-center">ดาวน์โหลด / พรีวิว</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evidenceFiles as $fIdx => $file): ?>
                                <tr>
                                    <td class="text-center"><?= $fIdx + 1 ?></td>
                                    <td><strong><?= Html::encode($file->description ?: 'เอกสารประกอบ') ?></strong></td>
                                    <td>
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i>
                                        <?= Html::encode($file->original_name) ?>
                                    </td>
                                    <td class="text-center"><?= $file->formattedSize ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-info me-1 btn-preview-evidence shadow-sm" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#previewEvidenceModal"
                                                onclick="previewEvidenceFile(this)"
                                                data-url="<?= $file->fileUrl ?>" 
                                                data-name="<?= Html::encode($file->original_name) ?>"
                                                data-is-image="<?= $file->isImage() ? '1' : '0' ?>"
                                                data-is-pdf="<?= $file->isPdf() ? '1' : '0' ?>">
                                            <i class="bi bi-eye me-1"></i> พรีวิว
                                        </button>
                                        <a href="<?= $file->fileUrl ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="ดาวน์โหลด/เปิดแท็บใหม่">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ==================== SUPERVISOR COMMENTS & RECOMMENDATIONS ==================== -->
        <div class="card card-rmutt shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="fw-bold mb-0 text-white">
                    <i class="bi bi-chat-left-dots-fill me-1"></i> 
                    <?= $evaluatorTier === 'l2' ? 'ความเห็นและข้อเสนอแนะของหัวหน้าฝ่าย (L2)' : 'ความเห็นและข้อเสนอแนะของหัวหน้างาน (L1)' ?>
                </h6>
            </div>
            <div class="card-body p-4">
                
                <?php if ($evaluatorTier === 'l2' && $evaluation->l1_evaluated_at): ?>
                    <!-- L1 Reference Comments for L2 Reviewer -->
                    <div class="card bg-info-subtle border-info-subtle mb-4">
                        <div class="card-header bg-info text-dark fw-bold py-2">
                            <i class="bi bi-person-check-fill me-1"></i> ความเห็นประกอบการประเมินจากหัวหน้างาน (L1: <?= $evaluation->evaluatorL1 ? Html::encode($evaluation->evaluatorL1->fullName) : '-' ?>)
                        </div>
                        <div class="card-body py-3">
                            <div class="row g-2 small">
                                <div class="col-md-4">
                                    <strong class="text-dark">๑) จุดเด่น:</strong>
                                    <div class="text-secondary"><?= Html::encode($evaluation->l1_comment_strength ?: '-') ?></div>
                                </div>
                                <div class="col-md-4">
                                    <strong class="text-dark">๒) สิ่งที่ควรปรับปรุง:</strong>
                                    <div class="text-secondary"><?= Html::encode($evaluation->l1_comment_improvement ?: '-') ?></div>
                                </div>
                                <div class="col-md-4">
                                    <strong class="text-dark">๓) ข้อเสนอแนะส่งเสริม:</strong>
                                    <div class="text-secondary"><?= Html::encode($evaluation->l1_comment_suggestion ?: '-') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <?php 
                    $commentFieldPrefix = ($evaluatorTier === 'l2') ? 'l2_comment_' : 'l1_comment_';
                    $valStrength = ($evaluatorTier === 'l2') ? ($evaluation->l2_comment_strength ?: $evaluation->l1_comment_strength) : $evaluation->l1_comment_strength;
                    $valImprovement = ($evaluatorTier === 'l2') ? ($evaluation->l2_comment_improvement ?: $evaluation->l1_comment_improvement) : $evaluation->l1_comment_improvement;
                    $valSuggestion = ($evaluatorTier === 'l2') ? ($evaluation->l2_comment_suggestion ?: $evaluation->l1_comment_suggestion) : $evaluation->l1_comment_suggestion;
                    ?>
                    <div class="col-12">
                        <label class="form-label fw-bold text-dark">๑) จุดเด่น ของผู้รับการประเมิน:</label>
                        <textarea class="form-control auto-save-field" name="<?= $commentFieldPrefix ?>strength" rows="2" placeholder="ระบุจุดเด่น ทักษะความเชี่ยวชาญ หรือความโดดเด่นในการปฏิบัติงาน..." <?= !$isEditable ? 'readonly' : '' ?>><?= Html::encode($valStrength) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-dark">๒) สิ่งที่ควรปรับปรุงแก้ไข / พัฒนา:</label>
                        <textarea class="form-control auto-save-field" name="<?= $commentFieldPrefix ?>improvement" rows="2" placeholder="ระบุจุดที่ควรปรับปรุงแก้ไข..." <?= !$isEditable ? 'readonly' : '' ?>><?= Html::encode($valImprovement) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold text-dark">๓) ข้อเสนอแนะเกี่ยวกับวิธีส่งเสริมและพัฒนา:</label>
                        <textarea class="form-control auto-save-field" name="<?= $commentFieldPrefix ?>suggestion" rows="2" placeholder="ระบุหลักสูตร แผนการฝึกอบรม หรือข้อเสนอแนะในการพัฒนาศักยภาพ..." <?= !$isEditable ? 'readonly' : '' ?>><?= Html::encode($valSuggestion) ?></textarea>
                    </div>

                    <?php if ($personnelType === 'SPECIAL'): ?>
                        <div class="col-12 p-3 bg-light rounded border">
                            <label class="form-label fw-bold text-primary">ความเห็นเกี่ยวกับการจ้างต่อไป (สำหรับพนักงานพิเศษเงินรายได้):</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="employment_recommendation" value="continue" class="form-check-input" <?= $evaluation->employment_recommendation === 'continue' ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?>>
                                    <label class="form-check-label fw-bold text-success">ควรให้จ้างต่อไป</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="employment_recommendation" value="terminate" class="form-check-input" <?= $evaluation->employment_recommendation === 'terminate' ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?>>
                                    <label class="form-check-label fw-bold text-danger">ไม่ควรให้จ้างต่อไป</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isEditable): ?>
                    <div class="mt-4 text-end">
                        <?php if ($evaluatorTier === 'l2'): ?>
                            <button type="submit" class="btn btn-success px-4 py-2 shadow-sm" id="btn-submit-final" onclick="return confirm('คุณต้องการยืนยันการอนุมัติและบันทึกผลการประเมินขั้นสุดท้าย (L2) ใช่หรือไม่?\n\nระบบจะทำการคำนวณคะแนนสุทธิและเปิดให้ผู้รับการประเมินลงนามรับทราบผล');">
                                <i class="bi bi-shield-check me-1"></i> อนุมัติ & ยืนยันผลการประเมินขั้นสุดท้าย
                            </button>
                        <?php elseif (!empty($isSamePersonL1L2)): ?>
                            <div class="d-inline-flex gap-2">
                                <button type="submit" name="submit_action" value="complete" class="btn btn-success px-4 py-2 shadow-sm" onclick="return confirm('คุณต้องการยืนยันการบันทึกและตัดสินผลการประเมินขั้นสุดท้ายใช่หรือไม่?\n\n(เนื่องจากคุณเป็นผู้ประเมินทั้ง L1 และ L2 ระบบจะทำการสรุปผลคะแนนให้เสร็จสิ้นทันที)');">
                                    <i class="bi bi-check2-all me-1"></i> บันทึก & ตัดสินผลการประเมิน (เสร็จสิ้น L1 & L2 ในครั้งเดียว)
                                </button>
                                <button type="submit" name="submit_action" value="forward" class="btn btn-outline-primary px-3 py-2" onclick="return confirm('คุณต้องการส่งต่อไปยังขั้นตอน L2 ใช่หรือไม่?');">
                                    <i class="bi bi-send me-1"></i> บันทึก & ส่งต่อ L2 ตามขั้นตอน
                                </button>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="submit_action" value="forward" class="btn btn-primary px-4 py-2 shadow-sm" id="btn-submit-final" onclick="return confirm('คุณต้องการยืนยันการบันทึกผลการประเมินขั้นต้น (L1) และส่งต่อให้หัวหน้าฝ่าย (L2) ใช่หรือไม่?');">
                                <i class="bi bi-send-check me-1"></i> บันทึก & ส่งต่อหัวหน้าฝ่าย (L2)
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div>

<!-- Modal Return -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= Url::to(['return', 'id' => $evaluation->id]) ?>" method="post">
                <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-1"></i> ส่งแบบประเมินกลับเพื่อแก้ไข</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($evaluatorTier === 'l2' && $evaluation->evaluatorL1): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">เลือกปลายทางที่ต้องการส่งกลับ:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="return_to" id="returnToL1" value="l1" checked>
                                <label class="form-check-label" for="returnToL1">
                                    <strong>ส่งกลับให้หัวหน้างาน (L1: <?= Html::encode($evaluation->evaluatorL1->fullName) ?>)</strong> ทบทวนคะแนน
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="return_to" id="returnToStaff" value="staff">
                                <label class="form-check-label" for="returnToStaff">
                                    <strong>ส่งกลับให้ผู้รับการประเมิน (<?= Html::encode($personnel->fullName) ?>)</strong> แก้ไขข้อมูลตนเอง
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ระบุเหตุผล / สิ่งที่ต้องการให้แก้ไข:</label>
                        <textarea name="return_reason" class="form-control" rows="4" required placeholder="เช่น กรุณาแนบเอกสารหลักฐานข้อ 5.1 หรือปรับปรุงค่าน้ำหนักภาระงานหลักให้ครบ 80%"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยันส่งกลับ</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

<!-- JavaScript -->
<?php
$autoSaveUrl = Url::to(['auto-save']);
$evaluationId = $evaluation->id;
$mainItemId = (isset($mainItem) && $mainItem) ? $mainItem->id : 0;
$govtSecItemId = (isset($govtSecItem) && $govtSecItem) ? $govtSecItem->id : 0;
$govtSecDetailsJson = json_encode($govtSecDetails ?? [], JSON_UNESCAPED_UNICODE) ?: '{}';
$spec16ItemId = (isset($spec16Item) && $spec16Item) ? $spec16Item->id : 0;
$specSecDetailsJson = json_encode($spec16Details ?? [], JSON_UNESCAPED_UNICODE) ?: '{}';
$perfWeightVal = floatval($perfWeight);
$compWeightVal = floatval($compWeight);

$script = <<<JS
$(function() {
    let autoSaveTimer = null;

    window.updateSupervisorPdcaHighlights = updateSupervisorPdcaHighlights;

    updateSupervisorPdcaHighlights();
    recalculateSupervisorGAP();
    recalculateGovtSupervisorSummary();

    $(document).on('input change keyup paste blur', '.auto-save-field, .sup-score-select, .sup-policy-check, .sup-acad-radio, .sup-govt-sec-checkbox', function() {
        updateSupervisorPdcaHighlights();
        recalculateSupervisorGAP();
        recalculateGovtSupervisorSummary();
        triggerAutoSave();
    });

    $(document).on('change', '.sup-govt-sec-checkbox', function() {
        let count = $('.sup-govt-sec-checkbox:checked').length;
        $('#sup-govt-sec-badge-pill').text('เลือกปฏิบัติ ' + count + ' / 10 ข้อ');
        let card = $(this).closest('.card');
        if ($(this).is(':checked')) {
            card.addClass('border-primary');
        } else {
            card.removeClass('border-primary');
        }
        recalculateGovtSupervisorSummary();
        triggerAutoSave();
    });

    $(document).on('change', '.sup-spec-sec-checkbox', function() {
        let row = $(this).closest('tr');
        let container = row.find('.sup-spec-evidence-container');
        if ($(this).is(':checked')) {
            container.removeClass('d-none');
        } else {
            container.addClass('d-none');
        }
        triggerAutoSave();
    });

    $(document).on('blur change', '.sup-score-select', function() {
        let val = parseFloat($(this).val());
        if (!isNaN(val)) {
            val = Math.min(5, Math.max(1, Math.round(val * 10) / 10));
            $(this).val(val);
        }
        updateSupervisorPdcaHighlights();
    });

    function updateSupervisorPdcaHighlights() {
        let mainWeightSum = 0;
        let mainScoreSum = 0;
        $('.sup-main-work-row').each(function() {
            let row = $(this);
            let inputElem = row.find('.sup-score-select');
            let rawScore = parseFloat(inputElem.val());
            let score = isNaN(rawScore) ? 0 : rawScore;
            let w = parseFloat(row.find('input[name*="[weight]"]').val()) || 0;
            let weighted = (score / 5.0) * w;
            row.find('.sup-main-raw-score').text(score > 0 ? (Math.round(score * 10) / 10) : '-');
            row.find('.sup-row-weighted').text(weighted.toFixed(2));
            mainWeightSum += w;
            mainScoreSum += weighted;

            // Target level columns (highlight floor level)
            let tgtFloor = Math.min(5, Math.max(1, Math.floor(score)));
            for (let s = 1; s <= 5; s++) {
                let cell = $(this).find('.sup-main-tgt-' + s);
                if (score > 0 && s === tgtFloor) {
                    cell.html('<span class="badge bg-primary">' + s + '</span>');
                } else {
                    cell.html('<span class="text-muted">' + s + '</span>');
                }
            }

            $(this).find('.pdca-line').each(function() {
                let lvl = parseInt($(this).data('level'));
                if (score > 0 && lvl === tgtFloor) {
                    $(this).removeClass('text-muted').addClass('bg-success-subtle text-success fw-bold border border-success-subtle');
                } else {
                    $(this).removeClass('bg-success-subtle text-success fw-bold border border-success-subtle').addClass('text-muted');
                }
            });
        });
        $('#sup-footer-main-weight').text(mainWeightSum);
        $('#sup-footer-main-weighted-sum').text(mainScoreSum.toFixed(2));

        // 5.1 Policy Checklist
        let policyCheckedCount = $('.sup-policy-check:checked').length;
        let policyScore = 0;
        if (policyCheckedCount >= 3) policyScore = 5;
        else if (policyCheckedCount === 2) policyScore = 3;
        else if (policyCheckedCount === 1) policyScore = 1;
        let policyWeighted = (policyScore / 5.0) * 15.0;

        $('#sup-policy-count-badge').text(policyCheckedCount);
        $('#sup-policy-score-badge, #sup-policy-score-num').text(policyScore);
        $('#sup-policy-weighted-num').text(policyWeighted.toFixed(2));
        for (let s = 1; s <= 5; s++) {
            let cell = $('.sup-policy-tgt-' + s);
            if (policyScore > 0 && s === policyScore) {
                cell.html('<span class="badge bg-primary">' + s + '</span>');
            } else {
                cell.html('<span class="text-muted">' + s + '</span>');
            }
        }

        // 5.2 Academic Progress
        let acadChecked = $('.sup-acad-radio:checked');
        let acadVal = acadChecked.length ? parseInt(acadChecked.val()) : 0;
        let acadWeighted = (acadVal / 5.0) * 5.0;
        $('#sup-acad-score-badge, #sup-acad-score-num').text(acadVal);
        $('#sup-acad-weighted-num').text(acadWeighted.toFixed(2));
        for (let s = 1; s <= 5; s++) {
            let cell = $('.sup-acad-tgt-' + s);
            if (acadVal > 0 && s === acadVal) {
                cell.html('<span class="badge bg-primary">' + s + '</span>');
            } else {
                cell.html('<span class="text-muted">' + s + '</span>');
            }
        }

        // Footers
        let secSum = policyWeighted + acadWeighted;
        let totalPerf = mainScoreSum + secSum;
        let totalWeight = mainWeightSum + 20;
        let perf80 = (totalPerf / 100.0) * {$perfWeightVal};

        $('#sup-footer-main-weight').text(mainWeightSum);
        $('#sup-footer-sec-weighted-sum').text(secSum.toFixed(2));
        $('#sup-footer-total-weight').text(totalWeight);
        $('#sup-footer-total-perf-sum').text(totalPerf.toFixed(2));
        $('#sup-footer-total-perf-80').text(perf80.toFixed(2));
    }

    $('#btn-manual-save').on('click', function() {
        triggerAutoSave(true);
    });

    $('#btn-submit-supervisor-eval, #btn-submit-final').on('click', function(e) {
        e.preventDefault();
        if (confirm('คุณต้องการยืนยันการบันทึกผลการประเมินนี้ใช่หรือไม่?\\n\\nระบบจะทำการคำนวณคะแนนขั้นสุดท้าย และแจ้งให้ผู้รับการประเมินรับทราบผล')) {
            var formElem = document.getElementById('supervisor-eval-form');
            if (formElem) {
                formElem.submit();
            }
        }
    });

    function recalculateSupervisorGAP() {
        if ($('#sup-gap-total-sum').length === 0) return;
        let countGe = 0;
        let countM1 = 0;
        let countM2 = 0;
        let countM3 = 0;
        let totalComps = 0;

        $('.comp-level-select').each(function() {
            let actual = parseInt($(this).val()) || 3;
            let expected = parseInt($(this).data('expected')) || 3;
            let diff = actual - expected;
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

        $('#sup-gap-count-ge').text(countGe);
        $('#sup-gap-pts-ge').text(ptsGe);
        $('#sup-gap-count-m1').text(countM1);
        $('#sup-gap-pts-m1').text(ptsM1);
        $('#sup-gap-count-m2').text(countM2);
        $('#sup-gap-pts-m2').text(ptsM2);
        $('#sup-gap-count-m3').text(countM3);
        $('#sup-gap-pts-m3').text(ptsM3);
        $('#sup-gap-total-sum').text(totalGapSum);

        if (totalComps > 0) {
            let maxPossible = totalComps * 3.0;
            let ratio = (totalGapSum / maxPossible) * 100.0;
            let finalWeighted = (totalGapSum / maxPossible) * {$compWeightVal};
            $('#sup-gap-ratio-score').text(ratio.toFixed(2));
            $('#sup-gap-weighted-final').text(finalWeighted.toFixed(2));
        }
    }

    function recalculateGovtSupervisorSummary() {
        if ($('#sup-govt-summary-total').length === 0 && $('#sup-govt-perf-unified-table').length === 0) return;

        // 1. Main work supervisor
        let mainSum = 0;
        $('.sup-govt-main-row').each(function() {
            let k1 = parseFloat($(this).find('.sup-govt-kpi-vol').val()) || 5;
            let k2 = parseFloat($(this).find('.sup-govt-kpi-qua').val()) || 5;
            let k3 = parseFloat($(this).find('.sup-govt-kpi-time').val()) || 5;
            let k4 = parseFloat($(this).find('.sup-govt-kpi-res').val()) || 5;
            let w = parseFloat($(this).find('.sup-govt-work-weight').val()) || 40;
            let score = ((k1 * 25) + (k2 * 25) + (k3 * 15) + (k4 * 15)) / 80.0;
            let weighted = (score / 5.0) * w;
            mainSum += weighted;
            $(this).find('.sup-govt-row-weighted').text(weighted.toFixed(2));

            let rounded = Math.round(score);
            for (let s = 1; s <= 5; s++) {
                let cell = $(this).find('.sup-govt-tgt-' + s);
                if (s === rounded) {
                    cell.html('<span class="badge bg-primary">' + s + '</span>');
                } else {
                    cell.html('<span class="text-muted">' + s + '</span>');
                }
            }
        });

        // 2. Secondary work (4 KPIs evaluation like main work)
        let sk1 = parseFloat($('#sup-govt-sec-kpi-vol').val()) || 5;
        let sk2 = parseFloat($('#sup-govt-sec-kpi-qua').val()) || 5;
        let sk3 = parseFloat($('#sup-govt-sec-kpi-time').val()) || 5;
        let sk4 = parseFloat($('#sup-govt-sec-kpi-res').val()) || 5;
        let secScore = ((sk1 * 25) + (sk2 * 25) + (sk3 * 15) + (sk4 * 15)) / 80.0;
        let secWeighted = (secScore / 5.0) * 20.0;

        let roundedSec = Math.round(secScore);
        for (let s = 1; s <= 5; s++) {
            let el = $('.sup-govt-sec-tgt-' + s);
            if (s === roundedSec) {
                el.html('<span class="badge bg-primary">' + s + '</span>');
            } else {
                el.html('<span class="text-muted">' + s + '</span>');
            }
        }

        let perfScore = mainSum + secWeighted;
        let perfWeighted80 = (perfScore / 100.0) * {$perfWeightVal};

        $('#sup-govt-main-weighted-sum').text(mainSum.toFixed(2));
        $('#sup-govt-sec-weighted-sum, #sup-govt-sec-row-score').text(secWeighted.toFixed(2));
        $('#sup-govt-perf-total-sum').text(perfScore.toFixed(2));

        // 3. Behavior
        let behSum = 0;
        $('.comp-level-select').each(function() {
            behSum += parseInt($(this).val()) || 3;
        });
        let behScore100 = (behSum / 25.0) * 100.0;
        let behWeighted20 = (behSum / 25.0) * {$compWeightVal};

        let finalTotal = perfWeighted80 + behWeighted20;

        $('#sup-govt-summary-main-score').text(mainSum.toFixed(2));
        $('#sup-govt-summary-sec-score').text(secScore.toFixed(2));
        $('#sup-govt-summary-perf-score').text(perfScore.toFixed(2));
        $('#sup-govt-summary-perf-weighted').text(perfWeighted80.toFixed(2));
        $('#sup-govt-summary-beh-score').text(behScore100.toFixed(2));
        $('#sup-govt-summary-beh-weighted').text(behWeighted20.toFixed(2));
        $('#sup-govt-summary-total').text(finalTotal.toFixed(2));

        let govtLevel = 'ต้องปรับปรุง';
        if (finalTotal >= 95.0) govtLevel = 'ดีเด่น';
        else if (finalTotal >= 85.0) govtLevel = 'ดีมาก';
        else if (finalTotal >= 75.0) govtLevel = 'ดี';
        else if (finalTotal >= 65.0) govtLevel = 'พอใช้';
        $('#sup-govt-summary-level').text(govtLevel);
    }

    function triggerAutoSave(isManual = false) {
        clearTimeout(autoSaveTimer);
        $('#save-status').html('<span class="text-warning"><i class="bi bi-arrow-repeat spin me-1"></i> กำลังบันทึกคะแนน...</span>');

        autoSaveTimer = setTimeout(function() {
            saveFormData();
        }, isManual ? 0 : 1500);
    }

    function saveFormData() {
        let answers = {};
        let competencies = {};

        // 1. Main work supervisor scores
        let mainWorkRows = [];
        $('.sup-main-work-row').each(function() {
            let title = $(this).find('input[name*="[title]"]').val();
            let kpi = $(this).find('input[name*="[kpi]"]').val() || '';
            let selfScore = $(this).find('input[name*="[self_score]"]').val();
            let supScore = $(this).find('.sup-score-select').val();
            let weight = $(this).find('input[name*="[weight]"]').val();
            mainWorkRows.push({
                title: title,
                kpi: kpi,
                self_score: selfScore,
                supervisor_score: supScore,
                weight: weight
            });
        });

        let mainItemId = "{$mainItemId}";
        if (mainItemId && mainItemId !== "0") {
            answers[mainItemId] = mainWorkRows;
        }

        // 2. GOVT Secondary Checklist
        let govtSecSelected = [];
        $('.sup-govt-sec-checkbox:checked').each(function() {
            let key = $(this).val();
            govtSecSelected.push(key);
        });
        let govtSecDetails = {$govtSecDetailsJson};
        let govtSecItemId = "{$govtSecItemId}";
        if (govtSecItemId && govtSecItemId !== "0") {
            answers[govtSecItemId] = {
                selected: govtSecSelected,
                details: govtSecDetails,
                kpi_volume: $('#sup-govt-sec-kpi-vol').val() || 5,
                kpi_quality: $('#sup-govt-sec-kpi-qua').val() || 5,
                kpi_timeliness: $('#sup-govt-sec-kpi-time').val() || 5,
                kpi_resource: $('#sup-govt-sec-kpi-res').val() || 5
            };
        }

        // 2.1 SPECIAL 1.6 Secondary Checklist
        let specSecSelected = [];
        $('.sup-spec-sec-checkbox:checked').each(function() {
            let key = $(this).val();
            specSecSelected.push(key);
        });
        let specSecDetails = {$specSecDetailsJson};
        let spec16ItemId = "{$spec16ItemId}";
        if (spec16ItemId && spec16ItemId !== "0") {
            answers[spec16ItemId] = {
                selected: specSecSelected,
                details: specSecDetails
            };
        }

        // 3. Radio items
        $('input[type=radio]:checked').each(function() {
            let name = $(this).attr('name');
            let match = name ? name.match(/item_radio\[(\d+)\]/) : null;
            if (match) {
                answers[match[1]] = $(this).val();
            }
        });

        // 3. Other scores
        $('.other-score-input').each(function() {
            let itemId = $(this).data('item-id');
            if (itemId) {
                answers[itemId] = $(this).val();
            }
        });

        // 4. Competencies
        $('.comp-level-select').each(function() {
            let compId = $(this).data('comp-id');
            let level = $(this).val();
            let idp = $(this).closest('tr').find('input[name*="[idp]"]').val();
            competencies[compId] = {
                level: level,
                idp: idp
            };
        });

        $.ajax({
            url: '{$autoSaveUrl}',
            type: 'POST',
            data: {
                evaluation_id: '{$evaluationId}',
                answered_by: '{$evaluatorTier}',
                answers: answers,
                competencies: competencies,
                supervisor_comment_strength: $('textarea[name$="comment_strength"]').val(),
                supervisor_comment_improvement: $('textarea[name$="comment_improvement"]').val(),
                supervisor_comment_suggestion: $('textarea[name$="comment_suggestion"]').val(),
                employment_recommendation: $('input[name="employment_recommendation"]:checked').val(),
                _csrf: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                $('#save-status').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> บันทึกคะแนนอัตโนมัติแล้ว (' + new Date().toLocaleTimeString('th-TH') + ')</span>');
            }
        });
    }
});
JS;
$this->registerJs($script, \yii\web\View::POS_READY);
?>
