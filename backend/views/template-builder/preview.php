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
?>

<div class="template-builder-preview py-3">

    <!-- Notice Banner -->
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
        <div>
            <i class="bi bi-eye-fill fs-5 me-2"></i>
            <strong>หน้าจอพรีวิวเสมือนจริง (Interactive Live Preview):</strong> 
            ท่านสามารถทดลองคลิกเลือกระดับคะแนน 1-5 เพื่อดูการไฮไลต์และตรวจทานความถูกต้องของฟอร์มก่อนนำไปใช้จริง
        </div>
        <div>
            <?= Html::a('<i class="bi bi-pencil-square me-1"></i> กลับไปแก้ไข', ['builder', 'id' => $template->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>
    </div>

    <!-- Official Header Info Box -->
    <div class="card card-rmutt p-4 shadow-sm mb-4 bg-light border-0">
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-1 text-dark"><?= Html::encode($template->name_th) ?></h5>
            <div class="text-muted small">
                <?php if ($template->department): ?>
                    หน่วยงาน / สังกัด: <strong><?= Html::encode($template->department->name_th) ?></strong> | 
                <?php else: ?>
                    <span class="text-warning-emphasis">แม่แบบมาตรฐานกลาง (ใช้ทุกหน่วยงาน)</span> | 
                <?php endif; ?>
                ประเภทบุคลากร: <strong><?= Html::encode($template->personnelType->name_th) ?></strong>
            </div>
            <?php if ($template->description): ?>
                <p class="text-muted small mt-2 mb-0"><?= Html::encode($template->description) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- RENDER SECTIONS & ITEMS -->
    <?php foreach ($sections as $sIdx => $section): ?>
        <div class="card card-rmutt shadow-sm mb-4 border-0">
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
                                    <th>คำนิยาม</th>
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

                <?php else: ?>
                    <!-- KPI Items with PDCA 1-5 Levels -->
                    <?php foreach ($section->items as $iIdx => $item): ?>
                        <div class="card p-3 mb-3 border bg-light shadow-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-bold text-dark fs-6">
                                    <span class="badge bg-secondary me-2"><?= $iIdx + 1 ?></span>
                                    <?= Html::encode($item->name_th) ?>
                                </div>
                                <div>
                                    <span class="badge bg-info-subtle text-dark border border-info-subtle me-2">
                                        น้ำหนัก <?= number_format($item->max_weight, 0) ?>%
                                    </span>
                                    <?php if ($item->requires_evidence): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                            <i class="bi bi-paperclip me-1"></i> ต้องแนบหลักฐาน
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Interactive PDCA 5-Level Radio List -->
                            <div class="list-group mt-2">
                                <?php 
                                $critMap = [];
                                foreach ($item->criteria as $c) {
                                    $critMap[$c->level_value] = $c->description;
                                }
                                $pdcaTitles = [
                                    1 => 'ระดับ 1 (Plan): ',
                                    2 => 'ระดับ 2 (Do): ',
                                    3 => 'ระดับ 3 (Check): ',
                                    4 => 'ระดับ 4 (Act): ',
                                    5 => 'ระดับ 5 (Impact): ',
                                ];
                                for ($lvl = 1; $lvl <= 5; $lvl++):
                                    $desc = $critMap[$lvl] ?? "เกณฑ์ความสำเร็จระดับ {$lvl}";
                                ?>
                                    <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 py-2 criteria-row-<?= $item->id ?>" id="crit-label-<?= $item->id ?>-<?= $lvl ?>" style="cursor: pointer;">
                                        <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="preview_item_<?= $item->id ?>" value="<?= $lvl ?>" onchange="highlightPreviewCrit(<?= $item->id ?>, <?= $lvl ?>)">
                                        <span class="small">
                                            <strong><?= $pdcaTitles[$lvl] ?></strong> <?= Html::encode($desc) ?>
                                        </span>
                                    </label>
                                <?php endfor; ?>
                            </div>

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
