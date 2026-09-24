<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\EvaluationTemplate;

/** @var yii\web\View $this */
/** @var common\models\EvaluationTemplate[] $templates */
/** @var common\models\Department[] $departments */
/** @var common\models\PersonnelType[] $personnelTypes */
/** @var int|null $selectedDepartmentId */
/** @var int|null $selectedPersonnelTypeId */
/** @var array|null $adminCtx */
/** @var common\models\EvaluationCycle|null $activeCycle */
/** @var common\models\Department|null $targetDepartment */
/** @var int|null $targetDeptId */
/** @var array|null $assignedTemplates */

$this->title = 'จัดการแบบประเมินผลการปฏิบัติงาน';
$this->params['breadcrumbs'][] = $this->title;

$isSuper = isset($adminCtx) && $adminCtx['isSuperAdmin'];
$myDept = isset($adminCtx) ? $adminCtx['department'] : null;
$targetDepartment = $targetDepartment ?? null;
$targetDeptId = $targetDeptId ?? null;
$activeCycle = $activeCycle ?? null;
$assignedTemplates = $assignedTemplates ?? [];
$activeDept = $targetDepartment ?: $myDept;

// Determine active tab
$reqTab = Yii::$app->request->get('tab');
$isLibraryTab = ($reqTab === 'library' || $selectedDepartmentId !== null || $selectedPersonnelTypeId !== null);
?>

