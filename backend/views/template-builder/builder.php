<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\EvaluationTemplate $template */
/** @var common\models\TemplateVersion $version */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */
/** @var float $totalSectionWeight */
/** @var float|null $perfWeight */
/** @var float|null $compWeight */
/** @var common\models\Department[] $departments */

$this->title = 'จัดการแบบประเมินผลการปฏิบัติงาน: ' . $template->name_th;
$this->params['breadcrumbs'][] = ['label' => 'จัดการแบบประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $template->name_th;

$csrfParam = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf';
$csrfToken = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '';

$ptCode = $template->personnelType ? $template->personnelType->code : '';
$isSpecial = ($ptCode === 'SPECIAL');
$isCivilOrUniv = ($ptCode === 'CIVIL' || $ptCode === 'UNIVERSITY');
$isGovt = ($ptCode === 'GOVT');

$perfWeight = $perfWeight ?? ($isSpecial ? 55.0 : ($isGovt ? 80.0 : 70.0));
$compWeight = $compWeight ?? ($isSpecial ? 45.0 : ($isGovt ? 20.0 : 30.0));

// Find sections for CIVIL/UNIVERSITY
$secMain = null;
$secPolicy = null;
$secAcad = null;
$secComp = null;

// Find sections for SPECIAL
$secSpec1 = null;
$secSpec2 = null;

// Find sections for GOVT
$secGovt1 = null;
$secGovt2 = null;
$secGovt3 = null;

foreach ($sections as $s) {
    if ($s->section_code === 'MAIN_WORK' || $s->section_type === 'main_work') {
        if (!$secMain) $secMain = $s;
    } elseif ($s->section_code === 'SECONDARY_POLICY' || ($s->section_type === 'secondary_work' && (strpos($s->name_th, '๕.๑') !== false || strpos($s->name_th, '5.1') !== false))) {
        $secPolicy = $s;
    } elseif ($s->section_code === 'SECONDARY_ACADEMIC' || ($s->section_type === 'secondary_work' && (strpos($s->name_th, '๕.๒') !== false || strpos($s->name_th, '5.2') !== false))) {
        $secAcad = $s;
    } elseif ($s->section_code === 'COMPETENCY_EVAL' || $s->section_type === 'competency') {
        $secComp = $s;
    } elseif (in_array($s->section_code, ['SPEC_PERFORMANCE', 'SPEC_SECTION_1'])) {
        $secSpec1 = $s;
    } elseif (in_array($s->section_code, ['SPEC_CHARACTERISTICS', 'SPEC_SECTION_2'])) {
        $secSpec2 = $s;
    } elseif (in_array($s->section_code, ['GOVT_MAIN_WORK', 'GOVT_SECTION_1'])) {
        $secGovt1 = $s;
    } elseif (in_array($s->section_code, ['GOVT_SECONDARY_WORK', 'GOVT_SECTION_2'])) {
        $secGovt2 = $s;
    } elseif (in_array($s->section_code, ['GOVT_BEHAVIOR', 'GOVT_SECTION_3'])) {
        $secGovt3 = $s;
    }
}

// Fallback assignments
if ($isCivilOrUniv) {
    if (!$secMain && !empty($sections)) $secMain = $sections[0];
    if (!$secPolicy && count($sections) > 1) $secPolicy = $sections[1];
    if (!$secAcad && count($sections) > 2) $secAcad = $sections[2];
    if (!$secComp && count($sections) > 3) $secComp = $sections[3];
} elseif ($isSpecial) {
    if (!$secSpec1 && !empty($sections)) $secSpec1 = $sections[0];
    if (!$secSpec2 && count($sections) > 1) $secSpec2 = $sections[1];
} elseif ($isGovt) {
    if (!$secGovt1 && !empty($sections)) $secGovt1 = $sections[0];
    if (!$secGovt2 && count($sections) > 1) $secGovt2 = $sections[1];
    if (!$secGovt3 && count($sections) > 2) $secGovt3 = $sections[2];
}

$policy7Options = [
    '1' => 'งานบริการวิชาการหารายได้ตั้งแต่ ๑๐,๐๐๐.- บาทขึ้นไป (สะสมใน ๑ ปี)',
    '2' => 'นวัตกรรม/สร้างสรรค์ โดยเป็นผู้ดำเนินการหลักหรือผู้ร่วมซึ่งมีส่วนร่วม ร้อยละ ๓๐ ขึ้นไป โดยใช้แบบฟอร์มการแสดงการมีส่วนร่วม',
    '3' => 'การพัฒนาตนเองด้านภาษาต่างประเทศ (RT-TEP ๓.๕/ IELTS ๕.๕ /TOEFL ๔๐๐) หรือกิจกรรมด้านภาษาที่คณะกรรมการรับรอง / วิชาชีพเฉพาะทาง (ใบ Certificate จากระบบ Certiport)',
    '4' => 'การเข้าร่วมกิจกรรมของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๔ ครั้งขึ้นไป/รอบการประเมิน (ดังเอกสารแนบ)',
    '5' => 'คณะกรรมการการดำเนินงานด้านต่าง ๆ ของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๓ งาน/โครงการขึ้นไป (**สามารถสะสมได้ภายใน ๑ ปี)',
    '6' => 'ปฏิบัติหน้าที่หัวหน้าฝ่าย (เท่ากับ ๒ ข้อ)',
    '7' => 'ปฏิบัติหน้าที่หัวหน้างาน (เท่ากับ ๑ ข้อ)',
];

$acad5Levels = [
    '0' => 'ระดับ ๐: ไม่มีการจัดทำ / ไม่เข้าเกณฑ์ (๐ คะแนน)',
    '1' => 'ระดับ ๑: ยื่นผลงานให้ผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญพิจารณา (แนบเอกสารขอความอนุเคราะห์/ คำสั่งแต่งตั้ง) (๑ คะแนน)',
    '2' => 'ระดับ ๒: ผ่านการพิจารณาจากผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญในงานที่เกี่ยวข้อง ตรวจเบื้องต้น (แนบแบบประเมินผลงาน) (๒ คะแนน)',
    '3' => 'ระดับ ๓: ผ่านการพิจารณาผู้บังคับบัญชาภายในหน่วยงาน ส่งไปยัง กบค. (แนบบันทึกข้อความ) (๓ คะแนน)',
    '4' => 'ระดับ ๔: อยู่ระหว่างการพิจารณาจาก กบค. (ใช้หลักฐานสถานะการดำเนินการจาก กบค.) (๔ คะแนน)',
    '5' => 'ระดับ ๕: เผยแพร่ผลงานทางวิชาการ เป็นตำรา หนังสือบทความ และหรือ ได้ตำแหน่งที่สูงขึ้น (แนบคำสั่งแต่งตั้ง หรือหลักฐาน) (๕ คะแนน)',
];

$spec10Options = [
    '1' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย (ระบุ พร้อมแนบหลักฐาน)',
    '2' => 'ดำเนินการขับเคลื่อนผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ของสำนักฯ (เช่น ผลงานที่แสดงให้เห็นชัดถึงการขับเคลื่อนการดำเนินแผนของสำนักฯ / มหาลัยฯ พร้อมแนบหลักฐาน)',
    '3' => 'คณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / งาน EdPEx (เช่น คำสั่งที่/ หนังสือมอบหมายหน้าที่/ หลักฐานที่เป็นลายลักษณ์อักษร พร้อมแนบหลักฐาน)',
    '4' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน /การทำ LEAN Management /การทำ Kaizen (ระบุ พร้อมแนบหลักฐาน)',
    '5' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์ (ระบุ พร้อมแนบหลักฐาน)',
    '6' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก ที่เกี่ยวข้องกับตำแหน่งหน้าที่ (ภายในปีงบประมาณ หรือ ย้อนหลัง 1 ปี **1 Certificate ใช้ได้ 2 รอบประเมิน พร้อมแนบหลักฐาน)',
    '7' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (มีใบรับรองเป็นหลักฐานแสดงผลการเข้าร่วม หรือ การทดสอบ พร้อมแนบหลักฐาน)',
    '8' => 'งานส่งเสริมความเป็นนานาชาติ /งานบริการวิชาการ / งานทำนุบำรุงศิลปวัฒนธรรม อย่างใดอย่างหนึ่ง (ระบุ พร้อมแนบหลักฐาน)',
    '9' => 'การหารายได้เข้าสำนักฯ (ระบุ พร้อมแนบหลักฐาน)',
    '10' => 'อื่น ๆ (ระบุ พร้อมแนบหลักฐาน)',
];

$defaultPdcaDescriptions = [
    1 => 'มีแผนการดำเนินงาน/แนวทางการดำเนินงาน (Plan)',
    2 => 'ดำเนินการตามแผน/แนวทางที่กำหนด (Do)',
    3 => 'ทบทวน ตรวจสอบ ประเมินผลการดำเนินงาน (Check)',
    4 => 'แก้ไขปรับปรุงกระบวนการ มีคู่มือหรือแนวทางปฏิบัติที่พัฒนาขึ้น (Act)',
    5 => 'ปรับปรุงต่อเนื่อง สร้างคุณค่าเพิ่มหรือนวัตกรรม เกิดผลลัพธ์ที่เป็นประโยชน์สูง (Impact)',
];
?>

<style>
/* Official RMUTT Evaluation Form Styling — Mirroring self_assess.php */
.official-form-doc {
    font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.sticky-eval-bar {
    position: sticky;
    top: 60px;
    z-index: 1020;
    backdrop-filter: blur(8px);
    background-color: rgba(255, 255, 255, 0.98);
}

.pdca-box {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 10px;
}

.pdca-box input.form-control {
    background-color: #ffffff;
    border-color: #cbd5e1;
    font-size: 0.85rem;
}

.pdca-box input.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
}

.level-badge-lbl {
    width: 96px;
    text-align: center;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 5px 6px;
    flex-shrink: 0;
}

.table-eval th {
    background-color: #f1f5f9 !important;
    color: #1e293b;
    font-weight: 600;
}

.table-eval td {
    vertical-align: middle;
}

.rule-box {
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 10px 14px;
}
</style>

<div class="template-builder-view py-3 official-form-doc">

    <!-- 1. Top Floating Header / Status Bar (Same as self_assess.php) -->
    <div class="card card-rmutt shadow-sm mb-4 sticky-eval-bar border-primary border-2">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                
                <!-- Left: Navigation & Template Context -->
                <div class="d-flex align-items-center gap-2">
                    <?= Html::a('<i class="bi bi-arrow-left me-1"></i> รายการแบบประเมิน', ['index'], [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'title' => 'กลับสู่หน้ารายการแบบประเมิน'
                    ]) ?>
                    <div>
                        <span class="fw-bold text-dark fs-6"><?= Html::encode($template->name_th) ?></span>
                        <span class="badge bg-primary ms-1"><?= Html::encode($template->personnelType ? $template->personnelType->name_th : '') ?></span>
                        <?php if ($template->department): ?>
                            <span class="badge bg-secondary ms-1">🏢 <?= Html::encode($template->department->name_th) ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark ms-1">⭐ มาตรฐานกลาง</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Weight Meter & Actions -->
                <div class="d-flex align-items-center gap-3">
                    <?php if ($isCivilOrUniv): ?>
                        <!-- CIVIL & UNIVERSITY: Show Form 2 (100%) and Overall (70% / 30%) -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">แบบ ป.ผ. ผลสัมฤทธิ์:</span>
                            <span class="badge bg-success fs-6" id="form2-weight-badge">
                                <span id="form2-weight-num">100</span>% / 100%
                            </span>
                            <span class="small text-muted ms-2">ภาพรวม:</span>
                            <span class="badge bg-primary fs-6" id="overall-weight-badge">
                                ผลสัมฤทธิ์ <?= intval($perfWeight) ?>% + สมรรถนะ <?= intval($compWeight) ?>% = 100%
                            </span>
                            <i class="bi bi-check-circle-fill text-success fs-5" title="สัดส่วนคะแนนถูกต้องสมบูรณ์ตามระเบียบมหาวิทยาลัย"></i>
                        </div>
                    <?php elseif ($isSpecial): ?>
                        <!-- SPECIAL: 55 + 45 = 100 Points -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">คะแนนเต็มรวม:</span>
                            <span class="badge bg-success fs-6" id="total-weight-badge">
                                <span id="total-weight-num">100</span> / 100 คะแนน
                            </span>
                            <span class="badge bg-info text-dark fs-6">
                                ผลงาน 55 + คุณลักษณะ 45
                            </span>
                            <i class="bi bi-check-circle-fill text-success fs-5" title="คะแนนเต็มรวม 100 คะแนนถูกต้อง"></i>
                        </div>
                    <?php elseif ($isGovt): ?>
                        <!-- GOVT: 80% + 20% = 100% -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">สัดส่วนรวม:</span>
                            <span class="badge bg-success fs-6" id="total-weight-badge">
                                <span id="total-weight-num">100</span>% / 100%
                            </span>
                            <span class="badge bg-primary fs-6">
                                ผลสัมฤทธิ์ 80% + พฤติกรรม 20%
                            </span>
                            <i class="bi bi-check-circle-fill text-success fs-5" title="สัดส่วนครบ 100% ถูกต้อง"></i>
                        </div>
                    <?php endif; ?>

                    <?= Html::a('<i class="bi bi-eye-fill me-1"></i> ดูตัวอย่างฟอร์มจริง (Live Preview)', ['preview', 'id' => $template->id], [
                        'class' => 'btn btn-sm btn-outline-info text-dark shadow-sm',
                        'target' => '_blank',
                        'title' => 'เปิดดูแบบฟอร์มเสมือนจริงที่บุคลากรและกรรมการจะใช้ประเมิน'
                    ]) ?>

                    <button type="button" class="btn btn-sm btn-success shadow-sm fw-bold px-3" onclick="saveEntireGrid()" id="btn-save-top">
                        <i class="bi bi-floppy-fill me-1"></i> บันทึกแบบประเมิน
                    </button>
                </div>

            </div>
        </div>
    </div>

    <?php
    $evalCount = \common\models\Evaluation::find()->where(['template_version_id' => $version->id])->count();
    if ($evalCount > 0):
    ?>
    <div class="alert alert-info py-2.5 px-3 mb-4 d-flex align-items-center justify-content-between rounded-3 shadow-sm border-info bg-info-subtle">
        <div class="small">
            <i class="bi bi-info-circle-fill me-2 text-primary fs-6"></i>
            <strong>ข้อมูลการใช้งาน:</strong> แบบประเมินเวอร์ชันนี้มีบุคลากรในรอบปัจจุบันผูกใช้งานอยู่ <strong><?= $evalCount ?></strong> รายการ 
            <span class="text-muted">(เมื่อท่านแก้ไขและกดบันทึก ระบบจะปรับปรุงโครงสร้างและข้อคำถามให้แบบประเมินของบุคลากรเป็นข้อมูลล่าสุดทันที)</span>
        </div>
        <span class="badge bg-primary">รอบประเมินปัจจุบัน</span>
    </div>
    <?php endif; ?>

    <!-- 2. Official Document Header Card (Exact styling from self_assess.php) -->
    <div class="card card-rmutt shadow-sm mb-4 bg-light border">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">แบบข้อตกลงและประเมินผลการปฏิบัติงานบุคลากร</h5>
                <h6 class="text-muted mb-0">
                    <?= Html::encode($template->department ? $template->department->name_th : 'สำนักวิทยบริการและเทคโนโลยีสารสนเทศ') ?> มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี
                </h6>
                <div class="text-primary fw-bold mt-1">
                    <?= Html::encode($template->personnelType ? $template->personnelType->name_th : 'บุคลากร') ?> &bull; เวอร์ชัน <?= Html::encode($version->version_label ?: 'v1.0') ?>
                </div>
            </div>
            <div class="row g-2 small border-top pt-3 align-items-center">
                <div class="col-md-5">
                    <label class="fw-bold text-dark mb-1">ชื่อแบบประเมินผลการปฏิบัติงาน:</label>
                    <input type="text" id="template-title-input" class="form-control form-control-sm fw-bold" 
                           value="<?= Html::encode($template->name_th) ?>" placeholder="ระบุชื่อแบบประเมิน...">
                </div>
                <div class="col-md-4">
                    <strong>หน่วยงาน / สังกัด:</strong> <?= Html::encode($template->department ? $template->department->name_th : 'แม่แบบมาตรฐานกลาง (มหาวิทยาลัย)') ?><br>
                    <strong>ประเภทบุคลากร:</strong> <?= Html::encode($template->personnelType ? $template->personnelType->name_th : '-') ?>
                </div>
                <div class="col-md-3">
                    <strong>สถานะแบบฟอร์ม:</strong> <span class="badge bg-success">ใช้งานประจำหน่วยงาน</span><br>
                    <span class="text-muted small">ปรับแต่งตัวชี้วัด (KPI) หรือเกณฑ์คะแนนตามภาระงานของหน่วยงาน</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================================= -->
    <!-- CASE 1: CIVIL & UNIVERSITY (ข้าราชการพลเรือน และ พนักงานมหาวิทยาลัย)                         -->
    <!-- Exactly mirroring self_assess.php: แบบ ป.ผ. (ผลสัมฤทธิ์ 100%) + แบบ พม. (สมรรถนะ 30%)      -->
    <!-- ========================================================================================= -->
    <?php if ($isCivilOrUniv): ?>

        <!-- =================================================================== -->
        <!-- CARD 1: ๒. แบบข้อตกลงและประเมินผลสัมฤทธิ์ของงาน (แบบ ป.ผ.) - ๑๐๐%   -->
        <!-- =================================================================== -->
        <div class="card card-rmutt shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-file-earmark-text-fill me-2"></i>๒. แบบข้อตกลงและประเมินผลสัมฤทธิ์ของงาน (แบบ ป.ผ.)
                    </h5>
                    <small class="text-white-50">
                        ประกอบด้วย: ภาระงานหลัก (๘๐%) &bull; งานนโยบาย ๕.๑ (๑๕%) &bull; คู่มือวิชาการ ๕.๒ (๕%) = รวม ๑๐๐% (ถ่วงน้ำหนักภาพรวม <?= intval($perfWeight) ?>%)
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-white text-primary fw-bold fs-6">
                        ค่าน้ำหนักรวม ๑๐๐% (ถ่วงภาพรวม <?= intval($perfWeight) ?>%)
                    </span>
                </div>
            </div>

            <div class="card-body p-4">

                <!-- ------------------------------------------------------------- -->
                <!-- 1.1 ภาระงานหลัก (MAIN_WORK) - ค่าน้ำหนัก 80%                   -->
                <!-- ------------------------------------------------------------- -->
                <div class="section-block mb-4 p-3 border rounded bg-white" 
                     data-section-id="<?= $secMain ? $secMain->id : 0 ?>" 
                     data-section-type="main_work"
                     data-section-code="MAIN_WORK">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white fw-bold">ส่วนที่ ๑</span>
                            <span class="fw-bold fs-6 text-dark">
                                ภาระงานหลัก (กิจกรรม/โครงการ/งาน และระดับความสำเร็จตามวงจร PDCA)
                            </span>
                            <input type="hidden" class="section-name-input" value="แบบข้อตกลงการประเมินผลสัมฤทธิ์ของงาน: ภาระงานหลัก (ค่าน้ำหนัก 80)">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">ค่าน้ำหนักหมวด:</span>
                            <div class="input-group input-group-sm" style="width: 105px;">
                                <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                                       value="<?= $secMain ? $secMain->weight : 80 ?>" oninput="recalcTotals()" id="sec-main-weight">
                                <span class="input-group-text px-1">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 table-eval kpi-table" id="main-work-table">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th style="min-width: 280px;">(๑) กิจกรรม / โครงการ / ภาระงานหลัก หรือ ตัวชี้วัด</th>
                                    <th style="min-width: 540px;">(๒) เกณฑ์ระดับค่าเป้าหมายความสำเร็จ (ตามวงจร PDCA ๑ - ๕)</th>
                                    <th style="width: 110px;">(๓)<br>น้ำหนัก (%)</th>
                                    <th style="width: 50px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="kpi-tbody" id="main-work-tbody">
                                <?php 
                                $mainItems = ($secMain && !empty($secMain->items)) ? $secMain->items : [];
                                if (empty($mainItems)): 
                                ?>
                                    <!-- Default KPI Row if none exist yet -->
                                    <tr class="kpi-row" data-item-id="">
                                        <td class="text-center fw-bold kpi-row-number">1</td>
                                        <td>
                                            <textarea class="form-control form-control-sm item-name-input fw-semibold mb-1" rows="3" 
                                                      placeholder="ระบุกิจกรรม/โครงการ/ภาระงานหลัก หรือตัวชี้วัดของหน่วยงาน...">ภาระงานหลักและโครงการตามพันธกิจของหน่วยงาน</textarea>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <div class="form-check small mb-0">
                                                    <input class="form-check-input item-ev-input" type="checkbox" id="ev_new_1">
                                                    <label class="form-check-label text-muted" for="ev_new_1">
                                                        <i class="bi bi-paperclip"></i> บังคับแนบหลักฐาน
                                                    </label>
                                                </div>
                                                <input type="hidden" class="item-type-select" value="pdca_level">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="pdca-box">
                                                <div class="mb-1 d-flex align-items-center gap-1.5">
                                                    <span class="badge bg-secondary-subtle text-dark border level-badge-lbl">ระดับ ๑ (Plan)</span>
                                                    <input type="text" class="form-control form-control-sm crit-input-1" value="<?= Html::encode($defaultPdcaDescriptions[1]) ?>" placeholder="ระดับ 1...">
                                                </div>
                                                <div class="mb-1 d-flex align-items-center gap-1.5">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle level-badge-lbl">ระดับ ๒ (Do)</span>
                                                    <input type="text" class="form-control form-control-sm crit-input-2" value="<?= Html::encode($defaultPdcaDescriptions[2]) ?>" placeholder="ระดับ 2...">
                                                </div>
                                                <div class="mb-1 d-flex align-items-center gap-1.5">
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle level-badge-lbl">ระดับ ๓ (Check)</span>
                                                    <input type="text" class="form-control form-control-sm crit-input-3" value="<?= Html::encode($defaultPdcaDescriptions[3]) ?>" placeholder="ระดับ 3...">
                                                </div>
                                                <div class="mb-1 d-flex align-items-center gap-1.5">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle level-badge-lbl">ระดับ ๔ (Act)</span>
                                                    <input type="text" class="form-control form-control-sm crit-input-4" value="<?= Html::encode($defaultPdcaDescriptions[4]) ?>" placeholder="ระดับ 4...">
                                                </div>
                                                <div class="d-flex align-items-center gap-1.5">
                                                    <span class="badge border level-badge-lbl" style="background-color: #f3e8ff; color: #6b21a8; border-color: #d8b4fe;">ระดับ ๕ (Impact)</span>
                                                    <input type="text" class="form-control form-control-sm crit-input-5" value="<?= Html::encode($defaultPdcaDescriptions[5]) ?>" placeholder="ระดับ 5...">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group input-group-sm justify-content-center" style="max-width: 95px; margin: 0 auto;">
                                                <input type="number" step="0.5" min="0" max="80" class="form-control form-control-sm text-center fw-bold item-weight-input main-item-weight" 
                                                       value="80" oninput="recalcTotals()">
                                                <span class="input-group-text px-1 text-muted">%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)" title="ลบรายการนี้">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mainItems as $iIdx => $item): 
                                        $critMap = [];
                                        foreach ($item->criteria as $crit) {
                                            $critMap[$crit->level_value] = $crit->description;
                                        }
                                        $wVal = $item->max_weight > 0 ? $item->max_weight : 80;
                                    ?>
                                        <tr class="kpi-row" data-item-id="<?= $item->id ?>">
                                            <td class="text-center fw-bold kpi-row-number"><?= $iIdx + 1 ?></td>
                                            <td>
                                                <textarea class="form-control form-control-sm item-name-input fw-semibold mb-1" rows="3" 
                                                          placeholder="ระบุกิจกรรม/โครงการ/ภาระงานหลัก หรือตัวชี้วัด..."><?= Html::encode($item->name_th) ?></textarea>
                                                <div class="d-flex align-items-center justify-content-between mt-1">
                                                    <div class="form-check small mb-0">
                                                        <input class="form-check-input item-ev-input" type="checkbox" id="ev_<?= $item->id ?>" <?= $item->requires_evidence ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-muted" for="ev_<?= $item->id ?>">
                                                            <i class="bi bi-paperclip"></i> บังคับแนบหลักฐาน
                                                        </label>
                                                    </div>
                                                    <input type="hidden" class="item-type-select" value="pdca_level">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="pdca-box">
                                                    <div class="mb-1 d-flex align-items-center gap-1.5">
                                                        <span class="badge bg-secondary-subtle text-dark border level-badge-lbl">ระดับ ๑ (Plan)</span>
                                                        <input type="text" class="form-control form-control-sm crit-input-1" 
                                                               value="<?= Html::encode($critMap[1] ?? $defaultPdcaDescriptions[1]) ?>" 
                                                               placeholder="ระดับ 1...">
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center gap-1.5">
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle level-badge-lbl">ระดับ ๒ (Do)</span>
                                                        <input type="text" class="form-control form-control-sm crit-input-2" 
                                                               value="<?= Html::encode($critMap[2] ?? $defaultPdcaDescriptions[2]) ?>" 
                                                               placeholder="ระดับ 2...">
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center gap-1.5">
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle level-badge-lbl">ระดับ ๓ (Check)</span>
                                                        <input type="text" class="form-control form-control-sm crit-input-3" 
                                                               value="<?= Html::encode($critMap[3] ?? $defaultPdcaDescriptions[3]) ?>" 
                                                               placeholder="ระดับ 3...">
                                                    </div>
                                                    <div class="mb-1 d-flex align-items-center gap-1.5">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle level-badge-lbl">ระดับ ๔ (Act)</span>
                                                        <input type="text" class="form-control form-control-sm crit-input-4" 
                                                               value="<?= Html::encode($critMap[4] ?? $defaultPdcaDescriptions[4]) ?>" 
                                                               placeholder="ระดับ 4...">
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1.5">
                                                        <span class="badge border level-badge-lbl" style="background-color: #f3e8ff; color: #6b21a8; border-color: #d8b4fe;">ระดับ ๕ (Impact)</span>
                                                        <input type="text" class="form-control form-control-sm crit-input-5" 
                                                               value="<?= Html::encode($critMap[5] ?? $defaultPdcaDescriptions[5]) ?>" 
                                                               placeholder="ระดับ 5...">
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="input-group input-group-sm justify-content-center" style="max-width: 95px; margin: 0 auto;">
                                                    <input type="number" step="0.5" min="0" max="80" class="form-control form-control-sm text-center fw-bold item-weight-input main-item-weight" 
                                                           value="<?= $wVal ?>" oninput="recalcTotals()">
                                                    <span class="input-group-text px-1 text-muted">%</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)" title="ลบรายการนี้">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="p-2.5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addMainWorkRow()">
                                                <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการภาระงานหลัก (PDCA)
                                            </button>
                                            <div class="small fw-semibold text-muted">
                                                ผลรวมน้ำหนักภาระงานหลัก: 
                                                <span class="badge bg-primary fs-6" id="main-work-sum-badge">
                                                    <span id="main-work-sum">80</span>% / 80%
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ------------------------------------------------------------- -->
                <!-- 1.2 ๕.๑ งานส่งเสริมการขับเคลื่อนนโยบาย (SECONDARY_POLICY) 15%     -->
                <!-- ------------------------------------------------------------- -->
                <div class="section-block mb-4 p-3 border rounded bg-white" 
                     data-section-id="<?= $secPolicy ? $secPolicy->id : 0 ?>" 
                     data-section-type="secondary_work"
                     data-section-code="SECONDARY_POLICY">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white fw-bold">ส่วนที่ ๒</span>
                            <span class="fw-bold fs-6 text-dark">
                                ๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายของมหาวิทยาลัยและสำนักฯ (ค่าน้ำหนัก ๑๕)
                            </span>
                            <input type="hidden" class="section-name-input" value="๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายของมหาวิทยาลัยและสำนักฯ (ค่าน้ำหนัก ๑๕)">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-warning-emphasis border">เกณฑ์กลางมหาวิทยาลัย</span>
                            <span class="text-muted small">ค่าน้ำหนัก:</span>
                            <div class="input-group input-group-sm" style="width: 105px;">
                                <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                                       value="<?= $secPolicy ? $secPolicy->weight : 15 ?>" oninput="recalcTotals()" id="sec-policy-weight">
                                <span class="input-group-text px-1">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="rule-box mb-3 small">
                            <i class="bi bi-info-circle-fill text-success me-1"></i>
                            <strong>เกณฑ์การให้คะแนนตามระเบียบมหาวิทยาลัย:</strong> 
                            ดำเนินการได้ <strong>๑ ข้อ = ๑ คะแนน</strong> | <strong>๒ ข้อ = ๓ คะแนน</strong> | <strong>๓ ข้อขึ้นไป = ๕ คะแนน</strong> 
                            (คะแนนเต็ม ๕ คะแนน &rarr; แปลงเป็นค่าน้ำหนัก <strong>๑๕%</strong>)
                        </div>

                        <div class="fw-semibold text-dark mb-2 small">
                            รายการขับเคลื่อนนโยบายมาตรฐานมหาวิทยาลัย (๗ รายการ):
                        </div>
                        <div class="list-group list-group-flush border rounded bg-white">
                            <?php foreach ($policy7Options as $pIdx => $pText): ?>
                                <div class="list-group-item d-flex align-items-start gap-2 py-2 small">
                                    <span class="badge bg-secondary-subtle text-dark border me-1 mt-0.5">ข้อ <?= $pIdx ?></span>
                                    <span class="text-dark"><?= Html::encode($pText) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ------------------------------------------------------------- -->
                <!-- 1.3 ๕.๒ คู่มือปฏิบัติงาน/ผลงานทางวิชาการ (SECONDARY_ACADEMIC) 5%  -->
                <!-- ------------------------------------------------------------- -->
                <div class="section-block p-3 border rounded bg-white" 
                     data-section-id="<?= $secAcad ? $secAcad->id : 0 ?>" 
                     data-section-type="secondary_work"
                     data-section-code="SECONDARY_ACADEMIC">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white fw-bold">ส่วนที่ ๓</span>
                            <span class="fw-bold fs-6 text-dark">
                                ๕.๒ การจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย ตำรา หนังสือ งานแปล หรือบทความทางวิชาการ (ค่าน้ำหนัก ๕)
                            </span>
                            <input type="hidden" class="section-name-input" value="๕.๒ การจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย ตำรา หนังสือ งานแปล หรือบทความทางวิชาการ (ค่าน้ำหนัก ๕)">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-warning-emphasis border">เกณฑ์กลางมหาวิทยาลัย</span>
                            <span class="text-muted small">ค่าน้ำหนัก:</span>
                            <div class="input-group input-group-sm" style="width: 105px;">
                                <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                                       value="<?= $secAcad ? $secAcad->weight : 5 ?>" oninput="recalcTotals()" id="sec-acad-weight">
                                <span class="input-group-text px-1">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border">
                        <div class="rule-box mb-3 small">
                            <i class="bi bi-info-circle-fill text-success me-1"></i>
                            <strong>เกณฑ์การให้คะแนนตามระดับความก้าวหน้า:</strong> 
                            ประเมินตามระดับความก้าวหน้าตั้งแต่ <strong>ระดับ ๐ ถึง ระดับ ๕</strong> (เต็ม ๕ คะแนน &rarr; แปลงเป็นค่าน้ำหนัก <strong>๕%</strong>)
                        </div>

                        <div class="fw-semibold text-dark mb-2 small">
                            ระดับความก้าวหน้าการจัดทำคู่มือ/ผลงานทางวิชาการ (๖ ระดับ):
                        </div>
                        <div class="list-group list-group-flush border rounded bg-white">
                            <?php foreach ($acad5Levels as $aLvl => $aText): ?>
                                <div class="list-group-item d-flex align-items-start gap-2 py-2 small">
                                    <span class="badge <?= $aLvl > 0 ? 'bg-primary-subtle text-primary border' : 'bg-light text-muted border' ?> me-1 mt-0.5">
                                        <?= $aLvl ?> คะแนน
                                    </span>
                                    <span class="text-dark"><?= Html::encode($aText) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- =================================================================== -->
        <!-- CARD 2: ๓. แบบข้อตกลงการประเมินขีดความสามารถ/สมรรถนะ (แบบ พม.)       -->
        <!-- =================================================================== -->
        <div class="card card-rmutt shadow-sm mb-4 border-info section-block" 
             data-section-id="<?= $secComp ? $secComp->id : 0 ?>" 
             data-section-type="competency"
             data-section-code="COMPETENCY_EVAL">
            <div class="card-header bg-info text-dark py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-award-fill me-2 text-primary"></i>๓. แบบข้อตกลงการประเมินขีดความสามารถ/สมรรถนะของสายสนับสนุน (แบบ พม.)
                    </h5>
                    <small class="text-muted">
                        สมรรถนะหลัก (Core Competencies) &bull; สมรรถนะประจำสายงาน (Functional Competencies) &bull; ถ่วงน้ำหนักภาพรวม <?= intval($compWeight) ?>%
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-dark small fw-bold">ค่าน้ำหนักหมวด:</span>
                    <div class="input-group input-group-sm" style="width: 105px;">
                        <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                               value="<?= $secComp ? $secComp->weight : $compWeight ?>" oninput="recalcTotals()" id="sec-comp-weight">
                        <span class="input-group-text px-1">%</span>
                    </div>
                    <input type="hidden" class="section-name-input" value="แบบข้อตกลงการประเมินขีดความสามารถ/สมรรถนะของสายสนับสนุน (ค่าน้ำหนัก <?= intval($compWeight) ?>%)">
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 table-eval" id="comp-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="min-width: 250px;">หัวข้อสมรรถนะ (Competency)</th>
                                <th style="min-width: 340px;">คำนิยาม / พฤติกรรมที่บ่งชี้</th>
                                <th style="width: 180px;">ประเภทสมรรถนะ</th>
                                <th style="width: 140px;">ระดับที่คาดหวัง</th>
                                <th style="width: 50px;">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="comp-tbody">
                            <?php foreach ($competencies as $cIdx => $comp): ?>
                                <tr class="comp-row" data-comp-id="<?= $comp->id ?>">
                                    <td class="text-center fw-bold comp-row-num"><?= $cIdx + 1 ?></td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm fw-semibold comp-name-input" 
                                               value="<?= Html::encode($comp->name_th) ?>" placeholder="ชื่อสมรรถนะ...">
                                    </td>
                                    <td>
                                        <textarea class="form-control form-control-sm comp-def-input" rows="2" 
                                                  placeholder="คำนิยามหรือพฤติกรรมบ่งชี้..."><?= Html::encode($comp->definition) ?></textarea>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm comp-type-input">
                                            <option value="core" <?= $comp->competency_type === 'core' ? 'selected' : '' ?>>สมรรถนะหลัก (Core)</option>
                                            <option value="functional" <?= $comp->competency_type === 'functional' ? 'selected' : '' ?>>สมรรถนะประจำสายงาน (Functional)</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <select class="form-select form-select-sm text-center fw-bold text-primary comp-level-input">
                                            <?php for ($lvl = 1; $lvl <= 5; $lvl++): ?>
                                                <option value="<?= $lvl ?>" <?= $comp->expected_level == $lvl ? 'selected' : '' ?>>ระดับ <?= $lvl ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCompRow(this)" title="ลบสมรรถนะนี้">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="6" class="p-2.5">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addBlankCompRow()">
                                            <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการสมรรถนะใหม่
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="openCompetencyPoolModal()">
                                            <i class="bi bi-stars me-1"></i> ดึงจากคลังสมรรถนะ มหาวิทยาลัย
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================================================================= -->
    <!-- CASE 2: SPECIAL (พนักงานพิเศษเงินรายได้) - ด้านที่ ๑ ผลงาน (55) + ด้านที่ ๒ คุณลักษณะ (45)      -->
    <!-- ========================================================================================= -->
    <?php elseif ($isSpecial): ?>

        <!-- SPECIAL SECTION 1: ด้านที่ ๑ ผลงาน (55 คะแนน) -->
        <div class="card card-rmutt shadow-sm mb-4 section-block" 
             data-section-id="<?= $secSpec1 ? $secSpec1->id : 0 ?>" 
             data-section-type="main_work"
             data-section-code="SPEC_PERFORMANCE">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-briefcase-fill me-2"></i>ด้านที่ ๑ ผลงาน (คะแนนเต็ม ๕๕ คะแนน)
                    </h5>
                    <small class="text-white-50">
                        ข้อ ๑.๑ - ๑.๕: ๕ ปัจจัยผลสัมฤทธิ์ (๕๐ คะแนน) &bull; ข้อ ๑.๖: ภาระงานอื่น/งานที่ได้รับมอบหมาย (๕ คะแนน)
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-white-50 small">คะแนนเต็มหมวด:</span>
                    <div class="input-group input-group-sm" style="width: 110px;">
                        <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                               value="<?= $secSpec1 ? $secSpec1->weight : 55 ?>" oninput="recalcTotals()" id="sec-spec1-weight">
                        <span class="input-group-text px-1">คะแนน</span>
                    </div>
                    <input type="hidden" class="section-name-input" value="ด้านที่ ๑ ผลงาน (คะแนนเต็ม ๕๕ คะแนน)">
                </div>
            </div>

            <div class="card-body p-0">
                <?php 
                $spec15Items = [];
                $spec16Item = null;
                if ($secSpec1 && !empty($secSpec1->items)) {
                    foreach ($secSpec1->items as $it) {
                        if ($it->item_code === 'SPEC_1_6_SECONDARY' || $it->input_type === 'checkbox_list' || strpos($it->name_th, '๑.๖') !== false) {
                            $spec16Item = $it;
                        } else {
                            $spec15Items[] = $it;
                        }
                    }
                }
                ?>

                <!-- 1.1 - 1.5: ๕ ปัจจัยผลสัมฤทธิ์ของงาน (๕๐ คะแนน) -->
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2">ส่วนที่ ๑.๑ - ๑.๕</span>
                        <strong class="text-dark fs-6">ผลสัมฤทธิ์ของงาน (๕ ปัจจัย - รวม ๕๐ คะแนน)</strong>
                        <div class="small text-muted mt-0.5">ประเมินตาม ๕ ปัจจัย: ปริมาณผลงาน (10), คุณภาพ (10), ความทันเวลา (10), ความคุ้มค่าทรัพยากร (10), ผลสัมฤทธิ์ (10)</div>
                    </div>
                    <div class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                        คะแนนเต็ม: <span id="spec15-sum">50</span> / 50 คะแนน
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 table-eval kpi-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th style="min-width: 320px;">รายการประเมิน / รายละเอียดภาระงาน</th>
                                <th style="width: 140px;">คะแนนเต็ม</th>
                                <th style="width: 60px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="kpi-tbody" id="spec15-tbody">
                            <?php foreach ($spec15Items as $iIdx => $item): 
                                $scoreVal = !empty($item->max_score) ? $item->max_score : ($item->max_weight ?: 10);
                            ?>
                                <tr class="kpi-row" data-item-id="<?= $item->id ?>">
                                    <td class="text-center fw-bold kpi-row-number"><?= $iIdx + 1 ?></td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm fw-semibold item-name-input mb-1" 
                                               value="<?= Html::encode($item->name_th) ?>" placeholder="ระบุรายการประเมิน...">
                                        <input type="hidden" class="item-type-select" value="<?= Html::encode($item->input_type ?: 'score_direct') ?>">
                                        <input type="hidden" class="item-code-input" value="<?= Html::encode($item->item_code ?: ('SPEC_1_' . ($iIdx + 1))) ?>">
                                        <input type="hidden" class="item-ev-input" value="0">
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: 0 auto;">
                                            <input type="number" step="0.5" min="0" max="50" class="form-control form-control-sm text-center fw-bold item-weight-input" 
                                                   value="<?= $scoreVal ?>" oninput="recalcTotals()">
                                            <span class="input-group-text px-1 text-muted">คะแนน</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)" title="ลบรายการนี้">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="p-2.5">
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addKpiRow(this, <?= $secSpec1 ? $secSpec1->id : 0 ?>, true)">
                                        <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการใน ๕ ปัจจัย
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 1.6 องค์ประกอบอื่น ๆ: ภาระงานรองหรืองานที่ได้รับมอบหมาย (๕ คะแนน) -->
                <?php
                $spec16OptionsList = [];
                if ($spec16Item && !empty($spec16Item->options_data)) {
                    $parsed = is_string($spec16Item->options_data) ? json_decode($spec16Item->options_data, true) : $spec16Item->options_data;
                    if (is_string($parsed)) $parsed = json_decode($parsed, true);
                    if (is_array($parsed)) {
                        foreach ($parsed as $pIdx => $opt) {
                            if (is_array($opt) && isset($opt['text'])) {
                                $spec16OptionsList[] = [
                                    'key' => (string)($opt['key'] ?? ($pIdx + 1)),
                                    'text' => $opt['text']
                                ];
                            } elseif (is_string($opt)) {
                                $spec16OptionsList[] = [
                                    'key' => (string)($pIdx + 1),
                                    'text' => $opt
                                ];
                            }
                        }
                    }
                }
                if (empty($spec16OptionsList)) {
                    foreach ($spec10Options as $k => $text) {
                        $spec16OptionsList[] = ['key' => (string)$k, 'text' => $text];
                    }
                }
                ?>
                <div class="p-3 border-top border-bottom bg-light mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-warning text-dark me-2">ส่วนที่ ๑.๖</span>
                        <strong class="text-dark fs-6">๑.๖ องค์ประกอบอื่น ๆ : ภาระงานรองหรืองานที่ได้รับมอบหมาย</strong>
                        <div class="small text-muted mt-0.5">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                            เกณฑ์การให้คะแนน: <strong>6-10 ข้อ = 5 คะแนน</strong> | <strong>5 ข้อ = 4 คะแนน</strong> | <strong>3-4 ข้อ = 3 คะแนน</strong> | <strong>2 ข้อ = 2 คะแนน</strong> | <strong>1 ข้อ = 1 คะแนน</strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning-subtle text-dark border fs-6">
                            จำนวน: <span id="spec16-opt-count"><?= count($spec16OptionsList) ?></span> รายการ | คะแนนเต็ม: 5 คะแนน
                        </span>
                    </div>
                </div>

                <div class="p-3 bg-white">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-2 table-hover" id="spec16-options-table">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 60px;">ข้อ</th>
                                    <th>รายการภาระงานรองหรืองานที่ได้รับมอบหมาย (Admin สามารถแก้ไขข้อความได้)</th>
                                    <th style="width: 70px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="spec16-options-tbody">
                                <?php foreach ($spec16OptionsList as $oIdx => $opt): ?>
                                    <tr class="spec16-option-row">
                                        <td class="text-center fw-bold spec16-row-num"><?= $oIdx + 1 ?></td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm spec16-option-text" 
                                                   value="<?= Html::encode($opt['text']) ?>" placeholder="ระบุภาระงานรองหรือภาระงานอื่นที่ได้รับมอบหมาย...">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSpec16OptionRow(this)" title="ลบรายการนี้">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="p-2.5">
                                        <button type="button" class="btn btn-sm btn-outline-success fw-bold" onclick="addSpec16OptionRow()">
                                            <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการภาระงานรอง (+ เพิ่มข้อ)
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <small class="text-muted"><i class="bi bi-lightbulb text-warning me-1"></i> รายการภาระงานรองนี้จะแสดงเป็น Checkbox ให้บุคลากรเลือกปฏิบัติและแนบหลักฐานในแบบประเมินตนเอง</small>
                </div>

                <!-- Hidden master row for Item 1.6 to be collected by saveEntireGrid -->
                <table class="d-none">
                    <tbody>
                        <tr class="kpi-row spec16-master-row" data-item-id="<?= $spec16Item ? $spec16Item->id : '' ?>" id="spec16-master-kpi-row">
                            <td>
                                <input type="hidden" class="item-name-input" value="<?= Html::encode($spec16Item ? $spec16Item->name_th : '๑.๖ องค์ประกอบอื่น ๆ (ภาระงานอื่น หรืองานที่ได้รับมอบหมาย - 5 คะแนน)') ?>">
                                <input type="hidden" class="item-type-select" value="checkbox_list">
                                <input type="hidden" class="item-code-input" value="SPEC_1_6_SECONDARY">
                                <input type="hidden" class="item-ev-input" value="1">
                            </td>
                            <td>
                                <input type="hidden" class="item-weight-input" value="5">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SPECIAL SECTION 2: ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (45 คะแนน) -->
        <div class="card card-rmutt shadow-sm mb-4 section-block" 
             data-section-id="<?= $secSpec2 ? $secSpec2->id : 0 ?>" 
             data-section-type="general"
             data-section-code="SPEC_CHARACTERISTICS">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-person-check-fill me-2"></i>ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (คะแนนเต็ม ๔๕ คะแนน)
                    </h5>
                    <small class="text-white-50">
                        ข้อ ๒.๑ - ๒.๗: วินัย คุณธรรม จริยธรรม การมาปฏิบัติงาน ความร่วมมือ และความคิดริเริ่ม
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-white-50 small">คะแนนเต็มหมวด:</span>
                    <div class="input-group input-group-sm" style="width: 110px;">
                        <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                               value="<?= $secSpec2 ? $secSpec2->weight : 45 ?>" oninput="recalcTotals()" id="sec-spec2-weight">
                        <span class="input-group-text px-1">คะแนน</span>
                    </div>
                    <input type="hidden" class="section-name-input" value="ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (คะแนนเต็ม ๔๕ คะแนน)">
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 table-eval kpi-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">ข้อ</th>
                                <th style="min-width: 320px;">รายการประเมิน / รายละเอียดคุณลักษณะ</th>
                                <th style="width: 140px;">คะแนนเต็ม</th>
                                <th style="width: 60px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="kpi-tbody">
                            <?php 
                            $spec2Items = ($secSpec2 && !empty($secSpec2->items)) ? $secSpec2->items : [];
                            foreach ($spec2Items as $iIdx => $item): 
                                $scoreVal = !empty($item->max_score) ? $item->max_score : ($item->max_weight ?: 5);
                            ?>
                                <tr class="kpi-row" data-item-id="<?= $item->id ?>">
                                    <td class="text-center fw-bold kpi-row-number"><?= $iIdx + 1 ?></td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm fw-semibold item-name-input mb-1" 
                                               value="<?= Html::encode($item->name_th) ?>" placeholder="ระบุรายการคุณลักษณะ...">
                                        <input type="hidden" class="item-type-select" value="score_direct">
                                        <input type="hidden" class="item-ev-input" value="0">
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: 0 auto;">
                                            <input type="number" step="0.5" min="0" max="50" class="form-control form-control-sm text-center fw-bold item-weight-input" 
                                                   value="<?= $scoreVal ?>" oninput="recalcTotals()">
                                            <span class="input-group-text px-1 text-muted">คะแนน</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)" title="ลบรายการนี้">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="p-2.5">
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addKpiRow(this, <?= $secSpec2 ? $secSpec2->id : 0 ?>, true)">
                                        <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการในด้านคุณลักษณะ
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================================================================= -->
    <!-- CASE 3: GENERAL / OTHER TYPES (e.g. GOVT)                                                 -->
    <!-- ========================================================================================= -->
    <?php else: ?>

        <?php foreach ($sections as $sIdx => $section): ?>
            <div class="card card-rmutt shadow-sm mb-4 section-block" data-section-id="<?= $section->id ?>" data-section-type="<?= Html::encode($section->section_type) ?>">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 me-3">
                        <span class="badge bg-white text-primary fw-bold">หมวดที่ <?= $sIdx + 1 ?></span>
                        <input type="text" class="form-control form-control-sm fw-bold section-name-input text-white border-0" 
                               style="background-color: rgba(255,255,255,0.2) !important; max-width: 500px;" 
                               value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดการประเมิน...">
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="text-white-50 small">ค่าน้ำหนักหมวด:</span>
                        <div class="input-group input-group-sm" style="width: 110px;">
                            <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                                   value="<?= $section->weight ?>" oninput="recalcTotals()">
                            <span class="input-group-text px-1">%</span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 table-eval kpi-table">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th style="min-width: 260px;">กิจกรรม / ภาระงาน หรือตัวชี้วัด</th>
                                    <th style="min-width: 450px;">เกณฑ์ระดับค่าเป้าหมายความสำเร็จ</th>
                                    <th style="width: 110px;">น้ำหนัก (%)</th>
                                    <th style="width: 50px;">ลบ</th>
                                </tr>
                            </thead>
                            <tbody class="kpi-tbody">
                                <?php foreach ($section->items as $iIdx => $item): ?>
                                    <tr class="kpi-row" data-item-id="<?= $item->id ?>">
                                        <td class="text-center fw-bold kpi-row-number"><?= $iIdx + 1 ?></td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm fw-semibold item-name-input mb-1" 
                                                   value="<?= Html::encode($item->name_th) ?>" placeholder="ระบุภาระงาน...">
                                            <input type="hidden" class="item-type-select" value="pdca_level">
                                            <input type="hidden" class="item-ev-input" value="0">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" placeholder="เกณฑ์ความสำเร็จ...">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold item-weight-input" 
                                                   value="<?= $item->max_weight ?>" oninput="recalcTotals()">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

    <!-- 4. Bottom Action Bar (Same as self_assess.php footer) -->
    <div class="card card-rmutt shadow-sm p-4 mb-5 border-0 bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="fw-bold fs-6 text-dark mb-1">
                    <i class="bi bi-check2-circle text-primary me-1"></i> ตรวจสอบแบบประเมินและบันทึกข้อมูล
                </div>
                <div class="text-muted small">
                    <?php if ($isCivilOrUniv): ?>
                        แบบ ป.ผ. (ผลสัมฤทธิ์ของงาน 100%) แปลงเป็นค่าน้ำหนักภาพรวม <?= intval($perfWeight) ?>% &bull; แบบ พม. (สมรรถนะ) ค่าน้ำหนัก <?= intval($compWeight) ?>% &bull; รวมครบ 100%
                    <?php elseif ($isSpecial): ?>
                        ด้านที่ ๑ ผลงาน (55 คะแนน) &bull; ด้านที่ ๒ คุณลักษณะ (45 คะแนน) &bull; รวมคะแนนเต็ม 100 คะแนน
                    <?php else: ?>
                        สัดส่วนรวมครบ 100% ตามโครงสร้างแบบประเมิน
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?= Html::a('<i class="bi bi-arrow-left me-1"></i> กลับ', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a('<i class="bi bi-eye-fill me-1"></i> ดูตัวอย่างฟอร์มจริง (Live Preview)', ['preview', 'id' => $template->id], [
                    'class' => 'btn btn-outline-info text-dark shadow-sm',
                    'target' => '_blank'
                ]) ?>
                <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" onclick="saveEntireGrid()" id="btn-save-bottom">
                    <i class="bi bi-floppy-fill me-1"></i> บันทึกข้อมูลแบบประเมินทั้งหมด
                </button>
            </div>
        </div>
    </div>

</div>

<!-- RMUTT Competency Pool Modal -->
<div class="modal fade" id="compPoolModal" tabindex="-1" aria-labelledby="compPoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="compPoolModalLabel">
                    <i class="bi bi-stars me-2"></i> คลังสมรรถนะมาตรฐาน มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="comp-pool-body">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>กำลังโหลดคลังสมรรถนะ...
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF_PARAM = '<?= $csrfParam ?>';
const CSRF_TOKEN = '<?= $csrfToken ?>';
const SAVE_ALL_URL = '<?= Url::to(['save-all', 'id' => $template->id]) ?>';
const COMP_POOL_URL = '<?= Url::to(['competency-pool']) ?>';
const IS_CIVIL_OR_UNIV = <?= $isCivilOrUniv ? 'true' : 'false' ?>;
const IS_SPECIAL = <?= $isSpecial ? 'true' : 'false' ?>;
const IS_GOVT = <?= $isGovt ? 'true' : 'false' ?>;

// Default PDCA Descriptions for new rows
const DEFAULT_PDCA = {
    1: 'มีแผนการดำเนินงาน/แนวทางการดำเนินงาน (Plan)',
    2: 'ดำเนินการตามแผน/แนวทางที่กำหนด (Do)',
    3: 'ทบทวน ตรวจสอบ ประเมินผลการดำเนินงาน (Check)',
    4: 'แก้ไขปรับปรุงกระบวนการ มีคู่มือหรือแนวทางปฏิบัติที่พัฒนาขึ้น (Act)',
    5: 'ปรับปรุงต่อเนื่อง สร้างคุณค่าเพิ่มหรือนวัตกรรม เกิดผลลัพธ์ที่เป็นประโยชน์สูง (Impact)'
};

// Real-time weight recalculation
function recalcTotals() {
    if (IS_CIVIL_OR_UNIV) {
        // Calculate Main Work weight sum
        let mainSum = 0;
        document.querySelectorAll('.main-item-weight').forEach(function(inp) {
            mainSum += parseFloat(inp.value) || 0;
        });

        const mainSumEl = document.getElementById('main-work-sum');
        const mainBadge = document.getElementById('main-work-sum-badge');
        if (mainSumEl) mainSumEl.innerText = mainSum.toFixed(0);
        if (mainBadge) {
            mainBadge.className = (mainSum === 80) ? 'badge bg-success fs-6' : 'badge bg-warning text-dark fs-6';
        }

        // Form 2 = Main (80) + Policy (15) + Academic (5) = 100
        const policyW = parseFloat(document.getElementById('sec-policy-weight')?.value) || 15;
        const acadW = parseFloat(document.getElementById('sec-acad-weight')?.value) || 5;
        const form2Total = mainSum + policyW + acadW;

        const form2Badge = document.getElementById('form2-weight-badge');
        const form2Num = document.getElementById('form2-weight-num');
        if (form2Num) form2Num.innerText = form2Total.toFixed(0);
        if (form2Badge) {
            form2Badge.className = (form2Total === 100) ? 'badge bg-success fs-6' : 'badge bg-danger fs-6';
        }
    } else if (IS_SPECIAL) {
        let spec15Sum = 0;
        document.querySelectorAll('#spec15-tbody .item-weight-input').forEach(function(inp) {
            spec15Sum += parseFloat(inp.value) || 0;
        });
        const spec15SumEl = document.getElementById('spec15-sum');
        if (spec15SumEl) spec15SumEl.innerText = spec15Sum.toFixed(0);

        let sec1Sum = 0;
        document.querySelectorAll('[data-section-code="SPEC_PERFORMANCE"] .item-weight-input').forEach(function(inp) {
            sec1Sum += parseFloat(inp.value) || 0;
        });
        const sec1WeightEl = document.getElementById('sec-spec1-weight');
        if (sec1WeightEl) sec1WeightEl.value = sec1Sum;

        let sec2Sum = 0;
        document.querySelectorAll('[data-section-code="SPEC_CHARACTERISTICS"] .item-weight-input').forEach(function(inp) {
            sec2Sum += parseFloat(inp.value) || 0;
        });
        const sec2WeightEl = document.getElementById('sec-spec2-weight');
        if (sec2WeightEl) sec2WeightEl.value = sec2Sum;

        const total = sec1Sum + sec2Sum;
        const totalBadge = document.getElementById('total-weight-badge');
        const totalNum = document.getElementById('total-weight-num');
        if (totalNum) totalNum.innerText = total.toFixed(0);
        if (totalBadge) {
            totalBadge.className = (total === 100) ? 'badge bg-success fs-6' : 'badge bg-danger fs-6';
        }
    }
}

// Add KPI row for Main Work (PDCA)
function addMainWorkRow() {
    const tbody = document.getElementById('main-work-tbody');
    const count = tbody.querySelectorAll('.kpi-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'kpi-row';
    tr.setAttribute('data-item-id', '');

    tr.innerHTML = `
        <td class="text-center fw-bold kpi-row-number">${count}</td>
        <td>
            <textarea class="form-control form-control-sm item-name-input fw-semibold mb-1" rows="3" 
                      placeholder="ระบุกิจกรรม/โครงการ/ภาระงานหลัก หรือตัวชี้วัด..."></textarea>
            <div class="d-flex align-items-center justify-content-between mt-1">
                <div class="form-check small mb-0">
                    <input class="form-check-input item-ev-input" type="checkbox" id="ev_new_${Date.now()}">
                    <label class="form-check-label text-muted" for="ev_new_${Date.now()}">
                        <i class="bi bi-paperclip"></i> บังคับแนบหลักฐาน
                    </label>
                </div>
                <input type="hidden" class="item-type-select" value="pdca_level">
            </div>
        </td>
        <td>
            <div class="pdca-box">
                <div class="mb-1 d-flex align-items-center gap-1.5">
                    <span class="badge bg-secondary-subtle text-dark border level-badge-lbl">ระดับ ๑ (Plan)</span>
                    <input type="text" class="form-control form-control-sm crit-input-1" value="${DEFAULT_PDCA[1]}">
                </div>
                <div class="mb-1 d-flex align-items-center gap-1.5">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle level-badge-lbl">ระดับ ๒ (Do)</span>
                    <input type="text" class="form-control form-control-sm crit-input-2" value="${DEFAULT_PDCA[2]}">
                </div>
                <div class="mb-1 d-flex align-items-center gap-1.5">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle level-badge-lbl">ระดับ ๓ (Check)</span>
                    <input type="text" class="form-control form-control-sm crit-input-3" value="${DEFAULT_PDCA[3]}">
                </div>
                <div class="mb-1 d-flex align-items-center gap-1.5">
                    <span class="badge bg-success-subtle text-success border border-success-subtle level-badge-lbl">ระดับ ๔ (Act)</span>
                    <input type="text" class="form-control form-control-sm crit-input-4" value="${DEFAULT_PDCA[4]}">
                </div>
                <div class="d-flex align-items-center gap-1.5">
                    <span class="badge border level-badge-lbl" style="background-color: #f3e8ff; color: #6b21a8; border-color: #d8b4fe;">ระดับ ๕ (Impact)</span>
                    <input type="text" class="form-control form-control-sm crit-input-5" value="${DEFAULT_PDCA[5]}">
                </div>
            </div>
        </td>
        <td class="text-center">
            <div class="input-group input-group-sm justify-content-center" style="max-width: 95px; margin: 0 auto;">
                <input type="number" step="0.5" min="0" max="80" class="form-control form-control-sm text-center fw-bold item-weight-input main-item-weight" 
                       value="20" oninput="recalcTotals()">
                <span class="input-group-text px-1 text-muted">%</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)" title="ลบรายการนี้">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    reindexKpiRows(tbody);
    recalcTotals();
}

// Add KPI Row for SPECIAL / General Direct score
function addKpiRow(btn, secId, isDirectScore) {
    const table = btn.closest('.kpi-table');
    const tbody = table.querySelector('.kpi-tbody');
    const count = tbody.querySelectorAll('.kpi-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'kpi-row';
    tr.setAttribute('data-item-id', '');

    tr.innerHTML = `
        <td class="text-center fw-bold kpi-row-number">${count}</td>
        <td>
            <input type="text" class="form-control form-control-sm fw-semibold item-name-input mb-1" 
                   placeholder="ระบุรายการประเมิน...">
            <input type="hidden" class="item-type-select" value="score_direct">
            <input type="hidden" class="item-ev-input" value="0">
        </td>
        <td class="text-center">
            <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: 0 auto;">
                <input type="number" step="0.5" min="0" max="50" class="form-control form-control-sm text-center fw-bold item-weight-input" 
                       value="10" oninput="recalcTotals()">
                <span class="input-group-text px-1 text-muted">คะแนน</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeKpiRow(this)" title="ลบรายการนี้">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    reindexKpiRows(tbody);
    recalcTotals();
}

function removeKpiRow(btn) {
    if (!confirm('ยืนยันลบรายการนี้ใช่หรือไม่?')) return;
    const tr = btn.closest('.kpi-row');
    const tbody = tr.closest('.kpi-tbody');
    tr.remove();
    reindexKpiRows(tbody);
    recalcTotals();
}

function reindexKpiRows(tbody) {
    tbody.querySelectorAll('.kpi-row').forEach(function(r, idx) {
        const numEl = r.querySelector('.kpi-row-number');
        if (numEl) numEl.innerText = idx + 1;
    });
}

// Spec 1.6 secondary tasks checklist functions
function addSpec16OptionRow() {
    const tbody = document.getElementById('spec16-options-tbody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'spec16-option-row';
    const nextNum = tbody.querySelectorAll('.spec16-option-row').length + 1;
    tr.innerHTML = `
        <td class="text-center fw-bold spec16-row-num">${nextNum}</td>
        <td>
            <input type="text" class="form-control form-control-sm spec16-option-text" 
                   value="" placeholder="ระบุภาระงานรองหรือภาระงานอื่นที่ได้รับมอบหมาย...">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSpec16OptionRow(this)" title="ลบรายการนี้">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    updateSpec16RowNumbers();
    const input = tr.querySelector('.spec16-option-text');
    if (input) input.focus();
}

function removeSpec16OptionRow(btn) {
    const tbody = document.getElementById('spec16-options-tbody');
    if (!tbody) return;
    if (tbody.querySelectorAll('.spec16-option-row').length <= 1) {
        alert('ต้องมีรายการภาระงานรองอย่างน้อย 1 รายการ');
        return;
    }
    const tr = btn.closest('.spec16-option-row');
    if (tr) tr.remove();
    updateSpec16RowNumbers();
}

function updateSpec16RowNumbers() {
    const rows = document.querySelectorAll('#spec16-options-tbody .spec16-option-row');
    rows.forEach((r, idx) => {
        const numCell = r.querySelector('.spec16-row-num');
        if (numCell) numCell.innerText = idx + 1;
    });
    const countBadge = document.getElementById('spec16-opt-count');
    if (countBadge) countBadge.innerText = rows.length;
}

// Competency Functions
function addBlankCompRow() {
    const tbody = document.getElementById('comp-tbody');
    const count = tbody.querySelectorAll('.comp-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'comp-row';
    tr.setAttribute('data-comp-id', '');
    tr.innerHTML = `
        <td class="text-center fw-bold comp-row-num">${count}</td>
        <td>
            <input type="text" class="form-control form-control-sm fw-semibold comp-name-input" placeholder="ชื่อสมรรถนะ...">
        </td>
        <td>
            <textarea class="form-control form-control-sm comp-def-input" rows="2" placeholder="คำนิยามหรือพฤติกรรมบ่งชี้..."></textarea>
        </td>
        <td>
            <select class="form-select form-select-sm comp-type-input">
                <option value="core">สมรรถนะหลัก (Core)</option>
                <option value="functional">สมรรถนะประจำสายงาน (Functional)</option>
            </select>
        </td>
        <td class="text-center">
            <select class="form-select form-select-sm text-center fw-bold text-primary comp-level-input">
                <option value="1">ระดับ 1</option>
                <option value="2">ระดับ 2</option>
                <option value="3" selected>ระดับ 3</option>
                <option value="4">ระดับ 4</option>
                <option value="5">ระดับ 5</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCompRow(this)" title="ลบสมรรถนะนี้">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removeCompRow(btn) {
    if (!confirm('ยืนยันลบสมรรถนะนี้ใช่หรือไม่?')) return;
    const tr = btn.closest('.comp-row');
    const tbody = tr.closest('#comp-tbody');
    tr.remove();
    tbody.querySelectorAll('.comp-row').forEach(function(r, idx) {
        const numEl = r.querySelector('.comp-row-num');
        if (numEl) numEl.innerText = idx + 1;
    });
}

function openCompetencyPoolModal() {
    const container = document.getElementById('comp-pool-body');
    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดคลังสมรรถนะ...</div>';
    
    var modal = new bootstrap.Modal(document.getElementById('compPoolModal'));
    modal.show();

    fetch(COMP_POOL_URL)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.pool) {
                let html = '<h6 class="fw-bold text-primary mb-3"><i class="bi bi-star-fill me-1"></i> สมรรถนะหลัก (Core Competencies)</h6><div class="row g-2 mb-4">';
                res.pool.core.forEach(function(c) {
                    html += '<div class="col-12"><div class="card p-2.5 border bg-light d-flex flex-row justify-content-between align-items-center rounded-3"><div><strong class="text-dark">' + c.name_th + '</strong><p class="small text-muted mb-0">' + c.definition + '</p></div><button type="button" class="btn btn-sm btn-primary ms-3 text-nowrap" onclick="addCompFromPool(\'' + escapeHtml(c.name_th) + '\', \'core\', \'' + escapeHtml(c.definition) + '\', ' + c.default_level + ')"><i class="bi bi-plus-circle me-1"></i> ดึงเข้าฟอร์ม</button></div></div>';
                });
                html += '</div><h6 class="fw-bold text-info mb-3"><i class="bi bi-gear-fill me-1"></i> สมรรถนะประจำสายงาน (Functional Competencies)</h6><div class="row g-2">';
                res.pool.functional.forEach(function(c) {
                    html += '<div class="col-12"><div class="card p-2.5 border bg-light d-flex flex-row justify-content-between align-items-center rounded-3"><div><strong class="text-dark">' + c.name_th + '</strong><p class="small text-muted mb-0">' + c.definition + '</p></div><button type="button" class="btn btn-sm btn-info text-white ms-3 text-nowrap" onclick="addCompFromPool(\'' + escapeHtml(c.name_th) + '\', \'functional\', \'' + escapeHtml(c.definition) + '\', ' + c.default_level + ')"><i class="bi bi-plus-circle me-1"></i> ดึงเข้าฟอร์ม</button></div></div>';
                });
                html += '</div>';
                container.innerHTML = html;
            }
        });
}

