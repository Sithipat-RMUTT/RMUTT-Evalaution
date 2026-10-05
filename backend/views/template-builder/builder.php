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
?>

<style>
/* Modern Form Builder Styling — Aligned with Self-Assessment Form */
.sticky-control-bar {
    position: sticky;
    top: 60px;
    z-index: 1020;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
    border-bottom: 2px solid #0d6efd;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.section-header-banner {
    background: linear-gradient(135deg, #0d3b66 0%, #0056b3 100%);
    color: #ffffff;
    border-radius: 10px 10px 0 0;
    padding: 14px 20px;
}

.kpi-item-card {
    background: #ffffff;
    border: 1px solid #dbe2ea;
    border-radius: 10px;
    margin-bottom: 18px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    transition: all 0.2s ease-in-out;
}

.kpi-item-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.08);
}

.kpi-card-header {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 18px;
    border-radius: 9px 9px 0 0;
}

.criteria-level-box {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    transition: background-color 0.15s ease;
}

.criteria-level-box:focus-within {
    background-color: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

.level-badge {
    min-width: 110px;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 6px;
    text-align: center;
}

.table-view-container {
    display: none;
}

.mode-form-active .form-view-container { display: block; }
.mode-form-active .table-view-container { display: none; }
.mode-table-active .form-view-container { display: none; }
.mode-table-active .table-view-container { display: block; }

.excel-input {
    border: 1px solid transparent;
    border-radius: 4px;
    padding: 5px 8px;
    font-size: 0.88rem;
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
</style>

<div class="template-builder-view py-3 mode-form-active" id="builder-main-wrapper">

    <!-- 1. Top Sticky Control Bar -->
    <div class="card shadow-sm mb-4 sticky-control-bar border-0 rounded-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                
                <!-- Left: Navigation & Template Title -->
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <?= Html::a('<i class="bi bi-arrow-left me-1"></i> กลับ', ['index'], [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'title' => 'กลับสู่หน้ารายการแบบประเมิน'
                    ]) ?>
                    
                    <div class="flex-grow-1" style="max-width: 520px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-pencil-square text-primary"></i></span>
                            <input type="text" id="template-title-input" class="form-control form-control-sm fw-bold border-0 bg-transparent fs-6" 
                                   value="<?= Html::encode($template->name_th) ?>" placeholder="ชื่อแบบประเมินผลการปฏิบัติงาน...">
                        </div>
                    </div>

                    <?php if ($template->department): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5">
                            🏢 <?= Html::encode($template->department->name_th) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5">
                            ⭐ แม่แบบมาตรฐานกลาง (มหาวิทยาลัย)
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-light text-dark border px-2.5 py-1.5 font-monospace">
                        <?= Html::encode($template->personnelType ? $template->personnelType->name_th : '') ?>
                    </span>
                </div>

                <!-- Right: Weight Monitor & Master Actions -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    
                    <!-- Real-time Weight Badge -->
                    <div class="d-flex align-items-center px-3 py-1.5 rounded-3 bg-light border">
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

                    <!-- View Switcher -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="btn-view-form" onclick="switchViewMode('form')">
                            <i class="bi bi-card-text me-1"></i> แบบฟอร์ม
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btn-view-table" onclick="switchViewMode('table')">
                            <i class="bi bi-table me-1"></i> ตาราง
                        </button>
                    </div>

                    <!-- Live Preview Button -->
                    <?= Html::a('<i class="bi bi-eye-fill me-1"></i> ดูตัวอย่างฟอร์มจริง', ['preview', 'id' => $template->id], [
                        'class' => 'btn btn-sm btn-outline-info text-dark shadow-sm',
                        'target' => '_blank',
                        'title' => 'ดูตัวอย่างแบบฟอร์มเสมือนจริงที่บุคลากรจะใช้ประเมิน'
                    ]) ?>

                    <!-- Master Save Button -->
                    <button type="button" class="btn btn-sm btn-success px-3.5 fw-bold shadow-sm" id="btn-save-top" onclick="saveEntireGrid()">
                        <i class="bi bi-floppy-fill me-1"></i> บันทึกข้อมูลแบบประเมิน
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- 2. Official Form Header Guide Banner -->
    <div class="card card-rmutt shadow-sm mb-4 border bg-light">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0 d-none d-md-block">
                    <img src="<?= Yii::getAlias('@web/images/rmutt-logo.png') ?>" alt="RMUTT Seal" style="height: 60px; width: auto;">
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="bi bi-journal-check text-primary me-2"></i>จัดการเกณฑ์และตัวชี้วัดแบบประเมินผลการปฏิบัติงาน
                    </h5>
                    <p class="text-muted small mb-0">
                        ปรับแต่งตัวชี้วัดภาระงาน (KPI) ค่าน้ำหนัก และเกณฑ์การประเมิน ๕ ระดับ (PDCA) ให้ตรงกับข้อตกลงการปฏิบัติงานของหน่วยงาน 
                        หน้าจอนี้ถูกออกแบบให้สอดคล้องกับแบบฟอร์มตอนประเมินตนเองจริง เพื่อให้ผู้ดูแลระบบแก้ไขได้ง่ายและไม่สับสน
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Sections Container -->
    <div id="grid-sections-container">
        <?php foreach ($sections as $sIdx => $section): ?>
            <?php if ($section->section_type === 'competency'): ?>
                
                <!-- ========================================================================= -->
                <!-- SECTION: COMPETENCIES (ขีดความสามารถ / สมรรถนะ)                           -->
                <!-- ========================================================================= -->
                <div class="card card-rmutt shadow-sm mb-4 border-0 section-block" data-section-id="<?= $section->id ?>" data-section-type="competency">
                    
                    <!-- Section Header -->
                    <div class="section-header-banner d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="badge bg-warning text-dark fs-6 px-2.5 py-1.5">ส่วนที่ <?= $sIdx + 1 ?></span>
                            <div class="flex-grow-1" style="max-width: 550px;">
                                <input type="text" class="form-control form-control-sm fw-bold border-0 bg-transparent text-white fs-6 section-name-input" 
                                       value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดสมรรถนะ...">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 170px;">
                                <span class="input-group-text bg-white fw-medium">ค่าน้ำหนักหมวด</span>
                                <input type="number" step="0.5" class="form-control text-end fw-bold text-primary section-weight-input" 
                                       value="<?= floatval($section->weight) ?>" onchange="recalculateTotalWeight()">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section Content: Official Competency Table -->
                    <div class="card-body p-3 p-md-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-award-fill text-warning me-1"></i> รายการสมรรถนะและพฤติกรรมบ่งชี้ (Competencies)
                                </h6>
                                <small class="text-muted">กำหนดสมรรถนะหลักและสมรรถนะสายงาน พร้อมระดับความสามารถที่คาดหวัง</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCompRow()">
                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มสมรรถนะใหม่
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" onclick="openCompetencyPoolModal()">
                                    <i class="bi bi-bookmark-star-fill me-1"></i> เลือกจากคลังสมรรถนะ มทร.ธัญบุรี
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th style="min-width: 240px;" class="text-start">หัวข้อสมรรถนะ</th>
                                        <th style="min-width: 320px;" class="text-start">คำนิยาม / พฤติกรรมบ่งชี้</th>
                                        <th style="width: 160px;">ประเภท</th>
                                        <th style="width: 150px;">ระดับที่คาดหวัง</th>
                                        <th style="width: 50px;">ลบ</th>
                                    </tr>
                                </thead>
                                <tbody id="comp-table-body">
                                    <?php foreach ($competencies as $cIdx => $comp): ?>
                                        <tr class="comp-row" data-comp-id="<?= $comp->id ?>">
                                            <td class="text-center fw-bold text-muted comp-row-num"><?= $cIdx + 1 ?></td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm fw-bold comp-name-input border-0 bg-light" 
                                                       value="<?= Html::encode($comp->name_th) ?>" placeholder="ชื่อสมรรถนะ...">
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm comp-def-input border-0 bg-light" rows="2" 
                                                          placeholder="คำอธิบาย / พฤติกรรมบ่งชี้..."><?= Html::encode($comp->definition) ?></textarea>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm comp-type-input">
                                                    <option value="core" <?= $comp->competency_type === 'core' ? 'selected' : '' ?>>สมรรถนะหลัก (Core)</option>
                                                    <option value="functional" <?= $comp->competency_type === 'functional' ? 'selected' : '' ?>>สมรรถนะสายงาน (Functional)</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <select class="form-select form-select-sm comp-level-input text-center fw-bold text-primary">
                                                    <?php 
                                                    $lvlNames = [
                                                        1 => 'ระดับ ๑ (พื้นฐาน)',
                                                        2 => 'ระดับ ๒ (ประยุกต์ใช้)',
                                                        3 => 'ระดับ ๓ (ความสามารถ)',
                                                        4 => 'ระดับ ๔ (ชำนาญ)',
                                                        5 => 'ระดับ ๕ (เชี่ยวชาญ)',
                                                    ];
                                                    for ($lvl = 1; $lvl <= 5; $lvl++): ?>
                                                        <option value="<?= $lvl ?>" <?= $comp->expected_level == $lvl ? 'selected' : '' ?>>
                                                            <?= $lvlNames[$lvl] ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removeRow(this)" title="ลบสมรรถนะนี้">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            <?php else: ?>

                <!-- ========================================================================= -->
                <!-- SECTION: PERFORMANCE / KPI (ผลสัมฤทธิ์ของงาน / ตัวชี้วัด)                 -->
                <!-- ========================================================================= -->
                <div class="card card-rmutt shadow-sm mb-4 border-0 section-block" data-section-id="<?= $section->id ?>" data-section-type="<?= Html::encode($section->section_type) ?>">
                    
                    <!-- Section Header -->
                    <div class="section-header-banner d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="badge bg-light text-primary fs-6 px-2.5 py-1.5">ส่วนที่ <?= $sIdx + 1 ?></span>
                            <div class="flex-grow-1" style="max-width: 550px;">
                                <input type="text" class="form-control form-control-sm fw-bold border-0 bg-transparent text-white fs-6 section-name-input" 
                                       value="<?= Html::encode($section->name_th) ?>" placeholder="ชื่อหมวดผลสัมฤทธิ์...">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 170px;">
                                <span class="input-group-text bg-white fw-medium">ค่าน้ำหนักหมวด</span>
                                <input type="number" step="0.5" class="form-control text-end fw-bold text-primary section-weight-input" 
                                       value="<?= floatval($section->weight) ?>" onchange="recalculateTotalWeight()">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section Content: Form Cards & Table Views -->
                    <div class="card-body p-3 p-md-4 bg-white">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-list-check text-primary me-1"></i> รายการตัวชี้วัดภาระงาน (KPI Items)
                                </h6>
                                <small class="text-muted">กำหนดชื่อตัวชี้วัด ค่าน้ำหนัก และเกณฑ์ค่าเป้าหมาย ๕ ระดับตามแนวทาง PDCA</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="addKpiCard(<?= $section->id ?>)">
                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มตัวชี้วัด (KPI) ใหม่
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillStandardPdca(<?= $section->id ?>)">
                                    <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> เติมเกณฑ์ PDCA ทุกข้อ
                                </button>
                            </div>
                        </div>

                        <!-- ----------------------------------------------------------------- -->
                        <!-- MODE 1: FORM VIEW (เหมือนแบบฟอร์มประเมินตนเอง — เข้าใจง่าย ไม่งง)  -->
                        <!-- ----------------------------------------------------------------- -->
                        <div class="form-view-container" id="kpi-form-cards-<?= $section->id ?>">
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
                                <div class="kpi-item-card kpi-row" data-item-id="<?= $item->id ?>">
                                    
                                    <!-- Card Header: Title & Badges & Controls -->
                                    <div class="kpi-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 280px;">
                                            <span class="badge bg-primary fs-6 px-2.5 py-1 kpi-row-num"><?= $iIdx + 1 ?></span>
                                            <div class="flex-grow-1">
                                                <input type="text" class="form-control form-control-sm fw-bold border-0 bg-transparent fs-6 item-name-input" 
                                                       value="<?= Html::encode($item->name_th) ?>" placeholder="ระบุชื่อภาระงาน / กิจกรรม / ตัวชี้วัด...">
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <!-- Weight / Score Input -->
                                            <div class="input-group input-group-sm" style="width: 140px;">
                                                <span class="input-group-text bg-white">น้ำหนัก</span>
                                                <input type="number" step="0.5" class="form-control text-end fw-bold text-primary item-weight-input" 
                                                       value="<?= $displayScore ?>" placeholder="0" title="ค่าน้ำหนัก % หรือคะแนนเต็มของข้อนี้">
                                                <span class="input-group-text bg-white">%</span>
                                            </div>

                                            <!-- Evaluation Type -->
                                            <select class="form-select form-select-sm item-type-select" style="width: 165px;" onchange="toggleItemTypeCardUI(this)">
                                                <option value="pdca_level" <?= $inputType === 'pdca_level' ? 'selected' : '' ?>>📊 PDCA ๕ ระดับ</option>
                                                <option value="score_direct" <?= $inputType === 'score_direct' ? 'selected' : '' ?>>🔢 คะแนนเต็มโดยตรง</option>
                                                <option value="checkbox_list" <?= $inputType === 'checkbox_list' ? 'selected' : '' ?>>☑️ เช็คลิสต์ ๑๐ ข้อ</option>
                                            </select>

                                            <!-- Evidence Checkbox -->
                                            <div class="form-check form-check-inline mb-0 bg-white border px-2.5 py-1 rounded">
                                                <input class="form-check-input item-ev-input" type="checkbox" id="ev_check_<?= $item->id ?>" <?= $item->requires_evidence ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="ev_check_<?= $item->id ?>">
                                                    <i class="bi bi-paperclip text-muted"></i> แนบไฟล์
                                                </label>
                                            </div>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1.5" onclick="removeKpiCard(this)" title="ลบตัวชี้วัดนี้">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Card Body: Criteria Levels 1-5 (PDCA) -->
                                    <div class="p-3 bg-white card-crit-body">
                                        <?php if ($inputType === 'score_direct'): ?>
                                            <div class="alert alert-light border text-center py-2 mb-0 crit-direct-notice">
                                                <i class="bi bi-info-circle me-1 text-primary"></i> 
                                                รูปแบบให้คะแนนโดยตรง (ผู้รับการประเมินและผู้ประเมินกรอกคะแนนได้ตั้งแต่ 0 ถึง <span class="fw-bold text-primary"><?= $displayScore ?></span> คะแนน)
                                            </div>
                                        <?php elseif ($inputType === 'checkbox_list'): ?>
                                            <div class="alert alert-light border text-center py-2 mb-0 crit-direct-notice">
                                                <i class="bi bi-check2-square me-1 text-success"></i> 
                                                รูปแบบเช็คลิสต์ภาระงานรอง ๑๐ ข้อ (เลือกข้อที่ปฏิบัติ คำนวณเป็นระดับคะแนน ๑-๕ โดยอัตโนมัติ)
                                            </div>
                                        <?php else: ?>
                                            <div class="crit-pdca-container">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small fw-bold text-muted">
                                                        <i class="bi bi-sliders me-1"></i> เกณฑ์ค่าเป้าหมายการประเมิน ๕ ระดับ (PDCA Criteria):
                                                    </span>
                                                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 small" onclick="fillCardPdca(this)">
                                                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> เติมเกณฑ์มาตรฐานให้ข้อนี้
                                                    </button>
                                                </div>

                                                <div class="row g-2">
                                                    <!-- Level 1 (Plan) -->
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                                                            <span class="badge bg-secondary text-white level-badge">ระดับ ๑ (Plan)</span>
                                                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-1" 
                                                                   value="<?= Html::encode($critMap[1] ?? '') ?>" placeholder="ระบุเกณฑ์ความสำเร็จระดับ ๑: มีการวางแผนและกำหนดขั้นตอนชัดเจน...">
                                                        </div>
                                                    </div>

                                                    <!-- Level 2 (Do) -->
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                                                            <span class="badge bg-info text-dark level-badge">ระดับ ๒ (Do)</span>
                                                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-2" 
                                                                   value="<?= Html::encode($critMap[2] ?? '') ?>" placeholder="ระบุเกณฑ์ความสำเร็จระดับ ๒: ดำเนินการปฏิบัติตามแผนงานได้ตามระยะเวลา...">
                                                        </div>
                                                    </div>

                                                    <!-- Level 3 (Check) -->
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                                                            <span class="badge bg-primary text-white level-badge">ระดับ ๓ (Check)</span>
                                                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-3" 
                                                                   value="<?= Html::encode($critMap[3] ?? '') ?>" placeholder="ระบุเกณฑ์ความสำเร็จระดับ ๓: มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน...">
                                                        </div>
                                                    </div>

                                                    <!-- Level 4 (Act) -->
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                                                            <span class="badge bg-warning text-dark level-badge">ระดับ ๔ (Act)</span>
                                                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-4" 
                                                                   value="<?= Html::encode($critMap[4] ?? '') ?>" placeholder="ระบุเกณฑ์ความสำเร็จระดับ ๔: ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ...">
                                                        </div>
                                                    </div>

                                                    <!-- Level 5 (Impact) -->
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                                                            <span class="badge bg-success text-white level-badge">ระดับ ๕ (Impact)</span>
                                                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-5" 
                                                                   value="<?= Html::encode($critMap[5] ?? '') ?>" placeholder="ระบุเกณฑ์ความสำเร็จระดับ ๕: ผลงานเกิดประโยชน์เชิงประจักษ์ ดีเยี่ยม...">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- ----------------------------------------------------------------- -->
                        <!-- MODE 2: TABLE VIEW (โหมดตารางสรุป สำหรับผู้ต้องการดูภาพรวม)           -->
                        <!-- ----------------------------------------------------------------- -->
                        <div class="table-view-container" id="kpi-table-view-<?= $section->id ?>">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light text-center small">
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th style="min-width: 220px;">ชื่อภาระงาน / ตัวชี้วัด</th>
                                            <th style="width: 140px;">รูปแบบ</th>
                                            <th style="width: 90px;">น้ำหนัก (%)</th>
                                            <th>เกณฑ์ ๑ (Plan)</th>
                                            <th>เกณฑ์ ๒ (Do)</th>
                                            <th>เกณฑ์ ๓ (Check)</th>
                                            <th>เกณฑ์ ๔ (Act)</th>
                                            <th>เกณฑ์ ๕ (Impact)</th>
                                            <th style="width: 50px;">แนบ</th>
                                            <th style="width: 45px;">ลบ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="kpi-table-body-<?= $section->id ?>">
                                        <!-- Synchronized dynamically via script -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Section Add Button -->
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="addKpiCard(<?= $section->id ?>)">
                                <i class="bi bi-plus-circle me-1"></i> เพิ่มตัวชี้วัด (KPI) ในหมวดนี้
                            </button>
                        </div>

                    </div>
                </div>

            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- 4. Bottom Master Save Card -->
    <div class="card p-4 shadow-sm bg-light border-0 text-center mb-5 rounded-3">
        <div>
            <button type="button" class="btn btn-success btn-lg px-5 shadow fw-bold" id="btn-save-bottom" onclick="saveEntireGrid()">
                <i class="bi bi-floppy-fill me-2"></i> บันทึกข้อมูลแบบประเมินทั้งหมด (Save Changes)
            </button>
            <div class="text-muted small mt-2">
                เมื่อปรับแต่งตัวชี้วัด ค่าน้ำหนัก และเกณฑ์เรียบร้อยแล้ว กดปุ่มนี้เพื่อบันทึกข้อมูลทุกหมวดในครั้งเดียว
            </div>
        </div>
    </div>

</div>

<!-- Modal: RMUTT Standard Competency Pool -->
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

// View Mode Switching (Form View vs Table View)
function switchViewMode(mode) {
    const wrapper = document.getElementById('builder-main-wrapper');
    const btnForm = document.getElementById('btn-view-form');
    const btnTable = document.getElementById('btn-view-table');

    if (mode === 'table') {
        wrapper.classList.remove('mode-form-active');
        wrapper.classList.add('mode-table-active');
        btnForm.classList.remove('active');
        btnTable.classList.add('active');
        syncCardsToTables();
    } else {
        wrapper.classList.remove('mode-table-active');
        wrapper.classList.add('mode-form-active');
        btnTable.classList.remove('active');
        btnForm.classList.add('active');
    }
}

// Recalculate total section weights
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

// Add KPI Card in Section
function addKpiCard(sectionId) {
    const container = document.getElementById('kpi-form-cards-' + sectionId);
    if (!container) return;

    const count = container.querySelectorAll('.kpi-item-card').length + 1;
    const card = document.createElement('div');
    card.className = 'kpi-item-card kpi-row';
    card.setAttribute('data-item-id', '');
    card.innerHTML = `
        <div class="kpi-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 280px;">
                <span class="badge bg-primary fs-6 px-2.5 py-1 kpi-row-num">${count}</span>
                <div class="flex-grow-1">
                    <input type="text" class="form-control form-control-sm fw-bold border-0 bg-transparent fs-6 item-name-input" 
                           value="ตัวชี้วัดที่ ${count}" placeholder="ระบุชื่อภาระงาน / กิจกรรม / ตัวชี้วัด...">
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-group input-group-sm" style="width: 140px;">
                    <span class="input-group-text bg-white">น้ำหนัก</span>
                    <input type="number" step="0.5" class="form-control text-end fw-bold text-primary item-weight-input" 
                           value="20" placeholder="0" title="ค่าน้ำหนัก % หรือคะแนนเต็มของข้อนี้">
                    <span class="input-group-text bg-white">%</span>
                </div>

                <select class="form-select form-select-sm item-type-select" style="width: 165px;" onchange="toggleItemTypeCardUI(this)">
                    <option value="pdca_level" selected>📊 PDCA ๕ ระดับ</option>
                    <option value="score_direct">🔢 คะแนนเต็มโดยตรง</option>
                    <option value="checkbox_list">☑️ เช็คลิสต์ ๑๐ ข้อ</option>
                </select>

                <div class="form-check form-check-inline mb-0 bg-white border px-2.5 py-1 rounded">
                    <input class="form-check-input item-ev-input" type="checkbox" checked>
                    <label class="form-check-label small">
                        <i class="bi bi-paperclip text-muted"></i> แนบไฟล์
                    </label>
                </div>

                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1.5" onclick="removeKpiCard(this)" title="ลบตัวชี้วัดนี้">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </div>
        </div>

        <div class="p-3 bg-white card-crit-body">
            <div class="crit-pdca-container">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-muted">
                        <i class="bi bi-sliders me-1"></i> เกณฑ์ค่าเป้าหมายการประเมิน ๕ ระดับ (PDCA Criteria):
                    </span>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 small" onclick="fillCardPdca(this)">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> เติมเกณฑ์มาตรฐานให้ข้อนี้
                    </button>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                            <span class="badge bg-secondary text-white level-badge">ระดับ ๑ (Plan)</span>
                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-1" 
                                   value="มีการวางแผนและกำหนดขั้นตอนการดำเนินงานชัดเจน" placeholder="ระดับ ๑ (Plan)...">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                            <span class="badge bg-info text-dark level-badge">ระดับ ๒ (Do)</span>
                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-2" 
                                   value="ดำเนินการปฏิบัติตามแผนงานที่กำหนดได้ตามระยะเวลา" placeholder="ระดับ ๒ (Do)...">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                            <span class="badge bg-primary text-white level-badge">ระดับ ๓ (Check)</span>
                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-3" 
                                   value="มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน" placeholder="ระดับ ๓ (Check)...">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                            <span class="badge bg-warning text-dark level-badge">ระดับ ๔ (Act)</span>
                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-4" 
                                   value="ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ" placeholder="ระดับ ๔ (Act)...">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 criteria-level-box">
                            <span class="badge bg-success text-white level-badge">ระดับ ๕ (Impact)</span>
                            <input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-5" 
                                   value="ผลงานเกิดประโยชน์เชิงประจักษ์ และสร้างผลกระทบเชิงบวก" placeholder="ระดับ ๕ (Impact)...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.appendChild(card);
}

// Remove KPI Card
function removeKpiCard(btn) {
    const card = btn.closest('.kpi-item-card');
    const container = card.closest('.form-view-container');
    card.remove();
    if (container) {
        container.querySelectorAll('.kpi-item-card').forEach(function(c, idx) {
            const numEl = c.querySelector('.kpi-row-num');
            if (numEl) numEl.textContent = idx + 1;
        });
    }
}

// Toggle Item Type in Card UI
function toggleItemTypeCardUI(select) {
    const card = select.closest('.kpi-item-card');
    const body = card.querySelector('.card-crit-body');
    const val = select.value;

    if (val === 'score_direct') {
        body.innerHTML = `
            <div class="alert alert-light border text-center py-2 mb-0 crit-direct-notice">
                <i class="bi bi-info-circle me-1 text-primary"></i> 
                รูปแบบให้คะแนนโดยตรง (ผู้รับการประเมินและผู้ประเมินกรอกคะแนนตามคะแนนเต็ม)
            </div>
            <input type="hidden" class="crit-input-1" value="">
            <input type="hidden" class="crit-input-2" value="">
            <input type="hidden" class="crit-input-3" value="">
            <input type="hidden" class="crit-input-4" value="">
            <input type="hidden" class="crit-input-5" value="">
        `;
    } else if (val === 'checkbox_list') {
        body.innerHTML = `
            <div class="alert alert-light border text-center py-2 mb-0 crit-direct-notice">
                <i class="bi bi-check2-square me-1 text-success"></i> 
                รูปแบบเช็คลิสต์ภาระงานรอง ๑๐ ข้อ (เลือกข้อที่ปฏิบัติ คำนวณคะแนน ๑-๕ อัตโนมัติ)
            </div>
            <input type="hidden" class="crit-input-1" value="">
            <input type="hidden" class="crit-input-2" value="">
            <input type="hidden" class="crit-input-3" value="">
            <input type="hidden" class="crit-input-4" value="">
            <input type="hidden" class="crit-input-5" value="">
        `;
    } else {
        body.innerHTML = `
            <div class="crit-pdca-container">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-muted">
                        <i class="bi bi-sliders me-1"></i> เกณฑ์ค่าเป้าหมายการประเมิน ๕ ระดับ (PDCA Criteria):
                    </span>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 small" onclick="fillCardPdca(this)">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> เติมเกณฑ์มาตรฐานให้ข้อนี้
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-12"><div class="d-flex align-items-center gap-2 criteria-level-box"><span class="badge bg-secondary text-white level-badge">ระดับ ๑ (Plan)</span><input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-1" value="มีการวางแผนและกำหนดขั้นตอนการดำเนินงานชัดเจน" placeholder="ระดับ ๑ (Plan)..."></div></div>
                    <div class="col-12"><div class="d-flex align-items-center gap-2 criteria-level-box"><span class="badge bg-info text-dark level-badge">ระดับ ๒ (Do)</span><input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-2" value="ดำเนินการปฏิบัติตามแผนงานที่กำหนดได้ตามระยะเวลา" placeholder="ระดับ ๒ (Do)..."></div></div>
                    <div class="col-12"><div class="d-flex align-items-center gap-2 criteria-level-box"><span class="badge bg-primary text-white level-badge">ระดับ ๓ (Check)</span><input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-3" value="มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน" placeholder="ระดับ ๓ (Check)..."></div></div>
                    <div class="col-12"><div class="d-flex align-items-center gap-2 criteria-level-box"><span class="badge bg-warning text-dark level-badge">ระดับ ๔ (Act)</span><input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-4" value="ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ" placeholder="ระดับ ๔ (Act)..."></div></div>
                    <div class="col-12"><div class="d-flex align-items-center gap-2 criteria-level-box"><span class="badge bg-success text-white level-badge">ระดับ ๕ (Impact)</span><input type="text" class="form-control form-control-sm border-0 bg-transparent crit-input-5" value="ผลงานเกิดประโยชน์เชิงประจักษ์ และสร้างผลกระทบเชิงบวก" placeholder="ระดับ ๕ (Impact)..."></div></div>
                </div>
            </div>
        `;
    }
}

// Fill standard PDCA for single card
function fillCardPdca(btn) {
    const card = btn.closest('.kpi-item-card');
    const inp1 = card.querySelector('.crit-input-1');
    const inp2 = card.querySelector('.crit-input-2');
    const inp3 = card.querySelector('.crit-input-3');
    const inp4 = card.querySelector('.crit-input-4');
    const inp5 = card.querySelector('.crit-input-5');
    if (inp1) inp1.value = 'มีการวางแผนและกำหนดขั้นตอนการดำเนินงานชัดเจน';
    if (inp2) inp2.value = 'ดำเนินการปฏิบัติตามแผนงานที่กำหนดได้ตามระยะเวลา';
    if (inp3) inp3.value = 'มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน';
    if (inp4) inp4.value = 'ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ';
    if (inp5) inp5.value = 'ผลงานเกิดประโยชน์เชิงประจักษ์ และสร้างผลกระทบเชิงบวก';
}

// Fill standard PDCA for all cards in section
function fillStandardPdca(sectionId) {
    const container = document.getElementById('kpi-form-cards-' + sectionId);
    if (!container) return;
    container.querySelectorAll('.kpi-item-card').forEach(function(card) {
        const inp1 = card.querySelector('.crit-input-1');
        const inp2 = card.querySelector('.crit-input-2');
        const inp3 = card.querySelector('.crit-input-3');
        const inp4 = card.querySelector('.crit-input-4');
        const inp5 = card.querySelector('.crit-input-5');
        if (inp1 && !inp1.value) inp1.value = 'มีการวางแผนและกำหนดขั้นตอนการดำเนินงานชัดเจน';
        if (inp2 && !inp2.value) inp2.value = 'ดำเนินการปฏิบัติตามแผนงานที่กำหนดได้ตามระยะเวลา';
        if (inp3 && !inp3.value) inp3.value = 'มีการตรวจสอบ ทดสอบ และประเมินผลการปฏิบัติงาน';
        if (inp4 && !inp4.value) inp4.value = 'ปรับปรุง แก้ไขปัญหา และส่งมอบงานอย่างมีคุณภาพ';
        if (inp5 && !inp5.value) inp5.value = 'ผลงานเกิดประโยชน์เชิงประจักษ์ และสร้างผลกระทบเชิงบวก';
    });
    alert('เติมข้อความเกณฑ์ PDCA ๑-๕ สำเร็จรูปเรียบร้อยแล้ว อย่าลืมกดปุ่ม "บันทึกข้อมูล"');
}

// Sync Cards to Table View when switching
function syncCardsToTables() {
    document.querySelectorAll('.section-block').forEach(function(secEl) {
        const secId = secEl.getAttribute('data-section-id');
        const secType = secEl.getAttribute('data-section-type');
        if (secType === 'competency') return;

        const cards = secEl.querySelectorAll('.kpi-item-card');
        const tbody = document.getElementById('kpi-table-body-' + secId);
        if (!tbody) return;

        tbody.innerHTML = '';
        cards.forEach(function(card, idx) {
            const name = card.querySelector('.item-name-input').value;
            const weight = card.querySelector('.item-weight-input').value;
            const type = card.querySelector('.item-type-select').value;
            const ev = card.querySelector('.item-ev-input').checked;
            const c1 = card.querySelector('.crit-input-1') ? card.querySelector('.crit-input-1').value : '';
            const c2 = card.querySelector('.crit-input-2') ? card.querySelector('.crit-input-2').value : '';
            const c3 = card.querySelector('.crit-input-3') ? card.querySelector('.crit-input-3').value : '';
            const c4 = card.querySelector('.crit-input-4') ? card.querySelector('.crit-input-4').value : '';
            const c5 = card.querySelector('.crit-input-5') ? card.querySelector('.crit-input-5').value : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center fw-bold">${idx + 1}</td>
                <td class="fw-medium">${escapeHtml(name)}</td>
                <td class="text-center small">${type === 'pdca_level' ? 'PDCA 5 ระดับ' : (type === 'score_direct' ? 'คะแนนตรง' : 'เช็คลิสต์')}</td>
                <td class="text-center fw-bold text-primary">${weight}%</td>
                <td class="small text-muted">${escapeHtml(c1)}</td>
                <td class="small text-muted">${escapeHtml(c2)}</td>
                <td class="small text-muted">${escapeHtml(c3)}</td>
                <td class="small text-muted">${escapeHtml(c4)}</td>
                <td class="small text-muted">${escapeHtml(c5)}</td>
                <td class="text-center">${ev ? '<i class="bi bi-check-circle-fill text-success"></i>' : '-'}</td>
                <td class="text-center"><span class="badge bg-light text-secondary border">แก้ไขในฟอร์ม</span></td>
            `;
            tbody.appendChild(tr);
        });
    });
}

