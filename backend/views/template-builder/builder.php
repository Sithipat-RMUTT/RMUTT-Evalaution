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

$this->title = 'จัดการแบบประเมิน (Excel Grid): ' . $template->name_th;
$this->params['breadcrumbs'][] = ['label' => 'จัดการแบบประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $template->name_th;

$csrfParam = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf';
$csrfToken = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '';
?>

<style>
.excel-table {
    border: 1px solid #dee2e6;
    background: #fff;
}
.excel-table th {
    background-color: #f1f5f9;
    color: #334155;
    font-size: 0.85rem;
    font-weight: 700;
    vertical-align: middle;
    border: 1px solid #cbd5e1;
    white-space: nowrap;
}
.excel-table td {
    padding: 3px 4px;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
}
.excel-input {
    border: 1px solid transparent;
    border-radius: 3px;
    padding: 4px 6px;
    font-size: 0.875rem;
    width: 100%;
    background: transparent;
    transition: all 0.15s ease-in-out;
}
.excel-input:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.excel-input:focus {
    border-color: #0d6efd;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    outline: none;
}
.sticky-bar {
    position: sticky;
    top: 60px;
    z-index: 1020;
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, 0.95);
}
</style>

<div class="template-builder-excel py-3">

    <!-- Top Sticky Control Bar -->
    <div class="card card-rmutt shadow-sm mb-4 sticky-bar border-2 border-primary">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                
                <!-- Left: Title & Badges -->
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <?= Html::a('<i class="bi bi-arrow-left me-1"></i> กลับ', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    
                    <div class="flex-grow-1" style="max-width: 500px;">
                        <input type="text" id="template-title-input" class="form-control form-control-sm fw-bold border-0 bg-transparent fs-6" 
                               value="<?= Html::encode($template->name_th) ?>" placeholder="ชื่อแบบประเมิน...">
                    </div>

                    <?php if ($template->department): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            🏢 <?= Html::encode($template->department->name_th) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                            ⭐ แม่แบบมาตรฐานกลาง
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-light text-dark border">
                        <?= Html::encode($template->personnelType->name_th) ?>
                    </span>
                </div>

                <!-- Right: Real-time Weight & Master Save Button -->
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center px-3 py-1 rounded bg-light border">
                        <small class="text-muted me-2">น้ำหนักรวม:</small>
                        <span id="total-weight-badge" class="fw-bold fs-6 <?= $totalSectionWeight == 100 ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($totalSectionWeight, 1) ?>%
                        </span>
                        <span id="weight-status-icon" class="ms-1">
                            <?php if ($totalSectionWeight == 100): ?>
                                <i class="bi bi-check-circle-fill text-success" title="สัดส่วนครบ 100% ถูกต้อง"></i>
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle-fill text-danger" title="ค่าน้ำหนักต้องรวมกันได้ 100%"></i>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?= Html::a('<i class="bi bi-eye-fill me-1"></i> พรีวิว', ['preview', 'id' => $template->id], [
                        'class' => 'btn btn-sm btn-outline-info text-dark',
                        'target' => '_blank',
                    ]) ?>

                    <button type="button" class="btn btn-sm btn-success px-4 fw-bold shadow-sm" onclick="saveEntireGrid()">
                        <i class="bi bi-floppy-fill me-1"></i> บันทึกทั้งหมด (Save)
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- SECTIONS CONTAINER -->
    <div id="grid-sections-container">
        <?php foreach ($sections as $sIdx => $section): ?>
            <?php if ($section->section_type === 'competency'): ?>
                
                <!-- SECTION 2: COMPETENCIES SPREADSHEET TABLE -->
                <div class="card card-rmutt shadow-sm mb-4 border-0 section-block" data-section-id="<?= $section->id ?>" data-section-type="competency">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="badge bg-info text-dark fs-6">ส่วนที่ <?= $sIdx + 1 ?></span>
                            <input type="text" class="form-control form-control-sm fw-bold border-0 bg-transparent section-name-input flex-grow-1" 
                                   value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดสมรรถนะ...">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 160px;">
                                <span class="input-group-text bg-white">ค่าน้ำหนัก</span>
                                <input type="number" step="0.5" class="form-control text-end fw-bold section-weight-input" 
                                       value="<?= floatval($section->weight) ?>" onchange="recalculateTotalWeight()">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-2 bg-white">
                        <div class="table-responsive">
                            <table class="table excel-table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th style="width: 25%;">หัวข้อสมรรถนะ</th>
                                        <th>คำนิยาม / พฤติกรรมบ่งชี้</th>
                                        <th style="width: 140px;">ประเภท</th>
                                        <th style="width: 120px;" class="text-center">ระดับที่คาดหวัง</th>
                                        <th style="width: 45px;" class="text-center">ลบ</th>
                                    </tr>
                                </thead>
                                <tbody id="comp-table-body">
                                    <?php foreach ($competencies as $cIdx => $comp): ?>
                                        <tr class="comp-row" data-comp-id="<?= $comp->id ?>">
                                            <td class="text-center text-muted comp-row-num"><?= $cIdx + 1 ?></td>
                                            <td>
                                                <input type="text" class="excel-input fw-bold comp-name-input" value="<?= Html::encode($comp->name_th) ?>" placeholder="ชื่อสมรรถนะ...">
                                            </td>
                                            <td>
                                                <input type="text" class="excel-input text-muted comp-def-input" value="<?= Html::encode($comp->definition) ?>" placeholder="คำอธิบายสมรรถนะ...">
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm border-0 bg-transparent comp-type-input">
                                                    <option value="core" <?= $comp->competency_type === 'core' ? 'selected' : '' ?>>สมรรถนะหลัก</option>
                                                    <option value="functional" <?= $comp->competency_type === 'functional' ? 'selected' : '' ?>>สมรรถนะสายงาน</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <select class="form-select form-select-sm border-0 bg-transparent comp-level-input text-center fw-bold text-primary">
                                                    <?php for ($lvl = 1; $lvl <= 5; $lvl++): ?>
                                                        <option value="<?= $lvl ?>" <?= $comp->expected_level == $lvl ? 'selected' : '' ?>>ระดับ <?= $lvl ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0" onclick="removeRow(this)" title="ลบแถวนี้">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 px-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCompRow()">
                                <i class="bi bi-plus-circle me-1"></i> เพิ่มแถวสมรรถนะใหม่
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openCompetencyPoolModal()">
                                <i class="bi bi-bookmark-star-fill me-1"></i> เลือกจากคลังสมรรถนะ มทร.ธัญบุรี
                            </button>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <!-- SECTION 1: KPI ITEMS SPREADSHEET TABLE -->
                <div class="card card-rmutt shadow-sm mb-4 border-0 section-block" data-section-id="<?= $section->id ?>" data-section-type="main_work">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="badge bg-primary fs-6">ส่วนที่ <?= $sIdx + 1 ?></span>
                            <input type="text" class="form-control form-control-sm fw-bold border-0 bg-transparent section-name-input flex-grow-1" 
                                   value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดผลสัมฤทธิ์...">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 160px;">
                                <span class="input-group-text bg-white">ค่าน้ำหนัก</span>
                                <input type="number" step="0.5" class="form-control text-end fw-bold section-weight-input" 
                                       value="<?= floatval($section->weight) ?>" onchange="recalculateTotalWeight()">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-2 bg-white">
                        <div class="table-responsive">
                            <table class="table excel-table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 35px;" class="text-center">#</th>
                                        <th style="width: 25%;">ชื่อภาระงาน / ตัวชี้วัด</th>
                                        <th style="width: 140px;">รูปแบบการประเมิน</th>
                                        <th style="width: 85px;" class="text-center">คะแนน/น้ำหนัก</th>
                                        <th style="width: 11%;">เกณฑ์ 1 (Plan)</th>
                                        <th style="width: 11%;">เกณฑ์ 2 (Do)</th>
                                        <th style="width: 11%;">เกณฑ์ 3 (Check)</th>
                                        <th style="width: 11%;">เกณฑ์ 4 (Act)</th>
                                        <th style="width: 11%;">เกณฑ์ 5 (Impact)</th>
                                        <th style="width: 45px;" class="text-center" title="ต้องแนบหลักฐานหรือไม่">แนบไฟล์</th>
                                        <th style="width: 40px;" class="text-center">ลบ</th>
                                    </tr>
                                </thead>
                                <tbody class="kpi-table-body" id="kpi-body-<?= $section->id ?>">
                                    <?php foreach ($section->items as $iIdx => $item): 
                                        $critMap = [];
                                        foreach ($item->criteria as $c) {
                                            $critMap[$c->level_value] = $c->description;
                                        }
                                        $inputType = $item->input_type ?: 'pdca_level';
                                        $displayScore = ($inputType === 'score_direct' || $inputType === 'checkbox_list') 
                                            ? floatval($item->max_score ?: 10.0) 
                                            : floatval($item->max_weight ?: 20.0);
                                    ?>
                                        <tr class="kpi-row" data-item-id="<?= $item->id ?>">
                                            <td class="text-center text-muted kpi-row-num"><?= $iIdx + 1 ?></td>
                                            <td>
                                                <input type="text" class="excel-input fw-bold item-name-input" value="<?= Html::encode($item->name_th) ?>" placeholder="ระบุชื่อภาระงาน / กิจกรรม / ตัวชี้วัด...">
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm border-0 bg-transparent item-type-select" onchange="toggleItemTypeUI(this)">
                                                    <option value="pdca_level" <?= $inputType === 'pdca_level' ? 'selected' : '' ?>>📊 PDCA 5 ระดับ</option>
                                                    <option value="score_direct" <?= $inputType === 'score_direct' ? 'selected' : '' ?>>🔢 คะแนนเต็มโดยตรง</option>
                                                    <option value="checkbox_list" <?= $inputType === 'checkbox_list' ? 'selected' : '' ?>>☑️ เช็คลิสต์ 10 ข้อ</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.5" class="excel-input text-end fw-bold item-weight-input text-primary" 
                                                       value="<?= $displayScore ?>" placeholder="0" title="ค่าน้ำหนัก % หรือคะแนนเต็มของข้อนี้">
                                            </td>
                                            
                                            <?php if ($inputType === 'score_direct'): ?>
                                                <td colspan="5" class="text-muted small py-2 px-3 bg-light text-center crit-cell-direct">
                                                    <i class="bi bi-info-circle me-1 text-primary"></i> 
                                                    แบบประเมินให้คะแนนโดยตรง (ผู้รับการประเมินและผู้ประเมินกรอกคะแนนได้ตั้งแต่ 0 ถึง <span class="fw-bold text-primary"><?= $displayScore ?></span> คะแนน)
                                                </td>
                                            <?php elseif ($inputType === 'checkbox_list'): ?>
                                                <td colspan="5" class="text-muted small py-2 px-3 bg-light text-center crit-cell-direct">
                                                    <i class="bi bi-check2-square me-1 text-success"></i> 
                                                    แบบเช็คลิสต์ภาระงานรอง 10 ข้อ (เลือกติ๊กข้อที่ปฏิบัติ คำนวณเป็นระดับคะแนน 1-5 อัตโนมัติ)
                                                </td>
                                            <?php else: ?>
                                                <td>
                                                    <input type="text" class="excel-input small crit-input-1" value="<?= Html::encode($critMap[1] ?? '') ?>" placeholder="ระดับ 1 (Plan)...">
                                                </td>
                                                <td>
                                                    <input type="text" class="excel-input small crit-input-2" value="<?= Html::encode($critMap[2] ?? '') ?>" placeholder="ระดับ 2 (Do)...">
                                                </td>
                                                <td>
                                                    <input type="text" class="excel-input small crit-input-3" value="<?= Html::encode($critMap[3] ?? '') ?>" placeholder="ระดับ 3 (Check)...">
                                                </td>
                                                <td>
                                                    <input type="text" class="excel-input small crit-input-4" value="<?= Html::encode($critMap[4] ?? '') ?>" placeholder="ระดับ 4 (Act)...">
                                                </td>
                                                <td>
                                                    <input type="text" class="excel-input small crit-input-5" value="<?= Html::encode($critMap[5] ?? '') ?>" placeholder="ระดับ 5 (Impact)...">
                                                </td>
                                            <?php endif; ?>

                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input item-ev-input" <?= $item->requires_evidence ? 'checked' : '' ?> title="ติ๊กถ้าต้องแนบไฟล์">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0" onclick="removeRow(this)" title="ลบแถวนี้">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 px-2">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="addKpiRow(<?= $section->id ?>)">
                                <i class="bi bi-plus-circle me-1"></i> เพิ่มแถวตัวชี้วัด KPI ใหม่
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillStandardPdca(<?= $section->id ?>)">
                                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> เติมข้อความเกณฑ์ PDCA มาตรฐานให้อัตโนมัติ
                            </button>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Master Save Button Card -->
    <div class="card p-3 shadow-sm bg-light border-0 text-center mb-5">
        <div>
            <button type="button" class="btn btn-success btn-lg px-5 shadow fw-bold" onclick="saveEntireGrid()">
                <i class="bi bi-floppy-fill me-2"></i> บันทึกข้อมูลแบบประเมินทั้งหมด (Save All)
            </button>
            <div class="text-muted small mt-2">
                แก้ไขข้อมูลในตารางเสร็จแล้ว กดปุ่มนี้เพื่อบันทึกทุกหมวด ทุกตัวชี้วัด และสมรรถนะในครั้งเดียว
            </div>
        </div>
    </div>

