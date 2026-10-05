<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\EvaluationTemplate $template */
/** @var common\models\TemplateVersion $version */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */
/** @var float $totalSectionWeight */
/** @var common\models\Department[] $departments */

$this->title = 'จัดการแบบประเมินผลการปฏิบัติงาน: ' . $template->name_th;
$this->params['breadcrumbs'][] = ['label' => 'จัดการแบบประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $template->name_th;

$csrfParam = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf';
$csrfToken = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '';

$ptCode = $template->personnelType ? $template->personnelType->code : '';
$isSpecial = ($ptCode === 'SPECIAL');
$isCivilOrUniv = ($ptCode === 'CIVIL' || $ptCode === 'UNIVERSITY');
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
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">น้ำหนักรวมทุกหมวด:</span>
                        <span class="badge <?= $totalSectionWeight == 100 ? 'bg-success' : 'bg-danger' ?> fs-6" id="total-weight-badge">
                            <span id="total-weight-num"><?= number_format($totalSectionWeight, 0) ?></span>% / 100%
                        </span>
                        <span id="weight-status-icon">
                            <?php if ($totalSectionWeight == 100): ?>
                                <i class="bi bi-check-circle-fill text-success" title="น้ำหนักรวมครบ 100% ถูกต้อง"></i>
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle-fill text-danger" title="น้ำหนักรวมควรได้ 100%"></i>
                            <?php endif; ?>
                        </span>
                    </div>

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

    <!-- 2. Official Document Header Card (Same as self_assess.php) -->
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
                    <span class="text-muted small">แก้ไขตัวชี้วัด เกณฑ์คะแนน หรือค่าน้ำหนักได้ในตารางด้านล่าง</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. RENDER SECTIONS & EVALUATION TABLES (Exact Official Evaluation Layout) -->
    <?php 
    $competencyRendered = false;
    foreach ($sections as $sIdx => $section): 
    ?>
        <?php if ($section->section_type === 'competency'): 
            $competencyRendered = true;
        ?>
            <!-- ========================================== -->
            <!-- SECTION TYPE: COMPETENCY TABLE (แบบ พม.)   -->
            <!-- ========================================== -->
            <div class="card card-rmutt shadow-sm mb-4 section-block" data-section-id="<?= $section->id ?>" data-section-type="competency">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 me-3">
                        <i class="bi bi-award-fill fs-5"></i>
                        <input type="text" class="form-control form-control-sm fw-bold section-name-input text-white border-0" 
                               style="background-color: rgba(255,255,255,0.2) !important; max-width: 500px;" 
                               value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดสมรรถนะ...">
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="text-white-50 small">ค่าน้ำหนักหมวด:</span>
                        <div class="input-group input-group-sm" style="width: 105px;">
                            <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                                   value="<?= $section->weight ?>" oninput="recalcTotals()">
                            <span class="input-group-text px-1">%</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 table-eval" id="comp-table">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="min-width: 250px;">หัวข้อสมรรถนะ (Competency)</th>
                                    <th style="min-width: 320px;">คำนิยาม / พฤติกรรมที่บ่งชี้</th>
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

        <?php else: 
            // Determine if this section is Direct Score (e.g. SPECIAL employee) or PDCA Level
            $hasAnyCriteria = false;
            foreach ($section->items as $it) {
                if ($it->input_type === 'pdca_level' || count($it->criteria) > 0) {
                    $hasAnyCriteria = true;
                    break;
                }
            }
            $isDirectScore = !$hasAnyCriteria && ($isSpecial || $section->section_type === 'general' || $section->items && $section->items[0]->input_type === 'score_direct');
        ?>
            <!-- ============================================================== -->
            <!-- SECTION TYPE: PERFORMANCE / WORK (แบบ ป.ผ. หรือ ด้านผลงาน)     -->
            <!-- ============================================================== -->
            <div class="card card-rmutt shadow-sm mb-4 section-block" data-section-id="<?= $section->id ?>" data-section-type="<?= Html::encode($section->section_type) ?>">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 me-3">
                        <span class="badge bg-white text-primary fw-bold">หมวดที่ <?= $sIdx + 1 ?></span>
                        <input type="text" class="form-control form-control-sm fw-bold section-name-input text-white border-0" 
                               style="background-color: rgba(255,255,255,0.2) !important; max-width: 500px;" 
                               value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดการประเมิน...">
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="text-white-50 small"><?= $isDirectScore ? 'คะแนนเต็มหมวด:' : 'ค่าน้ำหนักหมวด:' ?></span>
                        <div class="input-group input-group-sm" style="width: 110px;">
                            <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold section-weight-input" 
                                   value="<?= $section->weight ?>" oninput="recalcTotals()">
                            <span class="input-group-text px-1"><?= $isDirectScore ? 'คะแนน' : '%' ?></span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 table-eval kpi-table">
                            <thead class="table-light text-center align-middle">
                                <?php if ($isDirectScore): ?>
                                    <!-- Direct Score Table Header (พนักงานพิเศษเงินรายได้ / คะแนนตรง) -->
                                    <tr>
                                        <th style="width: 50px;">ข้อ</th>
                                        <th style="min-width: 320px;">รายการประเมิน / รายละเอียดภาระงาน</th>
                                        <th style="width: 140px;">คะแนนเต็ม</th>
                                        <th style="width: 60px;">จัดการ</th>
                                    </tr>
                                <?php else: ?>
                                    <!-- Official PDCA Table Header (แบบ ป.ผ. ข้าราชการ & พนักงานมหาวิทยาลัย) -->
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th style="min-width: 260px;">(๑) กิจกรรม / โครงการ / ภาระงาน หรือ ตัวชี้วัด</th>
                                        <th style="min-width: 520px;">(๒) เกณฑ์ระดับค่าเป้าหมายความสำเร็จ (PDCA ๑ - ๕)</th>
                                        <th style="width: 110px;">(๓)<br>น้ำหนัก (%)</th>
                                        <th style="width: 50px;">ลบ</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>

                            <tbody class="kpi-tbody">
                                <?php if (empty($section->items)): ?>
                                    <tr class="empty-kpi-row">
                                        <td colspan="<?= $isDirectScore ? 4 : 5 ?>" class="text-center py-4 text-muted">
                                            ยังไม่มีรายการตัวชี้วัดในหมวดนี้ กรุณากดปุ่ม <strong>"+ เพิ่มรายการ"</strong> ด้านล่าง
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($section->items as $iIdx => $item): 
                                        $critMap = [];
                                        foreach ($item->criteria as $crit) {
                                            $critMap[$crit->level_value] = $crit->description;
                                        }
                                        $scoreVal = !empty($item->max_score) ? $item->max_score : $item->max_weight;
                                    ?>
                                        <tr class="kpi-row" data-item-id="<?= $item->id ?>">
                                            <!-- Column 1: Row Number -->
                                            <td class="text-center fw-bold kpi-row-number">
                                                <?= $iIdx + 1 ?>
                                            </td>

                                            <?php if ($isDirectScore): ?>
                                                <!-- Direct Score: Item Name & Description -->
                                                <td>
                                                    <input type="text" class="form-control form-control-sm fw-semibold item-name-input mb-1" 
                                                           value="<?= Html::encode($item->name_th) ?>" placeholder="ระบุรายการประเมิน...">
                                                    <input type="hidden" class="item-type-select" value="score_direct">
                                                    <input type="hidden" class="item-ev-input" value="0">
                                                </td>
                                                <!-- Direct Score: Max Score Input -->
                                                <td class="text-center">
                                                    <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: 0 auto;">
                                                        <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold item-weight-input" 
                                                               value="<?= $scoreVal ?>" oninput="recalcTotals()">
                                                        <span class="input-group-text px-1 text-muted">คะแนน</span>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <!-- PDCA: Column (1) Work / KPI Name -->
                                                <td>
                                                    <textarea class="form-control form-control-sm item-name-input fw-semibold mb-1" rows="3" 
                                                              placeholder="ระบุกิจกรรม/โครงการ/ภาระงาน หรือตัวชี้วัด..."><?= Html::encode($item->name_th) ?></textarea>
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

                                                <!-- PDCA: Column (2) 5 Levels Box (Matching self_assess.php) -->
                                                <td>
                                                    <div class="pdca-box">
                                                        <div class="mb-1 d-flex align-items-center gap-1.5">
                                                            <span class="badge bg-secondary-subtle text-dark border level-badge-lbl">
                                                                ระดับ ๑ (Plan)
                                                            </span>
                                                            <input type="text" class="form-control form-control-sm crit-input-1" 
                                                                   value="<?= Html::encode($critMap[1] ?? '') ?>" 
                                                                   placeholder="มีแผนการดำเนินงาน / กำหนดขั้นตอนปฏิบัติงานชัดเจน">
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center gap-1.5">
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle level-badge-lbl">
                                                                ระดับ ๒ (Do)
                                                            </span>
                                                            <input type="text" class="form-control form-control-sm crit-input-2" 
                                                                   value="<?= Html::encode($critMap[2] ?? '') ?>" 
                                                                   placeholder="ปฏิบัติงานได้ตามขั้นตอนและแผนงานที่กำหนด">
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center gap-1.5">
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle level-badge-lbl">
                                                                ระดับ ๓ (Check)
                                                            </span>
                                                            <input type="text" class="form-control form-control-sm crit-input-3" 
                                                                   value="<?= Html::encode($critMap[3] ?? '') ?>" 
                                                                   placeholder="ตรวจสอบ ทบทวน ผลงานเป็นไปตามเป้าหมายและตรงเวลา">
                                                        </div>
                                                        <div class="mb-1 d-flex align-items-center gap-1.5">
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle level-badge-lbl">
                                                                ระดับ ๔ (Act)
                                                            </span>
                                                            <input type="text" class="form-control form-control-sm crit-input-4" 
                                                                   value="<?= Html::encode($critMap[4] ?? '') ?>" 
                                                                   placeholder="ปรับปรุงกระบวนการทำงาน มีคู่มือหรือแนวทางปฏิบัติที่พัฒนาขึ้น">
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1.5">
                                                            <span class="badge border level-badge-lbl" style="background-color: #f3e8ff; color: #6b21a8; border-color: #d8b4fe;">
                                                                ระดับ ๕ (Impact)
                                                            </span>
                                                            <input type="text" class="form-control form-control-sm crit-input-5" 
                                                                   value="<?= Html::encode($critMap[5] ?? '') ?>" 
                                                                   placeholder="ปรับปรุงต่อเนื่อง สร้างนวัตกรรม เกิดผลลัพธ์ที่เป็นประโยชน์สูง">
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- PDCA: Column (3) Weight (%) -->
                                                <td class="text-center">
                                                    <div class="input-group input-group-sm justify-content-center" style="max-width: 95px; margin: 0 auto;">
                                                        <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold item-weight-input" 
                                                               value="<?= $item->max_weight ?>" oninput="recalcTotals()">
                                                        <span class="input-group-text px-1 text-muted">%</span>
                                                    </div>
                                                </td>
                                            <?php endif; ?>

                                            <!-- Column: Delete -->
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
                                    <td colspan="<?= $isDirectScore ? 4 : 5 ?>" class="p-2.5">
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addKpiRow(this, <?= $section->id ?>, <?= $isDirectScore ? 'true' : 'false' ?>)">
                                            <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการในหมวดนี้
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- In case competencies exist but weren't in a competency-typed section -->
    <?php if (!$competencyRendered && !empty($competencies)): ?>
        <div class="card card-rmutt shadow-sm mb-4 section-block" data-section-id="0" data-section-type="competency">
            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-white"><i class="bi bi-award-fill me-2"></i> การประเมินสมรรถนะ (พม.)</h6>
                <span class="badge bg-white text-primary">สมรรถนะบุคลากร</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 table-eval">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="min-width: 250px;">หัวข้อสมรรถนะ</th>
                                <th style="min-width: 320px;">คำนิยาม / พฤติกรรมที่บ่งชี้</th>
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
    <?php endif; ?>

    <!-- 4. Bottom Action Bar (Same as self_assess.php footer) -->
    <div class="card card-rmutt shadow-sm p-4 mb-5 border-0 bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="fw-bold fs-6 text-dark mb-1">
                    <i class="bi bi-check2-circle text-primary me-1"></i> ตรวจสอบแบบประเมินและบันทึกข้อมูล
                </div>
                <div class="text-muted small">
                    ค่าน้ำหนักรวมทุกหมวด: <span id="summary-total-weight-text" class="fw-bold text-primary"><?= number_format($totalSectionWeight, 0) ?>%</span> 
                    (ระบบจะใช้เกณฑ์นี้ในการคำนวณคะแนนของบุคลากรในสังกัด)
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

// Real-time weight and item counter
function recalcTotals() {
    let totalSecWeight = 0;
    document.querySelectorAll('.section-weight-input').forEach(function(inp) {
        totalSecWeight += parseFloat(inp.value) || 0;
    });

    const badge = document.getElementById('total-weight-badge');
    const badgeNum = document.getElementById('total-weight-num');
    const statusIcon = document.getElementById('weight-status-icon');
    const sumText = document.getElementById('summary-total-weight-text');

    if (badgeNum) badgeNum.innerText = totalSecWeight.toFixed(0);
    if (sumText) sumText.innerText = totalSecWeight.toFixed(0) + '%';

    if (totalSecWeight === 100) {
        if (badge) {
            badge.className = 'badge bg-success fs-6';
        }
        if (statusIcon) {
            statusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success" title="น้ำหนักรวมครบ 100% ถูกต้อง"></i>';
        }
    } else {
        if (badge) {
            badge.className = 'badge bg-danger fs-6';
        }
        if (statusIcon) {
            statusIcon.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger" title="น้ำหนักรวมควรได้ 100%"></i>';
        }
    }
}

// Add KPI item row
function addKpiRow(btn, secId, isDirectScore) {
    const table = btn.closest('.kpi-table');
    const tbody = table.querySelector('.kpi-tbody');
    const emptyRow = tbody.querySelector('.empty-kpi-row');
    if (emptyRow) emptyRow.remove();

    const count = tbody.querySelectorAll('.kpi-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'kpi-row';
    tr.setAttribute('data-item-id', '');

    if (isDirectScore) {
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
                    <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold item-weight-input" 
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
    } else {
        tr.innerHTML = `
            <td class="text-center fw-bold kpi-row-number">${count}</td>
            <td>
                <textarea class="form-control form-control-sm item-name-input fw-semibold mb-1" rows="3" 
                          placeholder="ระบุกิจกรรม/โครงการ/ภาระงาน หรือตัวชี้วัด..."></textarea>
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
                        <input type="text" class="form-control form-control-sm crit-input-1" placeholder="มีแผนการดำเนินงาน / กำหนดขั้นตอนปฏิบัติงานชัดเจน">
                    </div>
                    <div class="mb-1 d-flex align-items-center gap-1.5">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle level-badge-lbl">ระดับ ๒ (Do)</span>
                        <input type="text" class="form-control form-control-sm crit-input-2" placeholder="ปฏิบัติงานได้ตามขั้นตอนและแผนงานที่กำหนด">
                    </div>
                    <div class="mb-1 d-flex align-items-center gap-1.5">
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle level-badge-lbl">ระดับ ๓ (Check)</span>
                        <input type="text" class="form-control form-control-sm crit-input-3" placeholder="ตรวจสอบ ทบทวน ผลงานเป็นไปตามเป้าหมายและตรงเวลา">
                    </div>
                    <div class="mb-1 d-flex align-items-center gap-1.5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle level-badge-lbl">ระดับ ๔ (Act)</span>
                        <input type="text" class="form-control form-control-sm crit-input-4" placeholder="ปรับปรุงกระบวนการทำงาน มีคู่มือหรือแนวทางปฏิบัติที่พัฒนาขึ้น">
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="badge border level-badge-lbl" style="background-color: #f3e8ff; color: #6b21a8; border-color: #d8b4fe;">ระดับ ๕ (Impact)</span>
                        <input type="text" class="form-control form-control-sm crit-input-5" placeholder="ปรับปรุงต่อเนื่อง สร้างนวัตกรรม เกิดผลลัพธ์ที่เป็นประโยชน์สูง">
                    </div>
                </div>
            </td>
            <td class="text-center">
                <div class="input-group input-group-sm justify-content-center" style="max-width: 95px; margin: 0 auto;">
                    <input type="number" step="0.5" min="0" max="100" class="form-control form-control-sm text-center fw-bold item-weight-input" 
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
    }

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

// Competency functions
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
        const secNameInput = secEl.querySelector('.section-name-input');
        const secName = secNameInput ? secNameInput.value : '';
        const secWeightInput = secEl.querySelector('.section-weight-input');
        const secWeight = secWeightInput ? parseFloat(secWeightInput.value) || 0 : 0;

        const secObj = {
            id: secId,
            name_th: secName,
            weight: secWeight,
            section_type: secType,
            items: []
        };

        if (secType !== 'competency') {
            secEl.querySelectorAll('.kpi-row').forEach(function(row) {
                const typeSelect = row.querySelector('.item-type-select');
                const itemType = typeSelect ? typeSelect.value : 'pdca_level';
                const itemId = row.getAttribute('data-item-id') || '';
                const itemNameInput = row.querySelector('.item-name-input');
                const itemName = itemNameInput ? itemNameInput.value : '';
                const itemWeightInput = row.querySelector('.item-weight-input');
                const itemScoreVal = itemWeightInput ? parseFloat(itemWeightInput.value) || 0 : 0;
                const reqEv = row.querySelector('.item-ev-input') && row.querySelector('.item-ev-input').checked ? 1 : 0;

                const inp1 = row.querySelector('.crit-input-1');
                const inp2 = row.querySelector('.crit-input-2');
                const inp3 = row.querySelector('.crit-input-3');
                const inp4 = row.querySelector('.crit-input-4');
                const inp5 = row.querySelector('.crit-input-5');

                const crit = {
                    1: inp1 ? inp1.value : '',
                    2: inp2 ? inp2.value : '',
                    3: inp3 ? inp3.value : '',
                    4: inp4 ? inp4.value : '',
                    5: inp5 ? inp5.value : ''
                };

                secObj.items.push({
                    id: itemId,
                    name_th: itemName,
                    weight: itemScoreVal,
                    max_score: itemScoreVal,
                    input_type: itemType,
                    requires_evidence: reqEv,
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
</script>