// Competency Table Functions
function removeRow(btn) {
    const tr = btn.closest('tr');
    const tbody = tr.closest('tbody');
    tr.remove();
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(function(r, idx) {
            const numCell = r.querySelector('.comp-row-num');
            if (numCell) numCell.textContent = idx + 1;
        });
    }
}

function addCompRow(name = '', def = '', type = 'core', level = 3) {
    const tbody = document.getElementById('comp-table-body');
    const rowCount = tbody.querySelectorAll('tr').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'comp-row';
    tr.setAttribute('data-comp-id', '');
    tr.innerHTML = `
        <td class="text-center fw-bold text-muted comp-row-num">${rowCount}</td>
        <td>
            <input type="text" class="form-control form-control-sm fw-bold comp-name-input border-0 bg-light" 
                   value="${escapeHtml(name || ('สมรรถนะที่ ' + rowCount))}" placeholder="ชื่อสมรรถนะ...">
        </td>
        <td>
            <textarea class="form-control form-control-sm comp-def-input border-0 bg-light" rows="2" 
                      placeholder="คำอธิบาย / พฤติกรรมบ่งชี้...">${escapeHtml(def || 'คำอธิบายสมรรถนะ...')}</textarea>
        </td>
        <td>
            <select class="form-select form-select-sm comp-type-input">
                <option value="core" ${type === 'core' ? 'selected' : ''}>สมรรถนะหลัก (Core)</option>
                <option value="functional" ${type === 'functional' ? 'selected' : ''}>สมรรถนะสายงาน (Functional)</option>
            </select>
        </td>
        <td class="text-center">
            <select class="form-select form-select-sm comp-level-input text-center fw-bold text-primary">
                <option value="1" ${level == 1 ? 'selected' : ''}>ระดับ ๑ (พื้นฐาน)</option>
                <option value="2" ${level == 2 ? 'selected' : ''}>ระดับ ๒ (ประยุกต์ใช้)</option>
                <option value="3" ${level == 3 ? 'selected' : ''}>ระดับ ๓ (ความสามารถ)</option>
                <option value="4" ${level == 4 ? 'selected' : ''}>ระดับ ๔ (ชำนาญ)</option>
                <option value="5" ${level == 5 ? 'selected' : ''}>ระดับ ๕ (เชี่ยวชาญ)</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removeRow(this)" title="ลบสมรรถนะนี้">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

// Modal Competency Pool
function openCompetencyPoolModal() {
    const container = document.getElementById('comp-pool-body');
    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2"></div> กำลังโหลดคลังสมรรถนะ...</div>';
    
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
    addCompRow(name, def, type, level);
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
            secEl.querySelectorAll('.kpi-row').forEach(function(card) {
                const typeSelect = card.querySelector('.item-type-select');
                const itemType = typeSelect ? typeSelect.value : 'pdca_level';
                const itemId = card.getAttribute('data-item-id') || '';
                const itemNameInput = card.querySelector('.item-name-input');
                const itemName = itemNameInput ? itemNameInput.value : '';
                const itemScoreVal = parseFloat(card.querySelector('.item-weight-input').value) || 0;
                const reqEv = card.querySelector('.item-ev-input') && card.querySelector('.item-ev-input').checked ? 1 : 0;

                const inp1 = card.querySelector('.crit-input-1');
                const inp2 = card.querySelector('.crit-input-2');
                const inp3 = card.querySelector('.crit-input-3');
                const inp4 = card.querySelector('.crit-input-4');
                const inp5 = card.querySelector('.crit-input-5');

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