<div class="template-builder-index py-3">

    <!-- Top Clean Header (No Clutter) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-file-earmark-ruled-fill text-primary me-2"></i>จัดการแบบประเมินผลการปฏิบัติงาน
                </h4>
                <?php if ($activeDept): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 fs-6">
                        <i class="bi bi-building me-1"></i><?= Html::encode($activeDept->name_th) ?>
                    </span>
                <?php endif; ?>
                <?php if ($activeCycle): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fs-6">
                        <i class="bi bi-calendar-check me-1"></i><?= Html::encode($activeCycle->name_th) ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                กำหนดแบบประเมินที่ใช้งานจริงสำหรับแต่ละประเภทบุคลากร และปรับแต่งตัวชี้วัด (KPI) ค่าน้ำหนัก และเกณฑ์คะแนนตามบริบทหน่วยงาน
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if ($isSuper && !empty($departments)): ?>
                <form method="get" action="<?= Url::to(['/template-builder/index']) ?>" class="d-flex align-items-center gap-1.5">
                    <input type="hidden" name="r" value="template-builder/index">
                    <select name="department_id" class="form-select form-select-sm" style="min-width: 220px;" onchange="this.form.submit()">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d->id ?>" <?= $d->id == $targetDeptId ? 'selected' : '' ?>>
                                🏢 <?= Html::encode($d->name_th) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <?= Html::a('<i class="bi bi-plus-lg me-1"></i> สร้างแบบประเมินใหม่', ['/template-builder/create'], [
                'class' => 'btn btn-primary btn-sm px-3 shadow-sm fw-semibold',
            ]) ?>
        </div>
    </div>

    <!-- Navigation Tabs: Clean & Organized -->
    <ul class="nav nav-pills mb-3 gap-2" id="templateTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= !$isLibraryTab ? 'active' : '' ?> px-3 py-2 fw-semibold rounded-pill" 
                    id="tab-active-btn" 
                    data-bs-toggle="pill" 
                    data-bs-target="#tab-active" 
                    type="button" 
                    role="tab">
                <i class="bi bi-check2-circle me-1.5"></i>แบบประเมินที่เปิดใช้งานจริงในรอบนี้
                <span class="badge bg-white text-primary ms-1.5 rounded-pill border"><?= count($assignedTemplates) ?> กลุ่ม</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $isLibraryTab ? 'active' : '' ?> px-3 py-2 fw-semibold rounded-pill" 
                    id="tab-library-btn" 
                    data-bs-toggle="pill" 
                    data-bs-target="#tab-library" 
                    type="button" 
                    role="tab">
                <i class="bi bi-collection me-1.5"></i>คลังแบบประเมินทั้งหมด
                <span class="badge bg-white text-secondary ms-1.5 rounded-pill border"><?= count($templates) ?> แบบ</span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="templateTabsContent">

        <!-- TAB 1: Active Assignments (Primary View for Agency Admin) -->
        <div class="tab-pane fade <?= !$isLibraryTab ? 'show active' : '' ?>" id="tab-active" role="tabpanel">
            <div class="card card-rmutt shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-sliders me-1.5 text-primary"></i>สถานะแบบประเมินที่เปิดใช้งานประจำรอบสำหรับบุคลากรในหน่วยงาน
                        </h6>
                        <small class="text-muted">บุคลากรแต่ละกลุ่มจะได้รับแบบประเมินตามรายการด้านล่างนี้เมื่อเริ่มรอบการประเมิน</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th style="width: 220px;" class="ps-4">กลุ่มประเภทบุคลากร</th>
                                <th>แบบประเมินที่เปิดใช้งานจริง</th>
                                <th style="width: 160px;">แหล่งที่มา</th>
                                <th style="width: 200px;">โครงสร้างคะแนน & ตัวชี้วัด</th>
                                <th class="text-end pe-4" style="width: 260px;">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignedTemplates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-6 d-block mb-2 text-muted"></i>
                                        ไม่พบข้อมูลประเภทบุคลากร
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignedTemplates as $ptId => $assign): 
                                    $pt = $assign['personnelType'];
                                    $curTpl = $assign['currentTemplate'];
                                    $curVer = $assign['currentVersion'];
                                    $isCustom = $assign['isCustom'];
                                    $isInherited = $assign['isInherited'];
                                    $availTpls = $assign['availableTemplates'];

                                    $sections = $curVer ? $curVer->sections : [];
                                    $itemCount = 0;
                                    $weightStr = [];
                                    foreach ($sections as $s) {
                                        $itemCount += count($s->items);
                                        $weightStr[] = number_format($s->weight, 0) . '%';
                                    }
                                    $compCount = $curVer ? count($curVer->competencyDefinitions) : 0;
                                ?>
                                    <tr>
                                        <!-- 1. Personnel Type -->
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark fs-6"><?= Html::encode($pt->name_th) ?></div>
                                            <span class="badge bg-light text-secondary border font-monospace mt-1">
                                                <?= Html::encode($pt->code) ?>
                                            </span>
                                        </td>

                                        <!-- 2. Active Template -->
                                        <td>
                                            <?php if ($curTpl): ?>
                                                <div class="fw-bold text-dark fs-6 mb-1">
                                                    <?= Html::encode($curTpl->name_th) ?>
                                                </div>
                                                <div class="small text-muted d-flex align-items-center gap-2">
                                                    <span>รหัส: <code><?= Html::encode($curTpl->code) ?></code></span>
                                                    <span>•</span>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= $curVer ? Html::encode($curVer->version_label) : 'v1.0' ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-danger fw-semibold">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>ยังไม่ได้กำหนดแบบประเมิน
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 3. Source Origin Badge -->
                                        <td>
                                            <?php if ($isCustom): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-check2-circle me-1"></i>เฉพาะหน่วยงาน
                                                </span>
                                            <?php elseif ($isInherited): ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                                    <i class="bi bi-diagram-2 me-1"></i>สืบทอดจากสังกัดแม่
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border px-2 py-1">
                                                    <i class="bi bi-shield-check me-1"></i>แม่แบบมาตรฐานกลาง
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 4. Weight & KPI Structure -->
                                        <td>
                                            <div class="small fw-semibold text-dark">
                                                สัดส่วน: <?= !empty($weightStr) ? implode(' / ', $weightStr) : '70% / 30%' ?>
                                            </div>
                                            <div class="small text-muted mt-0.5">
                                                <?= $itemCount ?> ตัวชี้วัด • <?= $compCount ?> ด้านสมรรถนะ
                                            </div>
                                        </td>

                                        <!-- 5. Clear Actions -->
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1.5">
                                                <!-- Action 1: Change / Assign Template -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary fw-semibold btn-assign-template" 
                                                        data-dept-id="<?= $activeDept ? $activeDept->id : 0 ?>"
                                                        data-dept-name="<?= Html::encode($activeDept ? $activeDept->name_th : '') ?>"
                                                        data-pt-id="<?= $pt->id ?>"
                                                        data-pt-name="<?= Html::encode($pt->name_th) ?>"
                                                        data-current-template-id="<?= $curTpl ? $curTpl->id : 0 ?>"
                                                        onclick="window.openAssignModal && window.openAssignModal(<?= $activeDept ? $activeDept->id : 0 ?>, <?= $pt->id ?>, '<?= Html::encode($pt->name_th) ?>', '<?= Html::encode($activeDept ? $activeDept->name_th : '') ?>', <?= $curTpl ? $curTpl->id : 0 ?>)"
                                                        title="สลับไปใช้แบบประเมินอื่น">
                                                    <i class="bi bi-arrow-repeat me-1"></i>เปลี่ยนแบบ
                                                </button>

                                                <!-- Action 2: Customize KPIs or Clone -->
                                                <?php if ($curTpl && ($isCustom || $isSuper)): ?>
                                                    <?= Html::a('<i class="bi bi-pencil-square me-1"></i>ปรับแต่ง KPI', ['/template-builder/builder', 'id' => $curTpl->id], [
                                                        'class' => 'btn btn-sm btn-primary fw-semibold',
                                                        'title' => 'ปรับแต่งตัวชี้วัดและเกณฑ์คะแนน',
                                                    ]) ?>
                                                <?php elseif ($curTpl): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-success fw-semibold btn-clone-assign-template" 
                                                            data-source-id="<?= $curTpl->id ?>"
                                                            data-source-name="<?= Html::encode($curTpl->name_th) ?>"
                                                            data-target-dept-id="<?= $activeDept ? $activeDept->id : 0 ?>"
                                                            data-target-dept-name="<?= Html::encode($activeDept ? $activeDept->name_th : '') ?>"
                                                            onclick="window.openCloneAndAssignModal && window.openCloneAndAssignModal(<?= $curTpl->id ?>, '<?= Html::encode($curTpl->name_th) ?>', <?= $activeDept ? $activeDept->id : 0 ?>, '<?= Html::encode($activeDept ? $activeDept->name_th : '') ?>')"
                                                            title="คัดลอกแม่แบบกลางมาปรับแต่งตัวชี้วัดเฉพาะหน่วยงาน">
                                                        <i class="bi bi-copy me-1"></i>คัดลอกมาปรับแต่ง
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Action 3: Preview Form -->
                                                <?php if ($curTpl): ?>
                                                    <?= Html::a('<i class="bi bi-eye"></i>', ['/template-builder/preview', 'id' => $curTpl->id], [
                                                        'class' => 'btn btn-sm btn-light border text-secondary',
                                                        'title' => 'ดูตัวอย่างแบบฟอร์ม',
                                                        'target' => '_blank',
                                                    ]) ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-light py-2.5 px-4 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>ต้องการปรับตัวชี้วัดเฉพาะทางของหน่วยงาน? คลิก <strong>"คัดลอกมาปรับแต่ง"</strong> เพื่อสร้างแบบประเมินเฉพาะของหน่วยงานและแก้ไขตัวชี้วัดได้ทันที
                    </small>
                </div>
            </div>
        </div>

        <!-- TAB 2: Template Library (Full Inventory) -->
        <div class="tab-pane fade <?= $isLibraryTab ? 'show active' : '' ?>" id="tab-library" role="tabpanel">

            <!-- Compact Search & Filter Bar -->
            <div class="card card-rmutt shadow-sm border-0 mb-3 bg-light-subtle">
                <div class="card-body p-3">
                    <form method="get" action="<?= Url::to(['/template-builder/index']) ?>" class="row g-2 align-items-center">
                        <input type="hidden" name="r" value="template-builder/index">
                        <input type="hidden" name="tab" value="library">

                        <?php if ($isSuper): ?>
                            <div class="col-md-4">
                                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- ทุกหน่วยงาน (ทั้งมหาวิทยาลัย) --</option>
                                    <option value="default" <?= $selectedDepartmentId === 'default' ? 'selected' : '' ?>>⭐ แม่แบบมาตรฐานกลาง</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept->id ?>" <?= strval($selectedDepartmentId) === strval($dept->id) ? 'selected' : '' ?>>
                                            🏢 <?= Html::encode($dept->name_th) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-<?= $isSuper ? '4' : '6' ?>">
                            <select name="personnel_type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- ทุกกลุ่มบุคลากร --</option>
                                <?php foreach ($personnelTypes as $pt): ?>
                                    <option value="<?= $pt->id ?>" <?= strval($selectedPersonnelTypeId) === strval($pt->id) ? 'selected' : '' ?>>
                                        <?= Html::encode($pt->name_th) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-auto ms-auto">
                            <?php if ($selectedDepartmentId || $selectedPersonnelTypeId): ?>
                                <a href="<?= Url::to(['/template-builder/index', 'tab' => 'library']) ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i>ล้างตัวกรอง
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Library Table -->
            <div class="card card-rmutt shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th style="width: 50px;" class="ps-4">#</th>
                                <th>ชื่อแบบประเมิน</th>
                                <th>กลุ่มบุคลากร</th>
                                <th>สังกัด / หน่วยงาน</th>
                                <th>ตัวชี้วัด / สมรรถนะ</th>
                                <th>เวอร์ชัน</th>
                                <th class="text-end pe-4" style="width: 180px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-6 text-muted d-block mb-2"></i>
                                        ไม่พบแบบประเมินที่ตรงกับเงื่อนไขการค้นหา
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($templates as $idx => $tpl): 
                                    $version = $tpl->activeVersion ?: ($tpl->versions ? $tpl->versions[0] : null);
                                    $sections = $version ? $version->sections : [];
                                    $itemCount = 0;
                                    $weightStr = [];
                                    foreach ($sections as $s) {
                                        $itemCount += count($s->items);
                                        $weightStr[] = number_format($s->weight, 0) . '%';
                                    }
                                    $compCount = $version ? count($version->competencyDefinitions) : 0;
                                    $canEdit = $isSuper || ($tpl->department_id && $tpl->department_id == ($myDept ? $myDept->id : 0));
                                ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= Html::encode($tpl->name_th) ?></div>
                                            <small class="text-muted">รหัส: <code><?= Html::encode($tpl->code) ?></code></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= Html::encode($tpl->personnelType->name_th) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($tpl->department): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="bi bi-building me-1"></i><?= Html::encode($tpl->department->name_th) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border">
                                                    <i class="bi bi-star me-1"></i>แม่แบบมาตรฐานกลาง
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <span class="text-dark fw-semibold"><?= $itemCount ?> ตัวชี้วัด</span>
                                            <span class="text-muted">| <?= $compCount ?> สมรรถนะ</span>
                                            <?php if (!empty($weightStr)): ?>
                                                <div class="text-muted font-monospace mt-0.5"><?= implode(' / ', $weightStr) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                <?= $version ? Html::encode($version->version_label) : 'v1.0' ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($canEdit): ?>
                                                    <?= Html::a('<i class="bi bi-pencil-square"></i>', ['/template-builder/builder', 'id' => $tpl->id], [
                                                        'class' => 'btn btn-outline-primary',
                                                        'title' => 'ปรับแต่งตัวชี้วัด',
                                                    ]) ?>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary btn-clone-template" 
                                                        data-source-id="<?= $tpl->id ?>"
                                                        data-source-name="<?= Html::encode($tpl->name_th) ?>"
                                                        onclick="window.openCloneModal && window.openCloneModal(<?= $tpl->id ?>, '<?= Html::encode($tpl->name_th) ?>')" 
                                                        title="คัดลอกแบบประเมิน">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                                <?= Html::a('<i class="bi bi-eye"></i>', ['/template-builder/preview', 'id' => $tpl->id], [
                                                    'class' => 'btn btn-outline-secondary',
                                                    'title' => 'ดูตัวอย่าง',
                                                    'target' => '_blank',
                                                ]) ?>
                                                <?php if ($canEdit): ?>
                                                    <?= Html::a('<i class="bi bi-trash"></i>', ['/template-builder/delete', 'id' => $tpl->id], [
                                                        'class' => 'btn btn-outline-danger',
                                                        'title' => 'ลบ',
                                                        'data-method' => 'post',
                                                        'data-confirm' => "ยืนยันการลบแบบประเมิน '{$tpl->name_th}' ?",
                                                    ]) ?>
                                                <?php endif; ?>
                                            </div>
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