function addCompFromPool(name, type, def, level) {
    const tbody = document.getElementById('comp-tbody');
    const count = tbody.querySelectorAll('.comp-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'comp-row';
    tr.setAttribute('data-comp-id', '');
    tr.innerHTML = `
        <td class="text-center fw-bold comp-row-num">${count}</td>
        <td>
            <input type="text" class="form-control form-control-sm fw-semibold comp-name-input" value="${name}">
        </td>
        <td>
            <textarea class="form-control form-control-sm comp-def-input" rows="2">${def}</textarea>
        </td>
        <td>
            <select class="form-select form-select-sm comp-type-input">
                <option value="core" ${type === 'core' ? 'selected' : ''}>สมรรถนะหลัก (Core)</option>
                <option value="functional" ${type === 'functional' ? 'selected' : ''}>สมรรถนะประจำสายงาน (Functional)</option>
            </select>
        </td>
        <td class="text-center">
            <select class="form-select form-select-sm text-center fw-bold text-primary comp-level-input">
                <option value="1" ${level == 1 ? 'selected' : ''}>ระดับ 1</option>
                <option value="2" ${level == 2 ? 'selected' : ''}>ระดับ 2</option>
                <option value="3" ${level == 3 ? 'selected' : ''}>ระดับ 3</option>
                <option value="4" ${level == 4 ? 'selected' : ''}>ระดับ 4</option>
                <option value="5" ${level == 5 ? 'selected' : ''}>ระดับ 5</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCompRow(this)" title="ลบสมรรถนะนี้">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    alert('ดึงสมรรถนะ "' + name + '" เข้าสู่แบบฟอร์มเรียบร้อยแล้ว');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Master Save Function
function saveEntireGrid() {
    const btnTop = document.getElementById('btn-save-top');
    const btnBottom = document.getElementById('btn-save-bottom');

    if (btnTop) btnTop.disabled = true;
    if (btnBottom) btnBottom.disabled = true;

    const payload = {
        template_name: document.getElementById('template-title-input').value,
        sections: [],
        competencies: []
    };

    // Gather Sections and KPI items
    document.querySelectorAll('.section-block').forEach(function(secEl) {
        const secId = secEl.getAttribute('data-section-id');
        const secType = secEl.getAttribute('data-section-type');
        const secCode = secEl.getAttribute('data-section-code') || '';
        const secNameInput = secEl.querySelector('.section-name-input');
        const secName = secNameInput ? secNameInput.value : '';
        const secWeightInput = secEl.querySelector('.section-weight-input');
        const secWeight = secWeightInput ? parseFloat(secWeightInput.value) || 0 : 0;

        const secObj = {
            id: secId,
            name_th: secName,
            weight: secWeight,
            section_type: secType,
            section_code: secCode,
            items: []
        };

        if (secType !== 'competency') {
            secEl.querySelectorAll('.kpi-row').forEach(function(row) {
                const typeSelect = row.querySelector('.item-type-select');
                const itemType = typeSelect ? typeSelect.value : 'pdca_level';
                const itemId = row.getAttribute('data-item-id') || '';
                const itemNameInput = row.querySelector('.item-name-input');
                const itemName = itemNameInput ? itemNameInput.value : '';
                const itemCodeInput = row.querySelector('.item-code-input');
                const itemCode = itemCodeInput ? itemCodeInput.value : '';
                const itemWeightInput = row.querySelector('.item-weight-input');
                const itemScoreVal = itemWeightInput ? parseFloat(itemWeightInput.value) || 0 : 0;
                
                let reqEv = 0;
                const evInput = row.querySelector('.item-ev-input');
                if (evInput) {
                    reqEv = (evInput.type === 'checkbox') ? (evInput.checked ? 1 : 0) : (parseInt(evInput.value) || 0);
                }

                const inp1 = row.querySelector('.crit-input-1');
                const inp2 = row.querySelector('.crit-input-2');
                const inp3 = row.querySelector('.crit-input-3');
                const inp4 = row.querySelector('.crit-input-4');
                const inp5 = row.querySelector('.crit-input-5');

                const crit = {
                    1: inp1 ? inp1.value : DEFAULT_PDCA[1],
                    2: inp2 ? inp2.value : DEFAULT_PDCA[2],
                    3: inp3 ? inp3.value : DEFAULT_PDCA[3],
                    4: inp4 ? inp4.value : DEFAULT_PDCA[4],
                    5: inp5 ? inp5.value : DEFAULT_PDCA[5]
                };

                let optionsData = null;
                if (itemType === 'checkbox_list' || itemCode === 'SPEC_1_6_SECONDARY') {
                    const opts = [];
                    document.querySelectorAll('#spec16-options-tbody .spec16-option-row').forEach(function(optRow, optIdx) {
                        const textInp = optRow.querySelector('.spec16-option-text');
                        const textVal = textInp ? textInp.value.trim() : '';
                        if (textVal) {
                            opts.push({
                                key: (optIdx + 1).toString(),
                                text: textVal
                            });
                        }
                    });
                    optionsData = opts;
                }

                secObj.items.push({
                    id: itemId,
                    item_code: itemCode,
                    name_th: itemName,
                    weight: itemScoreVal,
                    max_score: itemScoreVal,
                    input_type: itemType,
                    requires_evidence: reqEv,
                    options_data: optionsData,
                    criteria: crit
                });
            });
        }

        payload.sections.push(secObj);
    });

    // Gather Competencies
    document.querySelectorAll('.comp-row').forEach(function(row) {
        const compId = row.getAttribute('data-comp-id');
        const compName = row.querySelector('.comp-name-input').value;
        const compDef = row.querySelector('.comp-def-input').value;
        const compType = row.querySelector('.comp-type-input').value;
        const compLevel = parseInt(row.querySelector('.comp-level-input').value) || 3;

        payload.competencies.push({
            id: compId,
            name_th: compName,
            definition: compDef,
            competency_type: compType,
            expected_level: compLevel
        });
    });

    // Send via AJAX
    fetch(SAVE_ALL_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (btnTop) btnTop.disabled = false;
        if (btnBottom) btnBottom.disabled = false;

        if (res.success) {
            alert('💾 บันทึกการเปลี่ยนแปลงแบบประเมินทั้งหมดเรียบร้อยแล้ว!');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (res.message || 'บันทึกไม่สำเร็จ'));
        }
    })
    .catch(err => {
        if (btnTop) btnTop.disabled = false;
        if (btnBottom) btnBottom.disabled = false;
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    });
}

// Initial recalculation on page load
document.addEventListener('DOMContentLoaded', function() {
    recalcTotals();
});
</script>
