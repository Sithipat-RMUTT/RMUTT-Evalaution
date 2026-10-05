<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\EvaluationTemplate $template */
/** @var common\models\TemplateVersion $version */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */

$this->title = 'พรีวิวแบบประเมิน: ' . $template->name_th;
$this->params['breadcrumbs'][] = ['label' => 'จัดการแบบประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $template->name_th, 'url' => ['builder', 'id' => $template->id]];
$this->params['breadcrumbs'][] = 'พรีวิวเสมือนจริง';

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
?>

<div class="template-builder-preview py-3">

    <!-- Notice Banner -->
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
        <div>
            <i class="bi bi-eye-fill fs-5 me-2"></i>
            <strong>หน้าจอพรีวิวเสมือนจริง (Interactive Live Preview):</strong> 
            จำลองแบบฟอร์มตามที่บุคลากรและคณะกรรมการจะเห็นจริงในระบบ
        </div>
        <div>
            <?= Html::a('<i class="bi bi-pencil-square me-1"></i> กลับไปแก้ไข', ['builder', 'id' => $template->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>
    </div>

    <!-- Official Header Info Box -->
    <div class="card card-rmutt p-4 shadow-sm mb-4 bg-light border">
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-1 text-dark"><?= Html::encode($template->name_th) ?></h5>
            <div class="text-muted small">
                <?php if ($template->department): ?>
                    หน่วยงาน / สังกัด: <strong><?= Html::encode($template->department->name_th) ?></strong> | 
                <?php else: ?>
                    <span class="text-warning-emphasis">แม่แบบมาตรฐานกลาง (ใช้ทุกหน่วยงาน)</span> | 
                <?php endif; ?>
                ประเภทบุคลากร: <strong><?= Html::encode($template->personnelType ? $template->personnelType->name_th : '-') ?></strong>
            </div>
            <?php if ($template->description): ?>
                <p class="text-muted small mt-2 mb-0"><?= Html::encode($template->description) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- RENDER SECTIONS & ITEMS -->
    <?php foreach ($sections as $sIdx => $section): ?>
        <div class="card card-rmutt shadow-sm mb-4 border">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-white">
                    <i class="bi bi-journal-check me-2"></i> <?= Html::encode($section->name_th) ?>
                </h6>
                <span class="badge bg-white text-primary fs-6">
                    ค่าน้ำหนัก <?= number_format($section->weight, 0) ?>%
                </span>
            </div>
            <div class="card-body p-4">
                
                <?php if ($section->section_type === 'competency'): ?>
                    <!-- Competencies Table / Cards -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>หัวข้อสมรรถนะ</th>
                                    <th>คำนิยาม / พฤติกรรมที่บ่งชี้</th>
                                    <th style="width: 140px;" class="text-center">ระดับที่คาดหวัง</th>
                                    <th style="width: 220px;" class="text-center">ระดับที่ประเมินได้</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($competencies as $cIdx => $comp): ?>
                                    <tr>
                                        <td><?= $cIdx + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= Html::encode($comp->name_th) ?></div>
                                            <span class="badge bg-light text-muted border small"><?= $comp->competency_type === 'core' ? 'สมรรถนะหลัก' : 'สมรรถนะสายงาน' ?></span>
                                        </td>
                                        <td class="small text-muted"><?= Html::encode($comp->definition) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary fs-6">ระดับ <?= $comp->expected_level ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php for ($lvl = 1; $lvl <= 5; $lvl++): ?>
                                                    <input type="radio" class="btn-check" name="preview_comp_<?= $comp->id ?>" id="p_comp_<?= $comp->id ?>_<?= $lvl ?>" autocomplete="off" <?= $lvl == 3 ? 'checked' : '' ?>>
                                                    <label class="btn btn-outline-primary" for="p_comp_<?= $comp->id ?>_<?= $lvl ?>"><?= $lvl ?></label>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($section->section_code === 'SECONDARY_POLICY' || strpos($section->name_th, '๕.๑') !== false): ?>
                    <!-- Section 5.1: Policy Checklist Preview -->
                    <div class="alert alert-success border-success small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>เกณฑ์การประเมิน:</strong> ดำเนินการ ๑ ข้อ = ๑ คะแนน | ๒ ข้อ = ๓ คะแนน | ๓ ข้อขึ้นไป = ๕ คะแนน (เต็ม ๕ คะแนน &rarr; คิดเป็นค่าน้ำหนัก ๑๕%)
                    </div>
                    <div class="list-group">
                        <?php foreach ($policy7Options as $pIdx => $pText): ?>
                            <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2">
                                <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" name="preview_policy_<?= $pIdx ?>" value="<?= $pIdx ?>">
                                <span class="small">
                                    <strong class="text-primary">ข้อ <?= $pIdx ?>:</strong> <?= Html::encode($pText) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($section->section_code === 'SECONDARY_ACADEMIC' || strpos($section->name_th, '๕.๒') !== false): ?>
                    <!-- Section 5.2: Academic Guide Preview -->
                    <div class="alert alert-success border-success small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>เกณฑ์การประเมิน:</strong> ประเมินตามระดับความก้าวหน้า ๐ - ๕ คะแนน (เต็ม ๕ คะแนน &rarr; คิดเป็นค่าน้ำหนัก ๕%)
                    </div>
                    <div class="list-group">
                        <?php foreach ($acad5Levels as $aLvl => $aText): ?>
                            <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2">
                                <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="preview_academic" value="<?= $aLvl ?>" <?= $aLvl == 0 ? 'checked' : '' ?>>
                                <span class="small">
                                    <strong class="text-primary"><?= $aLvl ?> คะแนน:</strong> <?= Html::encode($aText) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <!-- KPI Items with PDCA 1-5 Levels or Direct Score -->
                    <?php foreach ($section->items as $iIdx => $item): ?>
                        <div class="card p-3 mb-3 border bg-light shadow-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-bold text-dark fs-6">
                                    <span class="badge bg-secondary me-2"><?= $iIdx + 1 ?></span>
                                    <?= Html::encode($item->name_th) ?>
                                </div>
                                <div>
                                    <span class="badge bg-info-subtle text-dark border border-info-subtle me-2">
                                        น้ำหนัก <?= number_format($item->max_weight > 0 ? $item->max_weight : $item->max_score, 0) ?>%
                                    </span>
                                    <?php if ($item->requires_evidence): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                            <i class="bi bi-paperclip me-1"></i> ต้องแนบหลักฐาน
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($item->input_type === 'checkbox_list' || $item->item_code === 'SPEC_1_6_SECONDARY'): 
                                $optList = [];
                                if (!empty($item->options_data)) {
                                    $parsed = is_string($item->options_data) ? json_decode($item->options_data, true) : $item->options_data;
                                    if (is_string($parsed)) $parsed = json_decode($parsed, true);
                                    if (is_array($parsed)) {
                                        foreach ($parsed as $pIdx => $opt) {
                                            if (is_array($opt) && isset($opt['text'])) {
                                                $optList[] = ['key' => (string)($opt['key'] ?? ($pIdx + 1)), 'text' => $opt['text']];
                                            } elseif (is_string($opt)) {
                                                $optList[] = ['key' => (string)($pIdx + 1), 'text' => $opt];
                                            }
                                        }
                                    }
                                }
                                if (empty($optList)) {
                                    $defaultOpts = [
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
                                    foreach ($defaultOpts as $k => $txt) {
                                        $optList[] = ['key' => (string)$k, 'text' => $txt];
                                    }
                                }
                            ?>
                                <div class="alert alert-warning border-warning small mb-3">
                                    <i class="bi bi-info-circle-fill me-1"></i>
                                    <strong>เกณฑ์การให้คะแนน:</strong> 6-10 ข้อ = 5 คะแนน | 5 ข้อ = 4 คะแนน | 3-4 ข้อ = 3 คะแนน | 2 ข้อ = 2 คะแนน | 1 ข้อ = 1 คะแนน (เต็ม 5 คะแนน)
                                </div>
                                <div class="list-group mt-2">
                                    <?php foreach ($optList as $oItem): ?>
                                        <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2">
                                            <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" name="preview_spec16_<?= $item->id ?>[]" value="<?= Html::encode($oItem['key']) ?>">
                                            <span class="small">
                                                <strong class="text-warning-emphasis">ข้อ <?= Html::encode($oItem['key']) ?>:</strong> <?= Html::encode($oItem['text']) ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($item->input_type === 'score_direct'): ?>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <span class="small text-muted">กรอกคะแนนประเมินโดยตรง (0 - <?= $item->max_score ?> คะแนน):</span>
                                    <div class="input-group input-group-sm" style="width: 140px;">
                                        <input type="number" step="0.5" min="0" max="<?= $item->max_score ?>" class="form-control form-control-sm text-center fw-bold" value="<?= $item->max_score ?>">
                                        <span class="input-group-text px-1">คะแนน</span>
                                    </div>
                                </div>

                            <?php else: ?>
                                <!-- Interactive PDCA 5-Level Radio List -->
                                <div class="list-group mt-2">
                                    <?php 
                                    $critMap = [];
                                    foreach ($item->criteria as $c) {
                                        $critMap[$c->level_value] = $c->description;
                                    }
                                    $defaultDescriptions = [
                                        1 => 'มีแผนการดำเนินงาน/แนวทางการดำเนินงาน',
                                        2 => 'ดำเนินการตามแผน/แนวทางที่กำหนด',
                                        3 => 'ทบทวน ตรวจสอบ ประเมินผลการดำเนินงาน',
                                        4 => 'แก้ไขปรับปรุงกระบวนการ',
                                        5 => 'ปรับปรุงต่อเนื่อง สร้างคุณค่าเพิ่มหรือนวัตกรรม',
                                    ];
                                    $pdcaTitles = [
                                        1 => 'ระดับ 1 (Plan): ',
                                        2 => 'ระดับ 2 (Do): ',
                                        3 => 'ระดับ 3 (Check): ',
                                        4 => 'ระดับ 4 (Act): ',
                                        5 => 'ระดับ 5 (Impact): ',
                                    ];
                                    for ($lvl = 1; $lvl <= 5; $lvl++):
                                        $desc = $critMap[$lvl] ?? ($defaultDescriptions[$lvl] ?? "เกณฑ์ความสำเร็จระดับ {$lvl}");
                                    ?>
                                        <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2 criteria-row-<?= $item->id ?>" id="crit-label-<?= $item->id ?>-<?= $lvl ?>" style="cursor: pointer;">
                                            <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="preview_item_<?= $item->id ?>" value="<?= $lvl ?>" onchange="highlightPreviewCrit(<?= $item->id ?>, <?= $lvl ?>)">
                                            <span class="small">
                                                <strong><?= $pdcaTitles[$lvl] ?></strong> <?= Html::encode($desc) ?>
                                            </span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>

</div>

<script>
function highlightPreviewCrit(itemId, level) {
    document.querySelectorAll('.criteria-row-' + itemId).forEach(function(el) {
        el.classList.remove('bg-success-subtle', 'text-success-emphasis', 'border-success-subtle', 'fw-semibold');
    });
    const target = document.getElementById('crit-label-' + itemId + '-' + level);
    if (target) {
        target.classList.add('bg-success-subtle', 'text-success-emphasis', 'border-success-subtle', 'fw-semibold');
    }
}
</script>