</div>

<!-- MODAL 1: Clean & Straightforward Template Assignment -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="assignForm" action="<?= Url::to(['/template-builder/assign-template']) ?>" method="post">
                <input type="hidden" name="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf' ?>" value="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '' ?>">
                <input type="hidden" name="department_id" id="assignDeptId" value="">
                <input type="hidden" name="personnel_type_id" id="assignPtId" value="">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-arrow-repeat text-primary me-1.5"></i>เปลี่ยนแบบประเมินประจำหน่วยงาน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <div class="small text-muted mb-1">กลุ่มบุคลากร:</div>
                        <div class="fw-bold text-primary fs-6" id="assignPtName">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">เลือกแบบประเมินที่ต้องการเปิดใช้งาน: <span class="text-danger">*</span></label>
                        <select name="template_id" id="assignTemplateSelect" class="form-select form-select-lg" required>
                            <!-- Populated dynamically via JS -->
                        </select>
                        <div class="form-text text-muted">
                            บุคลากรในกลุ่มนี้ของหน่วยงานจะได้รับแบบประเมินฉบับที่เลือกในรอบการประเมิน
                        </div>
                    </div>

                    <div class="form-check p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="update_existing" value="1" id="updateExistingCheck" checked>
                        <label class="form-check-label small text-dark fw-semibold" for="updateExistingCheck">
                            อัปเดตแบบประเมินให้บุคลากรในรอบปัจจุบันที่ยังเป็นแบบร่าง (Draft) ทันที
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2.5 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i>บันทึกและเปิดใช้งาน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: 1-Click Clone & Customize for Department -->
<div class="modal fade" id="cloneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="cloneForm" action="<?= Url::to(['/template-builder/clone']) ?>" method="post">
                <input type="hidden" name="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf' ?>" value="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '' ?>">
                <input type="hidden" name="id" id="cloneSourceId" value="">
                <input type="hidden" name="assign_now" id="cloneAssignNow" value="0">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-copy text-success me-1.5"></i>คัดลอกแบบประเมินเพื่อปรับแต่งเฉพาะหน่วยงาน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        ระบบจะคัดลอกโครงสร้าง หมวด ตัวชี้วัด และสมรรถนะทั้งหมด เพื่อให้ท่านสามารถปรับแต่งตัวชี้วัด (KPI) ให้ตรงกับงานของหน่วยงานได้
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">แบบประเมินต้นทาง:</label>
                        <input type="text" id="cloneSourceName" class="form-control form-control-sm bg-light" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">หน่วยงานเป้าหมาย: <span class="text-danger">*</span></label>
                        <select name="target_department_id" id="cloneTargetDeptSelect" class="form-select" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept->id ?>" <?= $activeDept && $dept->id == $activeDept->id ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($dept->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">ชื่อแบบประเมินใหม่:</label>
                        <input type="text" name="new_name" id="cloneNewName" class="form-control" placeholder="เช่น แบบประเมิน... - สำนักวิทยบริการฯ">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2.5 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i>คัดลอกและเริ่มปรับแต่ง
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Modals & Dynamic Data -->
<script>
window.assignedTemplatesData = <?= json_encode($assignedTemplates) ?>;

