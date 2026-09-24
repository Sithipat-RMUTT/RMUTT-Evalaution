<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Personnel[] $personnelList */
/** @var common\models\PersonnelType[] $types */
/** @var common\models\Department[] $departments */
/** @var int|null $typeId */
/** @var int|null $deptId */
/** @var string|null $search */

$this->title = 'ข้อมูลบุคลากร';
?>

<div class="personnel-index py-3">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-people-fill text-primary me-2"></i> ข้อมูลบุคลากร</h4>
            <p class="text-muted mb-0">จัดการรายชื่อ กำหนดประเภทบุคลากร และผูกผู้บังคับบัญชาผู้ประเมิน</p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-diagram-3-fill me-1"></i> ผังสายการประเมิน', ['hierarchy', 'dept_id' => $deptId], ['class' => 'btn btn-outline-primary shadow-sm']) ?>
            <?= Html::a('<i class="bi bi-file-earmark-arrow-up-fill me-1"></i> นำเข้าข้อมูล (CSV)', ['import', 'target_dept_id' => $deptId], ['class' => 'btn btn-outline-success shadow-sm']) ?>
            <?= Html::a('<i class="bi bi-person-plus-fill me-1"></i> เพิ่มบุคลากรใหม่', ['create'], ['class' => 'btn btn-primary shadow-sm']) ?>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-rmutt p-3 shadow-sm mb-4">
        <form id="personnelFilterForm" method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-center">
            <input type="hidden" name="r" value="personnel/index">
            
            <!-- Live Search Text Box -->
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-search me-1"></i>ค้นหาด่วน (พิมพ์เพื่อกรองทันที):</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="personnelSearchInput" name="search" class="form-control border-start-0 ps-0" placeholder="พิมพ์ชื่อ-สกุล, รหัส, ตำแหน่ง, อีเมล..." value="<?= Html::encode($search) ?>" autocomplete="off">
                    <?php if (!empty($search)): ?>
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" title="ล้างคำค้นหา"><i class="bi bi-x-lg"></i></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Personnel Type Searchable Select -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-person-badge me-1"></i>ประเภทบุคลากร:</label>
                <select id="typeSelect" name="type_id" class="form-select form-select-sm select2-searchable" data-placeholder="-- ทุกประเภทบุคลากร --">
                    <option value="">-- ทุกประเภทบุคลากร --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" <?= $typeId == $t->id ? 'selected' : '' ?>><?= Html::encode($t->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Department Searchable Select -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-building me-1"></i>ฝ่าย / สังกัด (พิมพ์ค้นหาได้):</label>
                <select id="deptSelect" name="dept_id" class="form-select form-select-sm select2-searchable" data-placeholder="-- ทุกฝ่าย/สังกัด --">
                    <option value="">-- ทุกฝ่าย/สังกัด --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d->id ?>" <?= $deptId == $d->id ? 'selected' : '' ?>><?= Html::encode($d->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-md-2 d-flex align-items-end pt-md-3">
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
            <?php if (!empty($search) || !empty($typeId) || !empty($deptId)): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-2"><i class="bi bi-funnel me-1"></i>กรองจากฐานข้อมูล</span>
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
                                    <div class="personnel-dept"><?= Html::encode($p->department ? $p->department->name_th : '-') ?></div>
                                    <?php if (!empty($p->work_unit)): ?>
                                        <small class="text-muted"><i class="bi bi-briefcase me-1"></i><?= Html::encode($p->work_unit) ?></small>
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
    // 1. Initialize Select2 on Searchable Dropdowns (Type & Department)
    if ($.fn.select2) {
        $('.select2-searchable').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: function() {
                return $(this).data('placeholder') || '-- เลือก --';
            }
        });

        // Trigger filter automatically when dropdown selection changes
        $('.select2-searchable').on('change', function() {
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
