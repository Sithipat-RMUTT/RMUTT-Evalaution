<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle $activeCycle */
/** @var int $totalPersonnel */
/** @var int $totalSupervisors */
/** @var array $statusCounts */
/** @var array $gradeCounts */
/** @var common\models\Evaluation[] $recentEvaluations */
/** @var common\models\Department[] $departments */
/** @var array $deptProgress */
/** @var bool $isSuperAdmin */
/** @var common\models\Department|null $myDepartment */

$this->title = 'แผงควบคุมงานบุคคล (HR Admin Dashboard) - มทร.ธัญบุรี';

$completedCount = $statusCounts['completed'] ?? 0;
$totalCount = $statusCounts['total'] ?? 1;
$percentCompleted = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;
?>

<div class="site-index py-2">

    <?php if (isset($isSuperAdmin) && !$isSuperAdmin && isset($myDepartment) && $myDepartment): ?>
        <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4 rounded-3">
            <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-building fs-5"></i>
            </div>
            <div>
                <div class="fw-bold fs-6">แผงควบคุมประจำหน่วยงาน: <?= Html::encode($myDepartment->name_th) ?></div>
                <small class="text-muted">แสดงสถิติจำนวนบุคลากร ความคืบหน้าการประเมิน และผลคะแนนเฉพาะภายใน <strong><?= Html::encode($myDepartment->name_th) ?></strong></small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header & Quick Actions Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-dark-subtle text-dark border px-2 py-1 small fw-semibold">
                    <i class="bi bi-shield-check text-primary me-1"></i> HR Executive Portal
                </span>
                <?php if ($activeCycle): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small fw-semibold">
                        <i class="bi bi-broadcast me-1"></i> <?= Html::encode($activeCycle->name_th) ?> (Active)
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">
                        ไม่มีรอบการประเมินที่เปิดใช้งาน
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="h3 fw-bold mb-1 text-dark">
                <?= (isset($isSuperAdmin) && !$isSuperAdmin && isset($myDepartment) && $myDepartment) ? 'แผงควบคุมฝ่าย: ' . Html::encode($myDepartment->name_th) : 'แผงควบคุมงานบุคคล (HR Admin Dashboard)' ?>
            </h2>
            <p class="text-muted small mb-0">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</p>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?= Html::a('<i class="bi bi-ui-checks-grid me-1"></i> ติดตามการประเมิน', ['/monitor/index'], ['class' => 'btn btn-primary shadow-sm fw-semibold']) ?>
            <?= Html::a('<i class="bi bi-bar-chart-line-fill me-1"></i> รายงานสรุป/คะแนน', ['/report/index'], ['class' => 'btn btn-outline-primary shadow-sm fw-semibold']) ?>
            <?php if (isset($isSuperAdmin) && $isSuperAdmin): ?>
                <?= Html::a('<i class="bi bi-calendar3 me-1"></i> รอบประเมิน', ['/cycle/index'], ['class' => 'btn btn-outline-secondary shadow-sm']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Cards (4 High-Impact KPIs) -->
    <div class="row g-3 mb-4">
        
        <!-- 1. Total Personnel -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-primary">
                <i class="bi bi-people-fill text-primary stat-icon"></i>
                <div class="text-muted small fw-bold">บุคลากรทั้งหมดในระบบ</div>
                <div class="display-6 fw-bold text-dark mt-1"><?= number_format($totalPersonnel) ?> <span class="fs-6 fw-normal text-muted">คน</span></div>
                <div class="mt-2 text-muted small">
                    <i class="bi bi-award text-warning me-1"></i> ผู้ประเมิน: <strong><?= $totalSupervisors ?></strong> ท่าน
                </div>
            </div>
        </div>

        <!-- 2. Completed -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-success">
                <i class="bi bi-check-circle-fill text-success stat-icon"></i>
                <div class="text-muted small fw-bold">ประเมินเสร็จสมบูรณ์</div>
                <div class="display-6 fw-bold text-success mt-1"><?= number_format($completedCount) ?> <span class="fs-6 fw-normal text-muted">/ <?= $totalCount ?></span></div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: <?= $percentCompleted ?>%"></div>
                </div>
                <div class="mt-2 text-muted small d-flex justify-content-between">
                    <span>ความคืบหน้ารวม</span>
                    <strong class="text-success"><?= $percentCompleted ?>%</strong>
                </div>
            </div>
        </div>

        <!-- 3. Pending Supervisor Review -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-warning">
                <i class="bi bi-hourglass-split text-warning stat-icon"></i>
                <div class="text-muted small fw-bold">รอการตรวจประเมิน</div>
                <div class="display-6 fw-bold text-warning mt-1"><?= number_format(
                    ($statusCounts['submitted'] ?? 0) +
                    ($statusCounts['supervisor_review'] ?? 0) +
                    ($statusCounts['submitted_l1'] ?? 0) +
                    ($statusCounts['submitted_l2'] ?? 0)
                ) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></div>
                <div class="mt-2 text-muted small">
                    <span>รอ L1: <strong><?= ($statusCounts['submitted'] ?? 0) + ($statusCounts['supervisor_review'] ?? 0) ?></strong></span>
                    <span class="ms-2">รอ L2: <strong><?= ($statusCounts['submitted_l1'] ?? 0) ?></strong></span>
                </div>
            </div>
        </div>

        <!-- 4. Self Assessing / Drafting -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-info">
                <i class="bi bi-pencil-square text-info stat-icon"></i>
                <div class="text-muted small fw-bold">อยู่ระหว่างประเมินตนเอง</div>
                <div class="display-6 fw-bold text-info mt-1"><?= number_format($statusCounts['self_assessment'] + ($statusCounts['draft'] ?? 0) + ($statusCounts['returned'] ?? 0)) ?> <span class="fs-6 fw-normal text-muted">คน</span></div>
                <div class="mt-2 small">
                    <?php if (($statusCounts['returned'] ?? 0) > 0): ?>
                        <span class="text-danger fw-semibold"><i class="bi bi-arrow-return-left me-1"></i> ส่งกลับแก้ไข: <?= $statusCounts['returned'] ?> คน</span>
                    <?php else: ?>
                        <span class="text-muted">กำลังกรอกภาระงาน</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Visual Analytics Section -->
    <div class="row g-3 mb-4">
        
        <!-- Submission Status Chart -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold"><i class="bi bi-pie-chart-fill text-primary me-2"></i> สัดส่วนสถานะการประเมิน</div>
                        <small class="text-muted fw-normal">ภาพรวมขั้นตอนการประเมินในรอบปัจจุบัน</small>
                    </div>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div style="max-height: 280px; width: 100%;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Distribution Chart -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold"><i class="bi bi-bar-chart-fill text-success me-2"></i> การกระจายตัวของผลการประเมิน (เกรด)</div>
                        <small class="text-muted fw-normal">จำนวนบุคลากรในแต่ละระดับผลงาน</small>
                    </div>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div style="max-height: 280px; width: 100%;">
                        <canvas id="chartGrades"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Department Progress Breakdown (ความคืบหน้าแยกตามฝ่าย) -->
    <?php if (!empty($deptProgress)): ?>
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-diagram-3-fill me-2"></i> ความคืบหน้าการประเมินแยกตามหน่วยงาน / ฝ่าย</h6>
                    <small class="text-muted">สัดส่วนบุคลากรที่ประเมินเสร็จสิ้นในแต่ละฝ่าย</small>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <?php foreach ($deptProgress as $dp): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded-3 bg-light-subtle h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="fw-bold text-dark text-truncate me-2" title="<?= Html::encode($dp['department']->name_th) ?>">
                                        <?= Html::encode($dp['department']->name_th) ?>
                                    </div>
                                    <span class="badge <?= $dp['percent'] >= 100 ? 'bg-success' : ($dp['percent'] > 0 ? 'bg-primary' : 'bg-secondary') ?> px-2 py-1">
                                        <?= $dp['percent'] ?>%
                                    </span>
                                </div>
                                <div class="progress mb-2" style="height: 7px;">
                                    <div class="progress-bar <?= $dp['percent'] >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $dp['percent'] ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>เสร็จแล้ว: <strong class="text-success"><?= $dp['completed'] ?></strong> / <?= $dp['total'] ?> คน</span>
                                    <span>รอตรวจ: <strong class="text-warning"><?= $dp['pending'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Evaluations & Scores Table -->
    <div class="card card-rmutt shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <div>
                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-clock-history me-2"></i> รายการประเมินและผลคะแนนล่าสุด</h6>
                <small class="text-muted">รายการแบบประเมินที่มีความเคลื่อนไหวล่าสุดในระบบ</small>
            </div>
            <?= Html::a('ดูทั้งหมดในหน้าติดตามการประเมิน <i class="bi bi-arrow-right ms-1"></i>', ['/monitor/index'], ['class' => 'btn btn-sm btn-outline-primary fw-semibold']) ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>ผู้รับการประเมิน</th>
                        <th>ตำแหน่ง / ฝ่าย</th>
                        <th class="text-center" style="width: 140px;">ผลคะแนน / เกรด</th>
                        <th>สถานะ</th>
                        <th class="text-center" style="width: 110px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentEvaluations)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">ยังไม่มีรายการประเมินในรอบปัจจุบัน</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentEvaluations as $idx => $rEval): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.82rem;">
                                            <?= mb_substr($rEval->personnel->first_name_th ?: 'U', 0, 1) ?>
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block"><?= Html::encode($rEval->personnel->fullName) ?></strong>
                                            <small class="text-muted">รหัส: <?= Html::encode($rEval->personnel->employee_code ?: '-') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?= Html::encode($rEval->personnel->position ? $rEval->personnel->position->name_th : '-') ?></div>
                                    <small class="text-muted"><?= Html::encode($rEval->personnel->department ? $rEval->personnel->department->name_th : '-') ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($rEval->isCompleted() && $rEval->result && $rEval->result->final_percentage !== null): ?>
                                        <div class="fw-bold text-primary fs-6"><?= number_format($rEval->result->final_percentage, 2) ?>%</div>
                                        <div class="mt-1"><?= $rEval->result->performanceBadge ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">รอประเมินเสร็จสิ้น</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $rEval->statusLabel ?></td>
                                <td class="text-center">
                                    <?= Html::a('<i class="bi bi-eye me-1"></i> ตรวจสอบ', ['/monitor/view', 'id' => $rEval->id], ['class' => 'btn btn-sm btn-outline-primary fw-semibold']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Scripts -->
<?php
$statusDataJson = json_encode([
    'กำลังประเมินตนเอง' => ($statusCounts['self_assessment'] ?? 0) + ($statusCounts['draft'] ?? 0),
    'รอตรวจ (L1/ส่งแล้ว)' => ($statusCounts['submitted'] ?? 0) + ($statusCounts['submitted_l1'] ?? 0) + ($statusCounts['supervisor_review'] ?? 0),
    'รอตรวจ (L2)' => ($statusCounts['submitted_l2'] ?? 0),
    'เสร็จสมบูรณ์' => $statusCounts['completed'] ?? 0,
    'ส่งกลับแก้ไข' => $statusCounts['returned'] ?? 0,
]);

$gradeDataJson = json_encode([
    'ดีเด่น (95-100%)' => $gradeCounts['ดีเด่น'],
    'ดีมาก (85-94%)' => $gradeCounts['ดีมาก'],
    'ดี (75-84%)' => $gradeCounts['ดี'],
    'พอใช้ (65-74%)' => $gradeCounts['พอใช้'],
    'ต้องปรับปรุง (0-64%)' => $gradeCounts['ต้องปรับปรุง'] + $gradeCounts['ไม่ผ่าน'],
]);

$chartScript = <<<JS
$(function() {
    // 1. Status Donut Chart
    const statusData = {$statusDataJson};
    const ctxStatus = document.getElementById('chartStatus');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#0284c7', '#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            font: { family: 'Sarabun', size: 12 },
                            padding: 16
                        }
                    }
                }
            }
        });
    }

    // 2. Grade Bar Chart
    const gradeData = {$gradeDataJson};
    const ctxGrade = document.getElementById('chartGrades');
    if (ctxGrade) {
        new Chart(ctxGrade, {
            type: 'bar',
            data: {
                labels: Object.keys(gradeData),
                datasets: [{
                    label: 'จำนวนบุคลากร (คน)',
                    data: Object.values(gradeData),
                    backgroundColor: ['#10b981', '#0284c7', '#38bdf8', '#f59e0b', '#ef4444'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Sarabun' } }
                    },
                    x: {
                        ticks: { font: { family: 'Sarabun', size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
JS;
$this->registerJs($chartScript);
?>
