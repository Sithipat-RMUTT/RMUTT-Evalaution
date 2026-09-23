<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;

/** @var yii\web\View $this */
/** @var common\models\Evaluation[] $evaluations */
/** @var common\models\EvaluationCycle[] $cycles */
/** @var common\models\Department[] $departments */
/** @var common\models\PersonnelType[] $types */
/** @var int|null $cycleId */
/** @var int|null $deptId */
/** @var int|null $typeId */
/** @var string|null $status */

$this->title = 'ติดตามสถานะการประเมินทั้งองค์กร';
?>

<div class="monitor-index py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-ui-checks-grid text-primary me-2"></i> ติดตามการประเมินผล</h4>
            <p class="text-muted mb-0">ตรวจสอบสถานะรายบุคคล ดูคะแนน และบริหารจัดการแบบประเมินในระบบ</p>
        </div>
        <span class="badge bg-primary fs-6">พบ <?= count($evaluations) ?> รายการ</span>
    </div>

    <!-- Filters -->
    <div class="card card-rmutt p-3 shadow-sm mb-4">
        <form method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-center">
            <input type="hidden" name="r" value="monitor/index">
            <div class="col-md-3">
                <select name="cycle_id" class="form-select form-select-sm">
                    <option value="">-- เลือกรอบการประเมิน --</option>
                    <?php foreach ($cycles as $c): ?>
                        <option value="<?= $c->id ?>" <?= $cycleId == $c->id ? 'selected' : '' ?>><?= Html::encode($c->name_th) ?></option>
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
            <div class="col-md-2">
                <select name="type_id" class="form-select form-select-sm">
                    <option value="">-- ทุกประเภทบุคลากร --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" <?= $typeId == $t->id ? 'selected' : '' ?>><?= Html::encode($t->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="<?= Evaluation::STATUS_SELF_ASSESSMENT ?>" <?= $status === Evaluation::STATUS_SELF_ASSESSMENT ? 'selected' : '' ?>>กำลังประเมินตนเอง</option>
                    <option value="<?= Evaluation::STATUS_SUBMITTED_L1 ?>" <?= $status === Evaluation::STATUS_SUBMITTED_L1 ? 'selected' : '' ?>>รอหัวหน้างานประเมิน (L1)</option>
                    <option value="<?= Evaluation::STATUS_SUBMITTED_L2 ?>" <?= $status === Evaluation::STATUS_SUBMITTED_L2 ? 'selected' : '' ?>>รอหัวหน้าฝ่ายประเมิน (L2)</option>
                    <option value="<?= Evaluation::STATUS_COMPLETED ?>" <?= $status === Evaluation::STATUS_COMPLETED ? 'selected' : '' ?>>เสร็จสมบูรณ์ (รอรับทราบผล)</option>
                    <option value="<?= Evaluation::STATUS_ACKNOWLEDGED ?>" <?= $status === Evaluation::STATUS_ACKNOWLEDGED ? 'selected' : '' ?>>รับทราบผลแล้ว</option>
                    <option value="<?= Evaluation::STATUS_RETURNED ?>" <?= $status === Evaluation::STATUS_RETURNED ? 'selected' : '' ?>>ส่งกลับแก้ไข</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> ค้นหา</button>
                <?= Html::a('ล้าง', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card card-rmutt shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>ผู้รับการประเมิน</th>
                        <th>ตำแหน่ง / ฝ่าย</th>
                        <th>ประเภท</th>
                        <th>ผู้ประเมินตามสายงาน</th>
                        <th class="text-center" style="width: 130px;">ผลคะแนน / เกรด</th>
                        <th>สถานะ</th>
                        <th class="text-center" style="width: 140px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluations)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลการประเมินตามเงื่อนไขที่เลือก</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($evaluations as $idx => $eval): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <strong class="text-dark"><?= Html::encode($eval->personnel->fullName) ?></strong>
                                    <small class="text-muted d-block">รหัส: <?= Html::encode($eval->personnel->employee_code ?: '-') ?></small>
                                </td>
                                <td>
                                    <div><?= Html::encode($eval->personnel->position->name_th) ?></div>
                                    <small class="text-muted"><?= Html::encode($eval->personnel->department->name_th) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= Html::encode($eval->personnel->personnelType->name_th) ?></span>
                                </td>
                                <td>
                                    <?= $eval->evaluator ? Html::encode($eval->evaluator->fullName) : '<span class="text-warning">-</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($eval->isCompleted() && $eval->result && $eval->result->final_percentage !== null): ?>
                                        <div class="fw-bold text-primary fs-6"><?= number_format($eval->result->final_percentage, 2) ?>%</div>
                                        <div class="mt-1"><?= $eval->result->performanceBadge ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">รอประเมินเสร็จสิ้น</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $eval->statusLabel ?></td>
                                <td class="text-center">
                                    <?= Html::a('<i class="bi bi-eye me-1"></i> ตรวจสอบ', ['view', 'id' => $eval->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
