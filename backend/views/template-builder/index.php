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

$this->title = 'ระบบจัดการแบบประเมินประจำหน่วยงาน (Agency Evaluation Templates)';
$this->params['breadcrumbs'][] = $this->title;

$isSuper = isset($adminCtx) && $adminCtx['isSuperAdmin'];
$myDept = isset($adminCtx) ? $adminCtx['department'] : null;
$targetDepartment = $targetDepartment ?? null;
$targetDeptId = $targetDeptId ?? null;
$activeCycle = $activeCycle ?? null;
$assignedTemplates = $assignedTemplates ?? [];
$activeDept = $targetDepartment ?: $myDept;
?>

<div class="template-builder-index py-3">

    <!-- Top Alert & Scope Information -->
    <?php if (!$isSuper && $activeDept): ?>
        <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4 rounded-3 p-3">
            <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="bi bi-building fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold fs-6">ผู้ดูแลระบบประจำหน่วยงาน: <?= Html::encode($activeDept->name_th) ?></div>
                <small class="text-muted">
                    ท่านมีสิทธิ์กำหนดและเปลี่ยนแบบประเมินที่ใช้งานจริงสำหรับบุคลากรในสังกัด <strong><?= Html::encode($activeDept->name_th) ?></strong> ของท่านได้โดยอิสระ
                    <?php if ($activeCycle): ?>
                        | รอบการประเมินปัจจุบัน: <span class="badge bg-success-subtle text-success border border-success-subtle"><?= Html::encode($activeCycle->name_th) ?></span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    <?php elseif ($isSuper): ?>
        <div class="alert alert-dark border-0 shadow-sm d-flex align-items-center mb-4 rounded-3 p-3 bg-dark-subtle">
            <div class="bg-dark text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="bi bi-shield-check fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold fs-6">สิทธิ์ผู้ดูแลระบบส่วนกลาง (Central HR / Superadmin)</div>
                <small class="text-muted">
                    ท่านสามารถกำหนดแบบประเมินแม่แบบมาตรฐานกลาง หรือสลับเลือกดูและจัดการแบบประเมินประจำหน่วยงานใดก็ได้ในมหาวิทยาลัย
                    <?php if ($activeCycle): ?>
                        | รอบประเมิน: <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= Html::encode($activeCycle->name_th) ?></span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Banner -->
    <div class="card card-rmutt shadow-sm mb-4 border-0 text-white p-4" style="background: linear-gradient(135deg, #07152b 0%, #0d2244 60%, #0284c7 100%);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="badge bg-warning text-dark px-2.5 py-1 mb-2 fw-semibold">
                    <i class="bi bi-sliders me-1"></i> Department Template Management
                </div>
                <h3 class="fw-bold mb-1 text-white">
                    ระบบจัดการแบบประเมินประจำหน่วยงาน
                </h3>
                <p class="mb-0 text-white-50">
                    Admin ประจำหน่วยงานสามารถเลือกเปลี่ยนแบบประเมินที่ใช้งานจริง (Assign Template) ปรับแต่งตัวชี้วัด (KPI) สัดส่วนค่าน้ำหนัก และเกณฑ์คะแนนตามบริบทของหน่วยงาน
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <?= Html::a('<i class="bi bi-plus-circle-fill me-1"></i> สร้างแบบประเมินใหม่', ['/template-builder/create'], ['class' => 'btn btn-light text-primary fw-bold shadow-sm px-3']) ?>
            </div>
        </div>
    </div>

    <!-- MAIN FEATURE: Active Template Assignment by Agency -->
    <?php if ($activeDept && !empty($assignedTemplates)): ?>
        <div class="card card-rmutt shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold text-primary mb-0">
                        <i class="bi bi-check2-circle me-2"></i> แบบประเมินที่เปิดใช้งานจริงประจำหน่วยงาน: <?= Html::encode($activeDept->name_th) ?>
                    </h5>
                    <small class="text-muted">
                        แบบประเมินด้านล่างนี้คือแบบประเมินที่บุคลากรใน <?= Html::encode($activeDept->name_th) ?> จะได้รับเมื่อเข้าสู่รอบการประเมิน
                    </small>
                </div>

                <?php if ($isSuper && !empty($departments)): ?>
                    <form method="get" action="<?= Url::to(['/template-builder/index']) ?>" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="r" value="template-builder/index">
                        <span class="small fw-semibold text-muted text-nowrap"><i class="bi bi-building me-1"></i> สลับหน่วยงาน:</span>
                        <select name="department_id" class="form-select form-select-sm" style="min-width: 260px;" onchange="this.form.submit()">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d->id ?>" <?= $d->id == $targetDeptId ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($d->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card-body p-4 bg-light-subtle">
                <div class="row g-3">
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
                        <div class="col-md-6 col-xl-3">
                            <div class="card h-100 shadow-sm border-top border-4 <?= $isCustom ? 'border-success' : 'border-primary' ?> bg-white">
                                <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                                    <div>
                                        <!-- Personnel Type Badge -->
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-dark text-white px-2 py-1 small fw-semibold">
                                                <?= Html::encode($pt->name_th) ?>
                                            </span>
                                            <?php if ($isCustom): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                                    <i class="bi bi-check-circle-fill me-1"></i> เฉพาะหน่วยงาน
                                                </span>
                                            <?php elseif ($isInherited): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle small">
                                                    <i class="bi bi-diagram-2 me-1"></i> สืบทอดจากสำนัก
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border small">
                                                    <i class="bi bi-star me-1"></i> แม่แบบมาตรฐานกลาง
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Template Title -->
                                        <h6 class="fw-bold text-dark mt-2 mb-1" style="min-height: 44px; line-height: 1.4;">
                                            <?= $curTpl ? Html::encode($curTpl->name_th) : 'ยังไม่ได้กำหนดแบบประเมิน' ?>
                                        </h6>

                                        <div class="small text-muted mb-2">
                                            รหัส: <code><?= $curTpl ? Html::encode($curTpl->code) : '-' ?></code>
                                            <span class="badge bg-light text-secondary border ms-1">
                                                <?= $curVer ? Html::encode($curVer->version_label) : 'v1.0' ?>
                                            </span>
                                        </div>

                                        <!-- Structure Stats -->
                                        <div class="p-2.5 rounded-2 bg-light small mb-3 border">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">ตัวชี้วัดภาระงาน (KPI):</span>
                                                <strong class="text-primary"><?= $itemCount ?> ตัวชี้วัด</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">สมรรถนะ (Competency):</span>
                                                <strong class="text-dark"><?= $compCount ?> ด้าน</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">สัดส่วนน้ำหนัก:</span>
                                                <strong class="text-secondary"><?= !empty($weightStr) ? implode(' / ', $weightStr) : '70% / 30%' ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="openAssignModal(<?= $activeDept->id ?>, <?= $pt->id ?>, '<?= Html::encode($pt->name_th) ?>', '<?= Html::encode($activeDept->name_th) ?>', <?= $curTpl ? $curTpl->id : 0 ?>)">
                                            <i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนแบบประเมิน
                                        </button>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($curTpl && ($isCustom || $isSuper)): ?>
                                                <?= Html::a('<i class="bi bi-pencil-square me-1"></i> ปรับแต่ง KPI', ['/template-builder/builder', 'id' => $curTpl->id], [
                                                    'class' => 'btn btn-outline-secondary',
                                                    'title' => 'ปรับแต่งตัวชี้วัดและเกณฑ์คะแนน',
                                                ]) ?>
                                            <?php elseif ($curTpl): ?>
                                                <button type="button" class="btn btn-outline-success" onclick="openCloneAndAssignModal(<?= $curTpl->id ?>, '<?= Html::encode($curTpl->name_th) ?>', <?= $activeDept->id ?>, '<?= Html::encode($activeDept->name_th) ?>')" title="คัดลอกแม่แบบกลางมาเป็นแบบประเมินเฉพาะหน่วยงานและปรับแต่งได้ทันที">
                                                    <i class="bi bi-copy me-1"></i> คัดลอกมาปรับแต่ง
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($curTpl): ?>
                                                <?= Html::a('<i class="bi bi-eye"></i>', ['/template-builder/preview', 'id' => $curTpl->id], [
                                                    'class' => 'btn btn-outline-info',
                                                    'title' => 'พรีวิวฟอร์ม',
                                                    'target' => '_blank',
                                                ]) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Bar for Template Library -->
    <div class="card card-rmutt shadow-sm mb-4 border-0 bg-light">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-dark"><i class="bi bi-collection me-1"></i> คลังแบบประเมินทั้งหมดในระบบ (Template Inventory)</span>
                <small class="text-muted">ค้นหาและจัดการแบบประเมินทั้งหมดที่สร้างขึ้น</small>
            </div>
            <form method="get" action="<?= Url::to(['/template-builder/index']) ?>" class="row g-2 align-items-center">
                <input type="hidden" name="r" value="template-builder/index">
                <?php if ($isSuper): ?>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary mb-1">กรองตามหน่วยงาน / สังกัด (Superadmin):</label>
                        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- แสดงทุกหน่วยงาน (ทั้งมหาวิทยาลัย) --</option>
                            <option value="default" <?= $selectedDepartmentId === 'default' ? 'selected' : '' ?>>⭐ แบบประเมินมาตรฐานกลาง</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept->id ?>" <?= strval($selectedDepartmentId) === strval($dept->id) ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($dept->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary mb-1">หน่วยงานของคุณ:</label>
                        <input type="text" class="form-control form-control-sm bg-white" value="🏢 <?= $myDept ? Html::encode($myDept->name_th) : 'ส่วนกลาง' ?>" readonly disabled>
                    </div>
                <?php endif; ?>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">กรองตามประเภทบุคลากร:</label>
                    <select name="personnel_type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกประเภทบุคลากร --</option>
                        <?php foreach ($personnelTypes as $pt): ?>
                            <option value="<?= $pt->id ?>" <?= strval($selectedPersonnelTypeId) === strval($pt->id) ? 'selected' : '' ?>>
                                📝 <?= Html::encode($pt->name_th) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <?php if ($selectedDepartmentId || $selectedPersonnelTypeId): ?>
                        <a href="<?= Url::to(['/template-builder/index']) ?>" class="btn btn-sm btn-outline-secondary w-100 mt-md-4">
                            <i class="bi bi-x-circle me-1"></i> ล้างตัวกรอง
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates List Table -->
    <div class="card card-rmutt shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>หน่วยงาน / สังกัด</th>
                        <th>ชื่อแบบประเมิน</th>
                        <th>ประเภทบุคลากร</th>
                        <th>สัดส่วนน้ำหนัก (หมวด)</th>
                        <th>จำนวน KPI</th>
                        <th>เวอร์ชัน</th>
                        <th class="text-center" style="width: 220px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 text-muted d-block mb-2"></i>
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
                        ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <?php if ($tpl->department): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                            <i class="bi bi-building me-1"></i> <?= Html::encode($tpl->department->name_th) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6">
                                            <i class="bi bi-star-fill me-1"></i> แม่แบบมาตรฐานกลาง
                                        </span>
                                    <?php endif; ?>
                                </td>
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
                                    <?php if (!empty($weightStr)): ?>
                                        <span class="fw-semibold text-secondary"><?= implode(' / ', $weightStr) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-dark border border-info-subtle">
                                        <?= $itemCount ?> ตัวชี้วัด | <?= $compCount ?> สมรรถนะ
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= $version ? Html::encode($version->version_label) : 'v1.0' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($isSuper || ($tpl->department_id && $tpl->department_id == ($myDept ? $myDept->id : 0))): ?>
                                            <?= Html::a('<i class="bi bi-pencil-square"></i> แก้ไขฟอร์ม', ['/template-builder/builder', 'id' => $tpl->id], [
                                                'class' => 'btn btn-outline-primary',
                                                'title' => 'ปรับแต่งตัวชี้วัดและเกณฑ์คะแนน',
                                            ]) ?>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-success" onclick="openCloneModal(<?= $tpl->id ?>, '<?= Html::encode($tpl->name_th) ?>')" title="คัดลอกไปใช้กับหน่วยงานอื่น">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                        <?= Html::a('<i class="bi bi-eye"></i>', ['/template-builder/preview', 'id' => $tpl->id], [
                                            'class' => 'btn btn-outline-info',
                                            'title' => 'พรีวิวฟอร์ม',
                                            'target' => '_blank',
                                        ]) ?>
                                        <?php if ($isSuper || ($tpl->department_id && $tpl->department_id == ($myDept ? $myDept->id : 0))): ?>
                                            <?= Html::a('<i class="bi bi-trash"></i>', ['/template-builder/delete', 'id' => $tpl->id], [
                                                'class' => 'btn btn-outline-danger',
                                                'title' => 'ลบแบบประเมิน',
                                                'data-method' => 'post',
                                                'data-confirm' => "คุณแน่ใจหรือไม่ว่าต้องการลบแบบประเมิน '{$tpl->name_th}' ?",
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

<!-- MODAL 1: Change / Assign Template for Agency -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form id="assignForm" action="<?= Url::to(['/template-builder/assign-template']) ?>" method="post">
                <input type="hidden" name="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf' ?>" value="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '' ?>">
                <input type="hidden" name="department_id" id="assignDeptId" value="">
                <input type="hidden" name="personnel_type_id" id="assignPtId" value="">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนแบบประเมินประจำหน่วยงาน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <span class="text-muted small">หน่วยงานเป้าหมาย:</span>
                                <div class="fw-bold text-dark fs-6" id="assignDeptName">-</div>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small">ประเภทบุคลากร:</span>
                                <div class="fw-bold text-primary fs-6" id="assignPtName">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">
                            เลือกแบบประเมินที่ต้องการกำหนดให้บุคลากรใช้งาน: <span class="text-danger">*</span>
                        </label>
                        <select name="template_id" id="assignTemplateSelect" class="form-select form-select-lg" required>
                            <!-- Populated dynamically via JS -->
                        </select>
                        <div class="form-text mt-1 text-muted">
                            บุคลากรประเภทนี้ในหน่วยงานจะใช้แบบประเมินที่ท่านเลือกในการประเมินผลรอบปัจจุบันและรอบถัดไป
                        </div>
                    </div>

                    <div class="form-check p-3 bg-light-subtle rounded-3 border mb-3">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="update_existing" value="1" id="updateExistingCheck" checked>
                        <label class="form-check-label small text-dark fw-semibold" for="updateExistingCheck">
                            อัปเดตแบบประเมินสำหรับบุคลากรในรอบการประเมินปัจจุบันที่ยังอยู่ระหว่างประเมินตนเอง (Draft / Self-assessment)
                        </label>
                        <div class="small text-muted ms-4">
                            หากเลือก บุคลากรที่ยังไม่ได้ส่งผลการประเมินจะได้รับการเปลี่ยนแบบประเมินเป็นฉบับใหม่นี้ทันที
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i> ยืนยันการเปลี่ยนแบบประเมิน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: 1-Click Clone & Assign Template -->
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form id="cloneForm" action="<?= Url::to(['/template-builder/clone']) ?>" method="post">
                <input type="hidden" name="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf' ?>" value="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '' ?>">
                <input type="hidden" name="id" id="cloneSourceId" value="">
                <input type="hidden" name="assign_now" id="cloneAssignNow" value="0">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-copy me-1"></i> คัดลอกแบบประเมินสำหรับหน่วยงาน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        ระบบจะคัดลอกโครงสร้างหมวด ตัวชี้วัด KPI เกณฑ์ PDCA 1-5 และสมรรถนะทั้งหมด เพื่อสร้างเป็นแบบประเมินเฉพาะของหน่วยงานทันที
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">แบบประเมินต้นทาง:</label>
                        <input type="text" id="cloneSourceName" class="form-control bg-light" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">เลือกหน่วยงานเป้าหมาย (Department): <span class="text-danger">*</span></label>
                        <select name="target_department_id" id="cloneTargetDeptSelect" class="form-select" required>
                            <option value="">-- เลือกหน่วยงานที่ต้องการนำไปใช้ --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept->id ?>" <?= $activeDept && $dept->id == $activeDept->id ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($dept->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อแบบประเมินใหม่ (เว้นว่างไว้เพื่อตั้งชื่ออัตโนมัติ):</label>
                        <input type="text" name="new_name" id="cloneNewName" class="form-control" placeholder="เช่น แบบประเมินผลการปฏิบัติงาน - สำนักวิทยบริการฯ">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> ยืนยันคัดลอกแบบประเมิน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Modals & Dynamic Option Population -->
<?php
$assignedTemplatesJson = json_encode($assignedTemplates);
$jsScript = <<<JS
const assignedTemplatesData = {$assignedTemplatesJson};

function openAssignModal(deptId, ptId, ptName, deptName, currentTemplateId) {
    document.getElementById('assignDeptId').value = deptId;
    document.getElementById('assignPtId').value = ptId;
    document.getElementById('assignDeptName').innerText = deptName;
    document.getElementById('assignPtName').innerText = ptName;

    const select = document.getElementById('assignTemplateSelect');
    select.innerHTML = '';

    const ptData = assignedTemplatesData[ptId];
    if (ptData && ptData.availableTemplates) {
        ptData.availableTemplates.forEach(tpl => {
            const opt = document.createElement('option');
            opt.value = tpl.id;
            
            let label = tpl.name_th;
            if (tpl.department_id) {
                label += ' (แบบประเมินเฉพาะหน่วยงาน)';
            } else {
                label += ' (⭐ แม่แบบมาตรฐานกลาง)';
            }
            if (tpl.id === currentTemplateId) {
                label += ' [กำลังใช้งานอยู่]';
                opt.selected = true;
            }
            opt.text = label;
            select.appendChild(opt);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}

function openCloneModal(id, name) {
    document.getElementById('cloneSourceId').value = id;
    document.getElementById('cloneSourceName').value = name;
    document.getElementById('cloneAssignNow').value = '0';
    document.getElementById('cloneNewName').value = '';
    const modal = new bootstrap.Modal(document.getElementById('cloneModal'));
    modal.show();
}

function openCloneAndAssignModal(sourceTemplateId, sourceName, targetDeptId, targetDeptName) {
    document.getElementById('cloneSourceId').value = sourceTemplateId;
    document.getElementById('cloneSourceName').value = sourceName;
    document.getElementById('cloneAssignNow').value = '1';
    document.getElementById('cloneTargetDeptSelect').value = targetDeptId;
    document.getElementById('cloneNewName').value = sourceName + ' (' + targetDeptName + ')';
    const modal = new bootstrap.Modal(document.getElementById('cloneModal'));
    modal.show();
}
JS;
$this->registerJs($jsScript);
?>
