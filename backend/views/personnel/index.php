<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Personnel[] $personnelList */
/** @var common\models\PersonnelType[] $types */
/** @var common\models\Department[] $organizations */
/** @var common\models\Department[] $divisions */
/** @var bool $isSuperAdmin */
/** @var int|null $typeId */
/** @var int|null $orgId */
/** @var int|null $deptId */
/** @var string|null $search */

$this->title = 'ข้อมูลบุคลากร';

$orgId = $orgId ?? null;
$deptId = $deptId ?? null;
$typeId = $typeId ?? null;
$search = $search ?? null;
$isSuperAdmin = $isSuperAdmin ?? true;
$organizations = $organizations ?? [];
$divisions = $divisions ?? [];
?>

<div class="personnel-index py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-people-fill text-primary me-2"></i> ข้อมูลบุคลากร</h4>
            <p class="text-muted mb-0">จัดการรายชื่อ กำหนดประเภทบุคลากร และผูกผู้บังคับบัญชาผู้ประเมิน</p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-diagram-3-fill me-1"></i> ผังสายการประเมิน', ['hierarchy', 'org_id' => $orgId, 'dept_id' => $deptId], ['class' => 'btn btn-outline-primary shadow-sm']) ?>
            <?= Html::a('<i class="bi bi-file-earmark-arrow-up-fill me-1"></i> นำเข้าข้อมูล (CSV)', ['import', 'target_dept_id' => $deptId ?: $orgId], ['class' => 'btn btn-outline-success shadow-sm']) ?>
            <?= Html::a('<i class="bi bi-person-plus-fill me-1"></i> เพิ่มบุคลากรใหม่', ['create'], ['class' => 'btn btn-primary shadow-sm']) ?>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-rmutt p-3 shadow-sm mb-4">
        <form id="personnelFilterForm" method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="personnel/index">
            
            <!-- 1. Live Search Text Box -->
            <div class="col-xl-3 col-md-6">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-search me-1"></i>ค้นหาด่วน (พิมพ์กรองทันที):</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="personnelSearchInput" name="search" class="form-control border-start-0 ps-0" placeholder="พิมพ์ชื่อ-สกุล, รหัส, ตำแหน่ง..." value="<?= Html::encode($search) ?>" autocomplete="off">
                    <?php if (!empty($search)): ?>
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" title="ล้างคำค้นหา"><i class="bi bi-x-lg"></i></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Personnel Type Filter -->
            <div class="col-xl-2 col-md-6">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-person-badge me-1"></i>ประเภทบุคลากร:</label>
                <select id="typeSelect" name="type_id" class="form-select form-select-sm select2-searchable" data-placeholder="-- ทุกประเภท --">
                    <option value="">-- ทุกประเภท --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" <?= $typeId == $t->id ? 'selected' : '' ?>><?= Html::encode($t->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 3. Organization Filter (หน่วยงาน / สำนัก / คณะ) -->
            <div class="col-xl-3 col-md-6">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-building me-1"></i>หน่วยงาน / สำนัก:</label>
                <select id="orgSelect" name="org_id" class="form-select form-select-sm select2-searchable" data-placeholder="-- ทุกหน่วยงาน --">
                    <?php if ($isSuperAdmin): ?>
                        <option value="">-- ทุกหน่วยงาน --</option>
                    <?php endif; ?>
                    <?php foreach ($organizations as $org): ?>
                        <option value="<?= $org->id ?>" <?= $orgId == $org->id ? 'selected' : '' ?>><?= Html::encode($org->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 4. Division Filter (ฝ่าย / กลุ่มงาน) -->
            <div class="col-xl-2 col-md-6">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-diagram-2 me-1"></i>ฝ่าย / งาน:</label>
                <select id="deptSelect" name="dept_id" class="form-select form-select-sm select2-searchable" data-placeholder="-- ทุกฝ่าย/งาน --">
                    <option value="">-- ทุกฝ่าย/งาน --</option>
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?= $div->id ?>" data-parent="<?= $div->parent_id ?>" <?= $deptId == $div->id ? 'selected' : '' ?>><?= Html::encode($div->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 5. Action Buttons -->
            <div class="col-xl-2 col-md-12">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel-fill me-1"></i> กรอง</button>
                    <?= Html::a('ล้าง', ['index'], ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'ล้างตัวกรองทั้งหมด']) ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Status Bar -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <div class="small text-muted">
            <span id="matchCountText">แสดงทั้งหมด <strong><?= count($personnelList) ?></strong> รายการ</span>
            <?php if (!empty($search) || !empty($typeId) || !empty($deptId) || (!empty($orgId) && $isSuperAdmin)): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-2"><i class="bi bi-funnel me-1"></i>กรองข้อมูล</span>
            <?php endif; ?>
        </div>
        <div id="liveSearchStatus" class="small text-primary fw-medium" style="display:none;">
            <i class="bi bi-lightning-charge-fill me-1"></i>กรองผลลัพธ์แบบเรียลไทม์
        </div>
    </div>

    <!-- Table List -->
    <div class="card card-rmutt shadow-sm">
        <div class="table-responsive">
            <table id="personnelTable" class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 100px;">รหัส</th>
                        <th>ชื่อ - นามสกุล</th>
                        <th>ตำแหน่ง / ระดับ</th>
                        <th>ฝ่าย / สังกัด</th>
                        <th>ประเภทบุคลากร</th>
                        <th>ผู้ประเมินตามสายงาน</th>
                        <th>บทบาท</th>
                        <th class="text-center" style="width: 80px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="personnelTableBody">
                    <?php if (empty($personnelList)): ?>
                        <tr id="emptyTableInitialRow">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="bi bi-people display-6 text-secondary opacity-50"></i></div>
                                <h6 class="fw-bold text-dark">ไม่พบข้อมูลบุคลากร</h6>
                                <p class="small text-muted mb-0">ลองเปลี่ยนคำค้นหา หรือรีเซ็ตตัวกรองเพื่อดูรายชื่อทั้งหมด</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personnelList as $idx => $p): ?>
                            <tr class="personnel-row">
                                <td class="text-muted row-idx"><?= $idx + 1 ?></td>
                                <td><code><?= Html::encode($p->employee_code ?: '-') ?></code></td>
                                <td>
                                    <strong class="text-dark personnel-name"><?= Html::encode($p->fullName) ?></strong>
                                    <?php if ($p->email): ?>
                                        <small class="text-muted d-block personnel-email"><?= Html::encode($p->email) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="personnel-position"><?= Html::encode($p->position ? $p->position->name_th : '-') ?></div>
                                    <small class="text-muted"><?= Html::encode($p->position ? ($p->position->level_label ?: '-') : '-') ?></small>
                                </td>
                                <td>
                                    <div class="personnel-dept fw-medium text-dark"><?= Html::encode($p->department ? $p->department->name_th : '-') ?></div>
                                    <?php if ($p->department && $p->department->parent): ?>
                                        <small class="text-primary d-block" style="font-size: 0.78rem;">
                                            <i class="bi bi-building me-1"></i><?= Html::encode($p->department->parent->name_th) ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($p->work_unit)): ?>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                            <i class="bi bi-briefcase me-1"></i><?= Html::encode($p->work_unit) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= Html::encode($p->personnelType ? $p->personnelType->name_th : '-') ?></span>
                                </td>
                                <td>
                                    <?php if ($p->supervisor): ?>
                                        <span class="text-primary fw-medium"><i class="bi bi-person-check me-1"></i> <?= Html::encode($p->supervisor->fullName) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p->is_supervisor || in_array($p->position_level, ['section_head', 'division_head', 'director'], true)): ?>
                                        <span class="badge bg-warning text-dark">ผู้ประเมิน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ผู้รับการประเมิน</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $p->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'แก้ไข']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
