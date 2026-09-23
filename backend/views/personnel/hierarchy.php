<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var common\models\Personnel[] $personnelList */
/** @var common\models\Department[] $departments */
/** @var common\models\Department $currentDept */
/** @var bool $isSuperAdmin */

$this->title = 'ผังสายการประเมินและบังคับบัญชา (Evaluation Hierarchy)';
$this->params['breadcrumbs'][] = ['label' => 'ข้อมูลบุคลากร', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$currentOrg = $currentOrg ?? null;
$allOrgs = $allOrgs ?? [];
$workUnits = $workUnits ?? [];
$currentWorkUnit = $currentWorkUnit ?? null;

// Filter supervisors and division heads from current list
$divisionHeads = [];
$sectionHeads = [];
foreach ($personnelList as $p) {
    if (in_array($p->position_level, ['division_head', 'director'], true) || $p->isDivisionHead()) {
        $divisionHeads[$p->id] = $p->fullName . ' (' . ($p->position ? $p->position->name_th : '') . ')';
    }
    if (in_array($p->position_level, ['section_head', 'division_head', 'director'], true) || $p->is_supervisor || $p->isSectionHead()) {
        $sectionHeads[$p->id] = $p->fullName . ' (' . ($p->position ? $p->position->name_th : '') . ')';
    }
}
// Fallback if empty
if (empty($divisionHeads)) {
    foreach ($personnelList as $p) {
        $divisionHeads[$p->id] = $p->fullName;
    }
}
if (empty($sectionHeads)) {
    $sectionHeads = $divisionHeads;
}

$unassignedCount = 0;
foreach ($personnelList as $p) {
    if (empty($p->supervisor_id) && $p->position_level === 'staff') {
        $unassignedCount++;
    }
}
?>

<div class="personnel-hierarchy py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-3 border-bottom gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-diagram-3-fill text-primary me-2"></i><?= Html::encode($this->title) ?>
            </h3>
            <p class="text-muted mb-0">
                หน่วยงาน: <strong class="text-dark"><i class="bi bi-building me-1"></i><?= Html::encode($currentOrg ? $currentOrg->name_th : 'สำนักวิทยบริการและเทคโนโลยีสารสนเทศ') ?></strong>
                <?php if ($currentDept): ?>
                    &bull; ฝ่าย: <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= Html::encode($currentDept->name_th) ?></span>
                <?php endif; ?>
                <?php if ($currentWorkUnit): ?>
                    &bull; งาน: <span class="badge bg-info-subtle text-info border border-info-subtle"><?= Html::encode($currentWorkUnit) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if ($isSuperAdmin && !empty($allOrgs) && count($allOrgs) > 1): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle btn-sm shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-buildings me-1"></i> สลับหน่วยงานหลัก: <strong><?= Html::encode($currentOrg?->name_th ?: 'เลือกหน่วยงาน') ?></strong>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="dropdown-header">รายชื่อหน่วยงาน / กอง / สำนัก</li>
                        <?php foreach ($allOrgs as $org): ?>
                            <li>
                                <a class="dropdown-item <?= ($currentOrg && $currentOrg->id === $org->id) ? 'active fw-bold' : '' ?>" href="<?= Url::to(['hierarchy', 'org_id' => $org->id]) ?>">
                                    <?= Html::encode($org->name_th) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <a href="<?= Url::to(['import', 'target_dept_id' => $currentDept?->id ?: $currentOrg?->id]) ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-arrow-up-fill me-1"></i> นำเข้าข้อมูลบุคลากร
            </a>
            <a href="<?= Url::to(['index', 'dept_id' => $currentDept?->id ?: $currentOrg?->id]) ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-people-fill me-1"></i> หน้ารายชื่อ
            </a>
        </div>
    </div>

    <!-- Department & Work Unit Selector Dropdown Filter -->
    <div class="card bg-white border shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <!-- Dropdown 1: ฝ่ายในหน่วยงาน -->
                <div class="col-md-5 col-lg-4">
                    <label for="dept-filter-select" class="form-label fw-bold text-dark mb-1 small">
                        <i class="bi bi-diagram-2-fill text-primary me-1"></i> เลือกฝ่ายในหน่วยงาน:
                    </label>
                    <select class="form-select border-primary-subtle shadow-sm" id="dept-filter-select" onchange="window.location.href='<?= Url::to(['hierarchy', 'org_id' => $currentOrg?->id]) ?>' + (this.value !== 'all' ? ('&dept_id=' + this.value) : '&dept_id=all') + '&work_unit=all'">
                        <option value="all" <?= empty($currentDept) ? 'selected' : '' ?>>🏢 ทุกฝ่ายในหน่วยงาน (แสดงทั้งหมด)</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d->id ?>" <?= ($currentDept && (int)$currentDept->id === (int)$d->id) ? 'selected' : '' ?>>
                                <?= Html::encode($d->name_th) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dropdown 2: งานในฝ่าย -->
                <div class="col-md-5 col-lg-4">
                    <label for="unit-filter-select" class="form-label fw-bold text-dark mb-1 small">
                        <i class="bi bi-briefcase-fill text-info me-1"></i> เลือกงานในฝ่าย:
                    </label>
                    <select class="form-select border-info-subtle shadow-sm" id="unit-filter-select" onchange="window.location.href='<?= Url::to(['hierarchy', 'org_id' => $currentOrg?->id, 'dept_id' => $currentDept?->id ?: 'all']) ?>' + (this.value !== 'all' ? ('&work_unit=' + encodeURIComponent(this.value)) : '&work_unit=all')">
                        <option value="all" <?= empty($currentWorkUnit) ? 'selected' : '' ?>>💼 ทุกงานในฝ่าย (แสดงทั้งหมด)</option>
                        <?php foreach ($workUnits as $u): ?>
                            <option value="<?= Html::encode($u) ?>" <?= ($currentWorkUnit && $currentWorkUnit === $u) ? 'selected' : '' ?>>
                                <?= Html::encode($u) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Button -->
                <?php if ($currentDept || $currentWorkUnit): ?>
                    <div class="col-auto align-self-end">
                        <a href="<?= Url::to(['hierarchy', 'org_id' => $currentOrg?->id, 'dept_id' => 'all', 'work_unit' => 'all']) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> ล้างตัวกรองทั้งหมด
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <span class="text-muted small">บุคลากรทั้งหมดในฝ่าย</span>
                <h3 class="fw-bold mb-0 text-primary mt-1"><?= count($personnelList) ?> <span class="fs-6 fw-normal text-muted">คน</span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                <span class="text-muted small">หัวหน้าฝ่าย (L2)</span>
                <h3 class="fw-bold mb-0 text-info mt-1"><?= count($divisionHeads) ?> <span class="fs-6 fw-normal text-muted">คน</span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <span class="text-muted small">หัวหน้างาน (L1)</span>
                <h3 class="fw-bold mb-0 text-success mt-1"><?= count($sectionHeads) ?> <span class="fs-6 fw-normal text-muted">คน</span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 <?= $unassignedCount > 0 ? 'border-warning' : 'border-secondary' ?>">
                <span class="text-muted small">ยังไม่ได้ระบุหัวหน้างาน</span>
                <h3 class="fw-bold mb-0 <?= $unassignedCount > 0 ? 'text-warning' : 'text-secondary' ?> mt-1"><?= $unassignedCount ?> <span class="fs-6 fw-normal text-muted">คน</span></h3>
            </div>
        </div>
    </div>

    <!-- Assignment Matrix Table -->
    <div class="card bg-white border shadow-sm">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-table me-2 text-primary"></i>ตารางกำหนดสายการบังคับบัญชาและการประเมิน
            </h5>
            <span class="badge bg-secondary-subtle text-secondary border">แสดง <?= count($personnelList) ?> คน</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th style="width: 240px;">บุคลากร / ตำแหน่ง</th>
                        <th style="width: 220px;">ฝ่าย / สังกัด / งาน</th>
                        <th style="width: 140px;">ประเภทบุคลากร</th>
                        <th style="width: 170px;">ระดับการบริหาร</th>
                        <th style="width: 230px;">หัวหน้างาน (ผู้ประเมิน L1)</th>
                        <th style="width: 230px;">หัวหน้าฝ่าย (ผู้ประเมิน L2)</th>
                        <th style="width: 90px;" class="text-center">การทำงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($personnelList)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                ไม่พบบุคลากรในฝ่ายนี้ สามารถ <a href="<?= Url::to(['import', 'target_dept_id' => $currentDept?->id]) ?>">นำเข้าข้อมูล (CSV)</a> ได้
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personnelList as $idx => $p): ?>
                            <tr id="row-<?= $p->id ?>">
                                <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= Html::encode($p->fullName) ?></div>
                                    <small class="text-muted">
                                        <code><?= Html::encode($p->employee_code) ?></code> &bull; <?= Html::encode($p->position ? $p->position->name_th : '-') ?>
                                    </small>
                                    <?php if (!empty($p->work_unit)): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-info-subtle text-info border border-info-subtle py-1 px-2" style="font-size: 0.78rem;">
                                                <i class="bi bi-briefcase-fill me-1"></i><?= Html::encode($p->work_unit) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm mb-1" name="department_id_<?= $p->id ?>">
                                        <?php foreach ($departments as $d): ?>
                                            <option value="<?= $d->id ?>" <?= ((int)$p->department_id === (int)$d->id) ? 'selected' : '' ?>>
                                                <?= Html::encode($d->name_th) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="form-control form-control-sm" name="work_unit_<?= $p->id ?>" value="<?= Html::encode($p->work_unit ?: '') ?>" placeholder="งาน/กลุ่มงาน" list="work-units-datalist">
                                </td>
                                <td>
                                    <?php 
                                        $typeCode = $p->personnelType ? $p->personnelType->code : '';
                                        $badgeClass = match($typeCode) {
                                            'CIVIL' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'UNIVERSITY' => 'bg-info-subtle text-info border border-info-subtle',
                                            'GOVT' => 'bg-success-subtle text-success border border-success-subtle',
                                            'SPECIAL' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                            default => 'bg-secondary-subtle text-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-2 py-1">
                                        <?= Html::encode($p->personnelType ? $p->personnelType->name_th : '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="position_level_<?= $p->id ?>">
                                        <option value="staff" <?= $p->position_level === 'staff' ? 'selected' : '' ?>>พนักงานทั่วไป (Staff)</option>
                                        <option value="section_head" <?= $p->position_level === 'section_head' ? 'selected' : '' ?>>หัวหน้างาน (Section Head)</option>
                                        <option value="division_head" <?= $p->position_level === 'division_head' ? 'selected' : '' ?>>หัวหน้าฝ่าย (Division Head)</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm <?= empty($p->supervisor_id) && $p->position_level === 'staff' ? 'border-warning' : '' ?>" name="supervisor_id_<?= $p->id ?>">
                                        <option value="">-- ไม่มี / ส่งตรงหา L2 --</option>
                                        <?php foreach ($sectionHeads as $sId => $sName): ?>
                                            <option value="<?= $sId ?>" <?= ((int)$p->supervisor_id === (int)$sId) ? 'selected' : '' ?>>
                                                <?= Html::encode($sName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="division_head_id_<?= $p->id ?>">
                                        <option value="">-- ไม่ระบุ --</option>
                                        <?php foreach ($divisionHeads as $dId => $dName): ?>
                                            <option value="<?= $dId ?>" <?= ((int)$p->division_head_id === (int)$dId) ? 'selected' : '' ?>>
                                                <?= Html::encode($dName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary px-3 btn-save-row" data-id="<?= $p->id ?>">
                                        <i class="bi bi-check-lg"></i> บันทึก
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<datalist id="work-units-datalist">
    <?php foreach ($workUnits as $u): ?>
        <option value="<?= Html::encode($u) ?>">
    <?php endforeach; ?>
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var saveUrl = '<?= Url::to(['quick-assign']) ?>';
    var csrfParam = '<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : "_csrf" ?>';
    var csrfToken = '<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : "" ?>';

    document.querySelectorAll('.btn-save-row').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var deptElem = document.querySelector('select[name="department_id_' + id + '"]');
            var deptId = deptElem ? deptElem.value : '';
            var unitElem = document.querySelector('input[name="work_unit_' + id + '"]');
            var workUnit = unitElem ? unitElem.value : '';
            var posLevel = document.querySelector('select[name="position_level_' + id + '"]').value;
            var supId = document.querySelector('select[name="supervisor_id_' + id + '"]').value;
            var divId = document.querySelector('select[name="division_head_id_' + id + '"]').value;

            var originalHtml = this.innerHTML;
            var currentBtn = this;
            currentBtn.disabled = true;
            currentBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            var formData = new FormData();
            formData.append(csrfParam, csrfToken);
            formData.append('personnel_id', id);
            formData.append('department_id', deptId);
            formData.append('work_unit', workUnit);
            formData.append('position_level', posLevel);
            formData.append('supervisor_id', supId);
            formData.append('division_head_id', divId);

            fetch(saveUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                currentBtn.disabled = false;
                if (data.success) {
                    currentBtn.classList.remove('btn-primary');
                    currentBtn.classList.add('btn-success');
                    currentBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> สำเร็จ';
                    setTimeout(function() {
                        currentBtn.classList.remove('btn-success');
                        currentBtn.classList.add('btn-primary');
                        currentBtn.innerHTML = originalHtml;
                    }, 1800);
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
                    currentBtn.innerHTML = originalHtml;
                }
            })
            .catch(function(err) {
                currentBtn.disabled = false;
                currentBtn.innerHTML = originalHtml;
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย');
            });
        });
    });
});
</script>
