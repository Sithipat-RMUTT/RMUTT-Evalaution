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
        <form method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-center">
            <input type="hidden" name="r" value="personnel/index">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหาชื่อ-สกุล หรือรหัส..." value="<?= Html::encode($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="type_id" class="form-select form-select-sm">
                    <option value="">-- ทุกประเภทบุคลากร --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" <?= $typeId == $t->id ? 'selected' : '' ?>><?= Html::encode($t->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="dept_id" class="form-select form-select-sm">
                    <option value="">-- ทุกฝ่าย/สังกัด --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d->id ?>" <?= $deptId == $d->id ? 'selected' : '' ?>><?= Html::encode($d->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> กรองข้อมูล</button>
                <?= Html::a('ล้าง', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            </div>
        </form>
    </div>

    <!-- Table List -->
    <div class="card card-rmutt shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
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
                <tbody>
                    <?php if (empty($personnelList)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="bi bi-people display-6 text-secondary opacity-50"></i></div>
                                <h6 class="fw-bold text-dark">ยังไม่มีข้อมูลบุคลากรในระบบ</h6>
                                <p class="small text-muted mb-0">ระบบพร้อมสำหรับการนำเข้าข้อมูลบุคลากรชุดใหม่ผ่านไฟล์ CSV หรือเพิ่มรายชื่อใหม่</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personnelList as $idx => $p): ?>
                            <tr>
                                <td class="text-muted"><?= $idx + 1 ?></td>
                                <td><code><?= Html::encode($p->employee_code ?: '-') ?></code></td>
                                <td>
                                    <strong class="text-dark"><?= Html::encode($p->fullName) ?></strong>
                                    <?php if ($p->email): ?>
                                        <small class="text-muted d-block"><?= Html::encode($p->email) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= Html::encode($p->position ? $p->position->name_th : '-') ?></div>
                                    <small class="text-muted"><?= Html::encode($p->position ? ($p->position->level_label ?: '-') : '-') ?></small>
                                </td>
                                <td>
                                    <div><?= Html::encode($p->department ? $p->department->name_th : '-') ?></div>
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

