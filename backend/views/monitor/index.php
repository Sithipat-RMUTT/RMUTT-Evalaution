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
/** @var string|null $search */
/** @var string|null $activeTab */
/** @var array|null $deptStats */
/** @var array|null $typeStats */
/** @var bool|null $isSuperAdmin */

$this->title = 'ติดตามและสรุปผลการประเมิน';

$evaluations = $evaluations ?? [];
$cycles = $cycles ?? [];
$departments = $departments ?? [];
$types = $types ?? [];
$search = $search ?? '';
$activeTab = in_array($activeTab ?? 'individual', ['individual', 'summary'], true) ? ($activeTab ?? 'individual') : 'individual';
$deptStats = $deptStats ?? [];
$typeStats = $typeStats ?? [];
$isSuperAdmin = $isSuperAdmin ?? (Yii::$app->has('user') && !Yii::$app->user->isGuest ? \common\models\Department::isCentralAdmin() : true);
?>

<div class="monitor-index py-3">

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>ติดตามและสรุปผลการประเมิน
            </h4>
            <p class="text-muted small mb-0">
                ศูนย์กลางติดตามสถานะรายบุคคล ตรวจสอบคะแนน และรายงานสรุปสถิติผลสัมฤทธิ์ประจำรอบ
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($cycleId): ?>
                <?= Html::a('<i class="bi bi-file-earmark-excel-fill text-success me-1"></i> ส่งออกข้อมูล Excel (CSV)', ['/report/export-csv', 'cycle_id' => $cycleId, 'dept_id' => $deptId], [
                    'class' => 'btn btn-outline-success shadow-sm btn-sm fw-semibold',
                    'title' => 'ดาวน์โหลดรายงานสรุปคะแนนรายบุคคลในรูปแบบไฟล์ CSV',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Unified Filters Toolbar -->
    <div class="card card-rmutt p-3 shadow-sm mb-4">
        <form method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="r" value="monitor/index">
            <input type="hidden" name="tab" value="<?= Html::encode($activeTab) ?>">

            <!-- Live Search Text Box -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-search me-1"></i>ค้นหาบุคลากร:</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="ชื่อ-สกุล หรือรหัส..." value="<?= Html::encode($search) ?>">
                </div>
            </div>

            <!-- Cycle Filter -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-calendar3 me-1"></i>รอบการประเมิน:</label>
                <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- เลือกรอบการประเมิน --</option>
                    <?php foreach ($cycles as $c): ?>
                        <option value="<?= $c->id ?>" <?= $cycleId == $c->id ? 'selected' : '' ?>>
                            ปีงบ <?= $c->fiscal_year ?> รอบ <?= $c->cycle_number ?> (<?= Html::encode(mb_substr($c->name_th, 0, 24)) ?>...)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Department Filter -->
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-diagram-3 me-1"></i>ฝ่าย / สังกัด:</label>
                <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกฝ่าย/สังกัด --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d->id ?>" <?= $deptId == $d->id ? 'selected' : '' ?>><?= Html::encode($d->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Personnel Type Filter -->
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-person-badge me-1"></i>ประเภทบุคลากร:</label>
                <select name="type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกประเภท --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t->id ?>" <?= $typeId == $t->id ? 'selected' : '' ?>><?= Html::encode($t->name_th) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-1">
                <label class="form-label small text-muted mb-1 fw-bold"><i class="bi bi-flag me-1"></i>สถานะ:</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="<?= Evaluation::STATUS_SELF_ASSESSMENT ?>" <?= $status === Evaluation::STATUS_SELF_ASSESSMENT ? 'selected' : '' ?>>ประเมินตนเอง</option>
                    <option value="<?= Evaluation::STATUS_SUBMITTED_L1 ?>" <?= $status === Evaluation::STATUS_SUBMITTED_L1 ? 'selected' : '' ?>>รอหัวหน้า (L1)</option>
                    <option value="<?= Evaluation::STATUS_SUBMITTED_L2 ?>" <?= $status === Evaluation::STATUS_SUBMITTED_L2 ? 'selected' : '' ?>>รอฝ่าย (L2)</option>
                    <option value="<?= Evaluation::STATUS_COMPLETED ?>" <?= $status === Evaluation::STATUS_COMPLETED ? 'selected' : '' ?>>เสร็จสมบูรณ์</option>
                    <option value="<?= Evaluation::STATUS_ACKNOWLEDGED ?>" <?= $status === Evaluation::STATUS_ACKNOWLEDGED ? 'selected' : '' ?>>รับทราบผล</option>
                    <option value="<?= Evaluation::STATUS_RETURNED ?>" <?= $status === Evaluation::STATUS_RETURNED ? 'selected' : '' ?>>ส่งกลับแก้ไข</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1" title="ค้นหา"><i class="bi bi-funnel-fill"></i></button>
                <?= Html::a('<i class="bi bi-arrow-counterclockwise"></i>', ['index', 'cycle_id' => $cycleId, 'tab' => $activeTab], ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'ล้างตัวกรอง']) ?>
            </div>
        </form>
    </div>

    <!-- Consolidated View Switcher Tabs -->
    <ul class="nav nav-pills mb-3 gap-2" id="monitorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link py-2 px-3 fw-semibold <?= $activeTab === 'individual' ? 'active bg-primary' : 'bg-white border text-dark' ?>" 
               href="<?= Url::to(['index', 'cycle_id' => $cycleId, 'dept_id' => $deptId, 'type_id' => $typeId, 'status' => $status, 'search' => $search, 'tab' => 'individual']) ?>">
                <i class="bi bi-people-fill me-1.5"></i> รายชื่อและผลประเมินรายบุคคล
                <span class="badge <?= $activeTab === 'individual' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' ?> ms-1">
                    <?= count($evaluations) ?> ราย
                </span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link py-2 px-3 fw-semibold <?= $activeTab === 'summary' ? 'active bg-primary' : 'bg-white border text-dark' ?>" 
               href="<?= Url::to(['index', 'cycle_id' => $cycleId, 'dept_id' => $deptId, 'type_id' => $typeId, 'status' => $status, 'search' => $search, 'tab' => 'summary']) ?>">
                <i class="bi bi-pie-chart-fill me-1.5"></i> สรุปผลภาพรวมเชิงสถิติ (ฝ่าย / ประเภท)
            </a>
        </li>
    </ul>

    <!-- TAB 1: Individual Personnel Evaluations & Scores -->
    <?php if ($activeTab === 'individual'): ?>
        <div class="card card-rmutt shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;" class="text-center">#</th>
                            <th>ผู้รับการประเมิน</th>
                            <th>ตำแหน่ง / ฝ่ายสังกัด</th>
                            <th>ประเภท</th>
                            <th>ผู้ประเมิน</th>
                            <th class="text-center" style="width: 110px;">ภาระงาน (KPI)</th>
                            <th class="text-center" style="width: 110px;">สมรรถนะ</th>
                            <th class="text-center" style="width: 130px;">คะแนนสุทธิ / เกรด</th>
                            <th class="text-center" style="width: 120px;">สถานะ</th>
                            <th class="text-center" style="width: 110px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($evaluations)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                    ไม่พบข้อมูลการประเมินตามเงื่อนไขที่เลือก
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($evaluations as $idx => $eval): 
                                $r = $eval->result;
                                $rowTypeCode = $eval->personnel && $eval->personnel->personnelType ? strtoupper($eval->personnel->personnelType->code) : '';
                                $rowPerfWeight = in_array($rowTypeCode, ['CIVIL', 'UNIVERSITY', 'GOVT'], true) ? '70%' : ($rowTypeCode === 'SPECIAL' ? '55%' : '70%');
                                $rowCompWeight = in_array($rowTypeCode, ['CIVIL', 'UNIVERSITY', 'GOVT'], true) ? '30%' : ($rowTypeCode === 'SPECIAL' ? '45%' : '30%');
                            ?>
                                <tr>
                                    <td class="text-center text-muted small fw-bold"><?= $idx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= Html::encode($eval->personnel->fullName) ?></strong>
                                        <small class="text-muted">รหัส: <?= Html::encode($eval->personnel->employee_code ?: '-') ?></small>
                                    </td>
                                    <td>
                                        <div class="text-dark"><?= Html::encode($eval->personnel->position ? $eval->personnel->position->name_th : '-') ?></div>
                                        <small class="text-muted"><?= Html::encode($eval->personnel->department ? $eval->personnel->department->name_th : '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= Html::encode($eval->personnel->personnelType ? $eval->personnel->personnelType->name_th : '-') ?>
                                        </span>
                                    </td>
                                    <td class="small">
                                        <?= $eval->evaluator ? Html::encode($eval->evaluator->fullName) : '<span class="text-warning">-</span>' ?>
                                    </td>
                                    <td class="text-center small">
                                        <?php if ($eval->isCompleted() && $r && $r->supervisor_performance_score !== null): ?>
                                            <span class="fw-bold text-dark"><?= number_format($r->supervisor_performance_score, 2) ?></span>
                                            <small class="text-muted d-block">(<?= $rowPerfWeight ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center small">
                                        <?php if ($eval->isCompleted() && $r && $r->supervisor_competency_score !== null): ?>
                                            <span class="fw-bold text-dark"><?= number_format($r->supervisor_competency_score, 2) ?></span>
                                            <small class="text-muted d-block">(<?= $rowCompWeight ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($eval->isCompleted() && $r && $r->final_percentage !== null): ?>
                                            <div class="fw-bold text-primary fs-6"><?= number_format($r->final_percentage, 2) ?>%</div>
                                            <div class="mt-0.5"><?= $r->performanceBadge ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">รอเสร็จสิ้น</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $eval->statusLabel ?>
                                    </td>
                                    <td class="text-center">
                                        <?= Html::a('<i class="bi bi-eye me-1"></i> ตรวจสอบ', ['view', 'id' => $eval->id], [
                                            'class' => 'btn btn-sm btn-outline-primary fw-semibold px-2.5',
                                            'title' => 'ดูรายละเอียดผลการประเมินและประวัติ',
                                        ]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- TAB 2: Summary Stats by Department & Personnel Type -->
    <?php else: ?>
        <div class="row g-4">
            
            <!-- 1. By Department -->
            <div class="col-lg-6">
                <div class="card card-rmutt shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-diagram-3-fill me-2"></i>สรุปผลรายฝ่าย / สังกัด</h6>
                        <span class="badge bg-light text-secondary border">ทั้งหมด <?= count($deptStats) ?> ฝ่าย</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-admin align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ฝ่าย / หน่วยงาน</th>
                                    <th class="text-center" style="width: 120px;">บุคลากร</th>
                                    <th class="text-center" style="width: 140px;">ประเมินเสร็จ</th>
                                    <th class="text-end pe-3" style="width: 130px;">คะแนนเฉลี่ย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deptStats)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลสังกัดในรอบนี้</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deptStats as $ds): ?>
                                        <tr>
                                            <td><strong class="text-dark"><?= Html::encode($ds['department']->name_th) ?></strong></td>
                                            <td class="text-center"><?= $ds['total'] ?> คน</td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $ds['completed'] == $ds['total'] && $ds['total'] > 0 ? 'success' : 'secondary' ?>">
                                                    <?= $ds['completed'] ?> / <?= $ds['total'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-3 fw-bold text-primary">
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
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-person-lines-fill me-2"></i>สรุปผลตามประเภทบุคลากร</h6>
                        <span class="badge bg-light text-secondary border">ทั้งหมด <?= count($typeStats) ?> ประเภท</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-admin align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ประเภทบุคลากร</th>
                                    <th class="text-center" style="width: 120px;">จำนวน</th>
                                    <th class="text-center" style="width: 140px;">ประเมินเสร็จ</th>
                                    <th class="text-end pe-3" style="width: 130px;">คะแนนเฉลี่ย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($typeStats)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลประเภทบุคลากร</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($typeStats as $ts): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-dark"><?= Html::encode($ts['type']->name_th) ?></strong>
                                                <small class="text-muted d-block">
                                                    <?php 
                                                        $code = strtoupper($ts['type']->code);
                                                        if ($code === 'SPECIAL') {
                                                            echo 'สัดส่วน: ภาระงาน (55%) + คุณลักษณะ (45%)';
                                                        } else {
                                                            echo 'สัดส่วน: ภาระงาน (70%) + สมรรถนะ (30%)';
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
                                            <td class="text-end pe-3 fw-bold text-primary">
                                                <?= $ts['avg_score'] > 0 ? number_format($ts['avg_score'], 2) . '%' : '-' ?>
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
    <?php endif; ?>

</div>