function showBootstrapModal(modalId) {
    var el = document.getElementById(modalId);
    if (!el) return;
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var inst = bootstrap.Modal.getOrCreateInstance(el);
            inst.show();
            return;
        }
    } catch (e) {
        console.warn('Bootstrap modal instance error:', e);
    }
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.modal !== 'undefined') {
        jQuery(el).modal('show');
    }
}

window.openAssignModal = function(deptId, ptId, ptName, deptName, currentTemplateId) {
    var deptIdInput = document.getElementById('assignDeptId');
    var ptIdInput = document.getElementById('assignPtId');
    var ptNameEl = document.getElementById('assignPtName');
    var select = document.getElementById('assignTemplateSelect');

    if (deptIdInput) deptIdInput.value = deptId || '';
    if (ptIdInput) ptIdInput.value = ptId || '';
    if (ptNameEl) ptNameEl.innerText = (ptName || '-') + (deptName ? ' (' + deptName + ')' : '');

    if (select) {
        select.innerHTML = '';
        var ptData = window.assignedTemplatesData ? window.assignedTemplatesData[ptId] : null;
        if (ptData && ptData.availableTemplates && ptData.availableTemplates.length > 0) {
            ptData.availableTemplates.forEach(function(tpl) {
                var opt = document.createElement('option');
                opt.value = tpl.id;
                
                var label = tpl.name_th;
                if (tpl.department_id) {
                    label += ' [แบบเฉพาะหน่วยงาน]';
                } else {
                    label += ' [แม่แบบมาตรฐานกลาง]';
                }
                if (parseInt(tpl.id, 10) === parseInt(currentTemplateId, 10)) {
                    label += ' ⭐ (กำลังใช้งาน)';
                    opt.selected = true;
                }
                opt.text = label;
                select.appendChild(opt);
            });
        } else {
            var opt = document.createElement('option');
            opt.value = '';
            opt.text = '-- ไม่พบแบบประเมินสำหรับกลุ่มนี้ --';
            select.appendChild(opt);
        }
    }

    showBootstrapModal('assignModal');
};

