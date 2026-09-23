<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle[] $cycles */
/** @var common\models\Department[] $departments */
/** @var int|null $cycleId */
/** @var int|null $deptId */
/** @var array $deptStats */
/** @var array $typeStats */
/** @var common\models\Evaluation[] $individualEvaluations */
/** @var bool $isSuperadmin */

$this->title = 'รายงานสรุปผลการประเมินการปฏิบัติราชการ';

$isSuperadmin = $isSuperadmin ?? \common\models\Department::isCentralAdmin();
$departments = $departments ?? [];
$deptId = $deptId ?? null;
$cycleId = $cycleId ?? null;
$deptStats = $deptStats ?? [];
$typeStats = $typeStats ?? [];
$individualEvaluations = $individualEvaluations ?? [];
?>

<div class="report-index py-3">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i> รายงานสรุปผลการประเมิน</h4>
            <p class="text-muted mb-0">
                <?= $isSuperadmin ? 'ภาพรวมมหาวิทยาลัย สามารถเลือกดูทุกหน่วยงานหรือฟิลเตอร์เฉพาะฝ่าย' : 'รายงานผลการประเมินและคะแนนบุคลากรประจำหน่วยงาน' ?>
            </p>
        </div>
        <div>
            <?php if ($cycleId): ?>
                <?= Html::a('<i class="bi bi-file-earmark-excel-fill text-success me-1"></i> ส่งออกข้อมูล Excel (CSV)', ['export-csv', 'cycle_id' => $cycleId, 'dept_id' => $deptId], ['class' => 'btn btn-outline-success shadow-sm']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card card-rmutt p-3 shadow-sm mb-4">
        <form method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-center">
            <input type="hidden" name="r" value="report/index">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1 fw-bold">รอบการประเมิน:</label>
                <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit();">
                    <?php foreach ($cycles as $c): ?>
                        <option value="<?= $c->id ?>" <?= $cycleId == $c->id ? 'selected' : '' ?>><?= Html::encode($c->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1 fw-bold">ฝ่าย / หน่วยงาน:</label>
                <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit();">
                    <?php if ($isSuperadmin): ?>
                        <option value="">-- ทุกฝ่าย/สังกัด (ภาพรวมทั้งมหาวิทยาลัย) --</option>
                    <?php else: ?>
                        <option value="">-- ทุกฝ่ายในสังกัดที่ดูแล --</option>
                    <?php endif; ?>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d->id ?>" <?= $deptId == $d->id ? 'selected' : '' ?>><?= Html::encode($d->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1" style="padding-top: 1.5rem;">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> กรอง</button>
                <?= Html::a('ล้าง', ['index', 'cycle_id' => $cycleId], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            </div>
        </form>
    </div>

    <!-- Summary Stats Row -->
    <div class="row g-4 mb-4">
        
        <!-- 1. By Department -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-diagram-3-fill me-1"></i> สรุปผลรายฝ่าย / สังกัด</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-admin align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ฝ่าย/หน่วยงาน</th>
                                <th class="text-center">จำนวนบุคลากร</th>
                                <th class="text-center">ประเมินเสร็จ</th>
                                <th class="text-end">คะแนนเฉลี่ย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deptStats)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">ไม่พบข้อมูลสังกัด</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deptStats as $ds): ?>
                                    <tr>
                                        <td><strong><?= Html::encode($ds['department']->name_th) ?></strong></td>
                                        <td class="text-center"><?= $ds['total'] ?> คน</td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $ds['completed'] == $ds['total'] && $ds['total'] > 0 ? 'success' : 'secondary' ?>">
                                                <?= $ds['completed'] ?> / <?= $ds['total'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-primary">
                                            <?= $ds['avg_score'] > 0 ? number_format($ds['avg_score'], 2) . '%' : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. By Personnel Type -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-person-lines-fill me-1"></i> สรุปผลตามประเภทบุคลากร</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-admin align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ประเภทบุคลากร</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-center">ประเมินเสร็จ</th>
                                <th class="text-end">คะแนนเฉลี่ย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($typeStats as $ts): ?>
                                <tr>
                                    <td>
                                        <strong><?= Html::encode($ts['type']->name_th) ?></strong>
                                        <small class="text-muted d-block">
                                            <?php 
                                                $code = strtoupper($ts['type']->code);
                                                if ($code === 'SPECIAL') {
                                                    echo 'สัดส่วน: ผลสัมฤทธิ์ (55%) + คุณลักษณะ (45%)';
                                                } elseif ($code === 'GOVT') {
                                                    echo 'สัดส่วน: ผลสัมฤทธิ์ (70%) + พฤติกรรม (30%)';
                                                } else {
                                                    echo 'สัดส่วน: ผลสัมฤทธิ์ (70%) + สมรรถนะ (30%)';
                                                }
                                            ?>
                                        </small>
                                    </td>
                                    <td class="text-center"><?= $ts['total'] ?> คน</td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $ts['completed'] == $ts['total'] && $ts['total'] > 0 ? 'success' : 'secondary' ?>">
                                            <?= $ts['completed'] ?> / <?= $ts['total'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        <?= $ts['avg_score'] > 0 ? number_format($ts['avg_score'], 2) . '%' : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. Individual Personnel Evaluation Scores Table -->
    <div class="card card-rmutt shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <div>
                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-people-fill me-1"></i> ผลคะแนนการประเมินรายบุคคล (Individual Evaluation Scores)</h6>
                <small class="text-muted">
                    <?= $deptId ? 'แสดงเฉพาะบุคลากรในฝ่ายที่เลือก' : ($isSuperadmin ? 'แสดงบุคลากรทุกหน่วยงานในรอบการประเมิน' : 'แสดงเฉพาะบุคลากรในหน่วยงานของท่าน') ?>
                </small>
            </div>
            <span class="badge bg-primary fs-6">รวม <?= count($individualEvaluations) ?> ราย</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 45px;">#</th>
                        <th>ผู้รับการประเมิน</th>
                        <th>ตำแหน่ง / ฝ่าย</th>
                        <th>ประเภท</th>
                        <th class="text-center" style="width: 120px;">ภาระงาน/ผลงาน</th>
                        <th class="text-center" style="width: 120px;">สมรรถนะ/พฤติกรรม</th>
                        <th class="text-center" style="width: 120px;">คะแนนสุทธิ</th>
                        <th class="text-center" style="width: 110px;">ระดับผลงาน</th>
                        <th class="text-center" style="width: 110px;">สถานะ</th>
                        <th class="text-center" style="width: 90px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($individualEvaluations)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">ไม่พบข้อมูลผลการประเมินรายบุคคลตามเงื่อนไข</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($individualEvaluations as $idx => $eval): 
                            $r = $eval->result;
                            $rowTypeCode = $eval->personnel && $eval->personnel->personnelType ? $eval->personnel->personnelType->code : '';
                            $rowPerfWeight = in_array($rowTypeCode, ['CIVIL', 'UNIVERSITY', 'GOVT'], true) ? '70%' : ($rowTypeCode === 'SPECIAL' ? '55' : '70%');
                            $rowCompWeight = in_array($rowTypeCode, ['CIVIL', 'UNIVERSITY', 'GOVT'], true) ? '30%' : ($rowTypeCode === 'SPECIAL' ? '45' : '30%');
                        ?>
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
                                <td class="text-center">
                                    <?php if ($eval->isCompleted() && $r && $r->supervisor_performance_score !== null): ?>
                                        <span class="fw-bold text-dark"><?= number_format($r->supervisor_performance_score, 2) ?></span>
                                        <small class="text-muted d-block">(<?= $rowPerfWeight ?>)</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($eval->isCompleted() && $r && $r->supervisor_competency_score !== null): ?>
                                        <span class="fw-bold text-dark"><?= number_format($r->supervisor_competency_score, 2) ?></span>
                                        <small class="text-muted d-block">(<?= $rowCompWeight ?>)</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($eval->isCompleted() && $r && $r->final_percentage !== null): ?>
                                        <span class="fw-bold text-primary fs-6"><?= number_format($r->final_percentage, 2) ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= ($eval->isCompleted() && $r) ? $r->performanceBadge : '<span class="badge bg-light text-muted border">รอประเมินเสร็จสิ้น</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?= $eval->statusLabel ?>
                                </td>
                                <td class="text-center">
                                    <?= Html::a('<i class="bi bi-eye"></i> ตรวจสอบ', ['monitor/view', 'id' => $eval->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'ดูรายละเอียดผลการประเมิน']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