</div>

<!-- Modal RMUTT Competency Pool -->
<div class="modal fade" id="compPoolModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-bookmark-star-fill me-1"></i> เลือกสมรรถนะจากคลังมาตรฐาน มทร.ธัญบุรี</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="comp-pool-body">
                <div class="text-center py-4 text-muted">กำลังโหลดคลังสมรรถนะ...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
const TEMPLATE_ID = <?= $template->id ?>;
const SAVE_ALL_URL = '<?= Url::to(['save-all', 'id' => $template->id]) ?>';
const COMP_POOL_URL = '<?= Url::to(['competency-pool']) ?>';
const CSRF_PARAM = '<?= $csrfParam ?>';
const CSRF_TOKEN = '<?= $csrfToken ?>';

function recalculateTotalWeight() {
    let total = 0;
    document.querySelectorAll('.section-weight-input').forEach(function(inp) {
        total += parseFloat(inp.value) || 0;
    });
    const badge = document.getElementById('total-weight-badge');
    const icon = document.getElementById('weight-status-icon');
    if (badge) {
        badge.textContent = total.toFixed(1) + '%';
        if (Math.abs(total - 100) < 0.01) {
            badge.className = 'fw-bold fs-6 text-success';
            icon.innerHTML = '<i class="bi bi-check-circle-fill text-success" title="สัดส่วนครบ 100% ถูกต้อง"></i>';
        } else {
            badge.className = 'fw-bold fs-6 text-danger';
            icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger" title="ค่าน้ำหนักต้องรวมกันได้ 100%"></i>';
        }
    }
}