window.openCloneModal = function(id, name) {
    var idInput = document.getElementById('cloneSourceId');
    var nameInput = document.getElementById('cloneSourceName');
    var assignNowInput = document.getElementById('cloneAssignNow');
    var newNameInput = document.getElementById('cloneNewName');

    if (idInput) idInput.value = id || '';
    if (nameInput) nameInput.value = name || '';
    if (assignNowInput) assignNowInput.value = '0';
    if (newNameInput) newNameInput.value = '';

    showBootstrapModal('cloneModal');
};

window.openCloneAndAssignModal = function(sourceTemplateId, sourceName, targetDeptId, targetDeptName) {
    var idInput = document.getElementById('cloneSourceId');
    var nameInput = document.getElementById('cloneSourceName');
    var assignNowInput = document.getElementById('cloneAssignNow');
    var deptSelect = document.getElementById('cloneTargetDeptSelect');
    var newNameInput = document.getElementById('cloneNewName');

    if (idInput) idInput.value = sourceTemplateId || '';
    if (nameInput) nameInput.value = sourceName || '';
    if (assignNowInput) assignNowInput.value = '1';
    if (deptSelect && targetDeptId) deptSelect.value = targetDeptId;
    if (newNameInput) newNameInput.value = (sourceName || '') + (targetDeptName ? ' (' + targetDeptName + ')' : '');

    showBootstrapModal('cloneModal');
};

// Event Delegation once DOM / jQuery is ready
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('click', '.btn-assign-template', function (e) {
            e.preventDefault();
            var btn = jQuery(this);
            window.openAssignModal(
                btn.data('dept-id'),
                btn.data('pt-id'),
                btn.data('pt-name'),
                btn.data('dept-name'),
                btn.data('current-template-id')
            );
        });

        jQuery(document).on('click', '.btn-clone-template', function (e) {
            e.preventDefault();
            var btn = jQuery(this);
            window.openCloneModal(btn.data('source-id'), btn.data('source-name'));
        });

        jQuery(document).on('click', '.btn-clone-assign-template', function (e) {
            e.preventDefault();
            var btn = jQuery(this);
            window.openCloneAndAssignModal(
                btn.data('source-id'),
                btn.data('source-name'),
                btn.data('target-dept-id'),
                btn.data('target-dept-name')
            );
        });
    }
});
</script>