$clearUrl = Url::to(['index']);
$js = <<<JS
$(document).ready(function() {
    // 1. Initialize Select2 on Searchable Dropdowns
    if ($.fn.select2) {
        $('.select2-searchable').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: function() {
                return $(this).data('placeholder') || '-- เลือก --';
            }
        });

        // Cascading Organization -> Division filter for Superadmin
        $('#orgSelect').on('change', function() {
            const selectedOrgId = $(this).val();
            const deptSelect = $('#deptSelect');
            
            if (selectedOrgId) {
                deptSelect.find('option').each(function() {
                    const parentId = $(this).data('parent');
                    if (!parentId || parentId == selectedOrgId) {
                        $(this).prop('disabled', false).show();
                    } else {
                        $(this).prop('disabled', true).hide();
                        if ($(this).is(':selected')) {
                            deptSelect.val('');
                        }
                    }
                });
            } else {
                deptSelect.find('option').prop('disabled', false).show();
            }
            deptSelect.trigger('change.select2');
            $('#personnelFilterForm').submit();
        });

        $('#deptSelect, #typeSelect').on('change', function() {
            $('#personnelFilterForm').submit();
        });
    }

    // 2. Real-time Live Search as user types
    const searchInput = $('#personnelSearchInput');
    const tableRows = $('#personnelTableBody tr.personnel-row');
    const matchCountText = $('#matchCountText');
    const liveSearchStatus = $('#liveSearchStatus');
    const totalCount = tableRows.length;

    searchInput.on('input keyup', function() {
        const query = $(this).val().trim().toLowerCase();
        let matched = 0;

        if (query === '') {
            tableRows.show();
            $('#noLiveMatchRow').remove();
            matchCountText.html('แสดงทั้งหมด <strong>' + totalCount + '</strong> รายการ');
            liveSearchStatus.hide();
            return;
        }

        liveSearchStatus.show();
        tableRows.each(function() {
            const rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(query) !== -1) {
                $(this).show();
                matched++;
            } else {
                $(this).hide();
            }
        });

        matchCountText.html('พบ <strong>' + matched + '</strong> จาก ' + totalCount + ' รายการ');

        $('#noLiveMatchRow').remove();
        if (matched === 0) {
            $('#personnelTableBody').append(
                '<tr id="noLiveMatchRow"><td colspan="9" class="text-center py-5 text-muted">' +
                '<i class="bi bi-search display-6 d-block mb-2 text-secondary opacity-50"></i>' +
                '<h6 class="fw-bold text-dark">ไม่พบบุคลากรที่ตรงกับคำค้นหา</h6>' +
                '<p class="small text-muted mb-0">ไม่พบข้อมูลที่ตรงกับ "<strong>' + $('<div>').text(query).html() + '</strong>"</p>' +
                '</td></tr>'
            );
        }
    });

    // 3. Clear button functionality
    $('#clearSearchBtn').on('click', function() {
        window.location.href = '$clearUrl';
    });
});
JS;
$this->registerJs($js);
?>