function removeRow(btn) {
    const tr = btn.closest('tr');
    const tbody = tr.closest('tbody');
    tr.remove();
    renumberRows(tbody);
}

function renumberRows(tbody) {
    if (!tbody) return;
    tbody.querySelectorAll('tr').forEach(function(tr, idx) {
        const numCell = tr.querySelector('.kpi-row-num') || tr.querySelector('.comp-row-num');
        if (numCell) numCell.textContent = idx + 1;
    });
}

function addKpiRow(sectionId) {
    const tbody = document.getElementById('kpi-body-' + sectionId);
    const rowCount = tbody.querySelectorAll('tr').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'kpi-row';
    tr.setAttribute('data-item-id', '');
    tr.innerHTML = `
        <td class="text-center text-muted kpi-row-num">${rowCount}</td>
        <td>
            <input type="text" class="excel-input fw-bold item-name-input" value="ตัวชี้วัดที่ ${rowCount}" placeholder="ระบุชื่อภาระงาน / กิจกรรม / ตัวชี้วัด...">
        </td>
        <td>
            <input type="number" step="0.5" class="excel-input text-end fw-bold item-weight-input" value="20" placeholder="0">
        </td>
        <td>
            <select class="form-select form-select-sm border-0 bg-transparent item-type-select" onchange="toggleItemTypeUI(this)">
                <option value="pdca_level">PDCA (1-5)</option>
                <option value="score_direct">คะแนนตรง</option>
                <option value="checkbox_list">เช็คลิสต์</option>
            </select>
        </td>
        <td>
            <input type="text" class="excel-input small crit-input-1" value="มีการวางแผนและกำหนดขั้นตอนการดำเนินงานชัดเจน" placeholder="ระดับ 1 (Plan)...">
        </td>
        <td>
            <input type="text" class="excel-input small crit-input-2" value="ดำเนินการปฏิบัติตามแผนงานที่กำหนดได้ตามระยะเวลา" placeholder="ระดับ 2 (Do)...">
        </td>
        <td>
            <input type="text" class="excel-input small crit-input-3" value="มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน" placeholder="ระดับ 3 (Check)...">
        </td>
        <td>
            <input type="text" class="excel-input small crit-input-4" value="ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ" placeholder="ระดับ 4 (Act)...">
        </td>
        <td>
            <input type="text" class="excel-input small crit-input-5" value="ผลงานเกิดประโยชน์เชิงประจักษ์ และได้รับการตอบรับในระดับดีมาก" placeholder="ระดับ 5 (Impact)...">
        </td>
        <td class="text-center">
            <input type="checkbox" class="form-check-input item-ev-input" checked title="ติ๊กถ้าต้องแนบไฟล์">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0" onclick="removeRow(this)" title="ลบแถวนี้">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function toggleItemTypeUI(select) {
    const tr = select.closest('tr');
    const val = select.value;
    const isDirect = val === 'score_direct';
    const isChecklist = val === 'checkbox_list';
    
    let cell1 = tr.querySelector('.crit-input-1');
    let cellDirect = tr.querySelector('.crit-cell-direct');

    if (isDirect || isChecklist) {
        if (cell1) {
            const td1 = cell1.closest('td');
            const td2 = tr.querySelector('.crit-input-2').closest('td');
            const td3 = tr.querySelector('.crit-input-3').closest('td');
            const td4 = tr.querySelector('.crit-input-4').closest('td');
            const td5 = tr.querySelector('.crit-input-5').closest('td');
            if (td2) td2.remove(); 
            if (td3) td3.remove(); 
            if (td4) td4.remove(); 
            if (td5) td5.remove();
            td1.setAttribute('colspan', '5');
            td1.className = 'text-muted small py-2 px-3 bg-light text-center crit-cell-direct';
            td1.innerHTML = isDirect 
                ? '<i class="bi bi-info-circle me-1 text-primary"></i> แบบประเมินให้คะแนนโดยตรง (ผู้รับการประเมินและผู้ประเมินกรอกคะแนนตามคะแนนเต็ม)'
                : '<i class="bi bi-check2-square me-1 text-success"></i> แบบเช็คลิสต์ภาระงานรอง 10 ข้อ (คำนวณคะแนน 1-5 อัตโนมัติ)';
        }
    } else {
        if (cellDirect) {
            cellDirect.removeAttribute('colspan');
            cellDirect.className = '';
            cellDirect.innerHTML = '<input type="text" class="excel-input small crit-input-1" value="มีการวางแผนและกำหนดขั้นตอนชัดเจน" placeholder="ระดับ 1 (Plan)...">';
            
            const evTd = tr.querySelector('.item-ev-input').closest('td');
            const labels = ['', '', 'ดำเนินการปฏิบัติตามแผน', 'ตรวจสอบประเมินผล', 'ปรับปรุงแก้ไขคุณภาพ', 'เกิดประโยชน์เชิงประจักษ์'];
            for (let i = 2; i <= 5; i++) {
                const td = document.createElement('td');
                td.innerHTML = `<input type="text" class="excel-input small crit-input-${i}" value="${labels[i]}" placeholder="ระดับ ${i}...">`;
                tr.insertBefore(td, evTd);
            }
        }
    }
}

function fillStandardPdca(sectionId) {
    const tbody = document.getElementById('kpi-body-' + sectionId);
    tbody.querySelectorAll('tr').forEach(function(tr) {
        const inp1 = tr.querySelector('.crit-input-1');
        const inp2 = tr.querySelector('.crit-input-2');
        const inp3 = tr.querySelector('.crit-input-3');
        const inp4 = tr.querySelector('.crit-input-4');
        const inp5 = tr.querySelector('.crit-input-5');

        if (!inp1.value) inp1.value = 'มีการวางแผนและกำหนดขั้นตอนการดำเนินงานชัดเจน';
        if (!inp2.value) inp2.value = 'ดำเนินการปฏิบัติตามแผนงานที่กำหนดได้ตามระยะเวลา';
        if (!inp3.value) inp3.value = 'มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน';
        if (!inp4.value) inp4.value = 'ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ';
        if (!inp5.value) inp5.value = 'ผลงานเกิดประโยชน์เชิงประจักษ์ และได้รับการตอบรับในระดับดีมาก';
    });
    alert('เติมข้อความเกณฑ์ PDCA 1-5 สำเร็จรูปเรียบร้อยแล้ว อย่าลืมกดปุ่ม "บันทึกทั้งหมด" ด้านบน');
}

function addCompRow(name = '', def = '', type = 'core', level = 3) {
    const tbody = document.getElementById('comp-table-body');
    const rowCount = tbody.querySelectorAll('tr').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'comp-row';
    tr.setAttribute('data-comp-id', '');
    tr.innerHTML = `
        <td class="text-center text-muted comp-row-num">${rowCount}</td>
        <td>
            <input type="text" class="excel-input fw-bold comp-name-input" value="${escapeHtml(name || ('สมรรถนะที่ ' + rowCount))}" placeholder="ชื่อสมรรถนะ...">
        </td>
        <td>
            <input type="text" class="excel-input text-muted comp-def-input" value="${escapeHtml(def || 'คำอธิบายสมรรถนะ...')}" placeholder="คำอธิบายสมรรถนะ...">
        </td>
        <td>
            <select class="form-select form-select-sm border-0 bg-transparent comp-type-input">
                <option value="core" ${type === 'core' ? 'selected' : ''}>สมรรถนะหลัก</option>
                <option value="functional" ${type === 'functional' ? 'selected' : ''}>สมรรถนะสายงาน</option>
            </select>
        </td>
        <td class="text-center">
            <select class="form-select form-select-sm border-0 bg-transparent comp-level-input text-center fw-bold text-primary">
                <option value="1" ${level == 1 ? 'selected' : ''}>ระดับ 1</option>
                <option value="2" ${level == 2 ? 'selected' : ''}>ระดับ 2</option>
                <option value="3" ${level == 3 ? 'selected' : ''}>ระดับ 3</option>
                <option value="4" ${level == 4 ? 'selected' : ''}>ระดับ 4</option>
                <option value="5" ${level == 5 ? 'selected' : ''}>ระดับ 5</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0" onclick="removeRow(this)" title="ลบแถวนี้">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function openCompetencyPoolModal() {
    const container = document.getElementById('comp-pool-body');
    container.innerHTML = '<div class="text-center py-4 text-muted">กำลังโหลดคลังสมรรถนะ...</div>';
    
    var modal = new bootstrap.Modal(document.getElementById('compPoolModal'));
    modal.show();

    fetch(COMP_POOL_URL)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.pool) {
                let html = '<h6 class="fw-bold text-primary mb-3"><i class="bi bi-star-fill me-1"></i> สมรรถนะหลัก (Core Competencies)</h6><div class="row g-2 mb-4">';
                res.pool.core.forEach(function(c) {
                    html += '<div class="col-12"><div class="card p-2 border bg-light d-flex flex-row justify-content-between align-items-center"><div><strong>' + c.name_th + '</strong><p class="small text-muted mb-0">' + c.definition + '</p></div><button type="button" class="btn btn-sm btn-primary ms-3 text-nowrap" onclick="addCompFromPool(\'' + escapeHtml(c.name_th) + '\', \'core\', \'' + escapeHtml(c.definition) + '\', ' + c.default_level + ')"><i class="bi bi-plus-circle me-1"></i> ดึงเข้าตาราง</button></div></div>';
                });
                html += '</div><h6 class="fw-bold text-info mb-3"><i class="bi bi-gear-fill me-1"></i> สมรรถนะประจำสายงาน (Functional Competencies)</h6><div class="row g-2">';
                res.pool.functional.forEach(function(c) {
                    html += '<div class="col-12"><div class="card p-2 border bg-light d-flex flex-row justify-content-between align-items-center"><div><strong>' + c.name_th + '</strong><p class="small text-muted mb-0">' + c.definition + '</p></div><button type="button" class="btn btn-sm btn-info text-white ms-3 text-nowrap" onclick="addCompFromPool(\'' + escapeHtml(c.name_th) + '\', \'functional\', \'' + escapeHtml(c.definition) + '\', ' + c.default_level + ')"><i class="bi bi-plus-circle me-1"></i> ดึงเข้าตาราง</button></div></div>';
                });
                html += '</div>';
                container.innerHTML = html;
            }
        });
}

function addCompFromPool(name, type, def, level) {
    addCompRow(name, def, type, level);
    alert('ดึงสมรรถนะ "' + name + '" เข้าตารางเรียบร้อยแล้ว');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function saveEntireGrid() {
    const payload = {
        template_name: document.getElementById('template-title-input').value,
        sections: [],
        competencies: []
    };

    // Gather sections and items
    document.querySelectorAll('.section-block').forEach(function(secEl) {
        const secId = secEl.getAttribute('data-section-id');
        const secType = secEl.getAttribute('data-section-type');
        const secName = secEl.querySelector('.section-name-input').value;
        const secWeight = parseFloat(secEl.querySelector('.section-weight-input').value) || 0;

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
                const itemScoreVal = parseFloat(row.querySelector('.item-weight-input').value) || 0;
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

    // Gather competencies
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

    // Send payload
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
        if (res.success) {
            alert('💾 บันทึกข้อมูลแบบประเมินทั้งหมดเรียบร้อยแล้ว!');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (res.message || 'บันทึกไม่สำเร็จ'));
        }
    })
    .catch(err => {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    });
}
</script>
