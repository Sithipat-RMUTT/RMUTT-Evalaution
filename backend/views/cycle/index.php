<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\EvaluationCycle;
use common\models\DepartmentEvaluationCycle;

/** @var yii\web\View $this */
/** @var bool $isCentral */
/** @var common\models\Department|null $myDepartment */
/** @var int|null $myRootDeptId */
/** @var common\models\EvaluationCycle[] $cycles */
/** @var common\models\EvaluationCycle|null $activeCycle */
/** @var common\models\Department[] $rootDepartments */
/** @var array $deptStatuses */
/** @var array|null $subDivisionStatuses */

$this->title = 'จัดการรอบการประเมินผลการปฏิบัติราชการ';

// Safe fallbacks for resilient rendering
$isCentral = $isCentral ?? \common\models\Department::isCentralAdmin();
$myDepartment = $myDepartment ?? null;
$myRootDeptId = $myRootDeptId ?? null;
$cycles = $cycles ?? [];
$activeCycle = $activeCycle ?? (EvaluationCycle::find()->where(['status' => [EvaluationCycle::STATUS_ACTIVE, EvaluationCycle::STATUS_EVALUATION]])->orderBy(['id' => SORT_DESC])->one());
$rootDepartments = $rootDepartments ?? (\common\models\Department::find()->where(['parent_id' => null])->orderBy(['name_th' => SORT_ASC])->all());
$deptStatuses = $deptStatuses ?? [];
$subDivisionStatuses = $subDivisionStatuses ?? [];
?>

<div class="cycle-index py-3">

    <!-- Header & Action -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-calendar3 text-primary me-2"></i>จัดการรอบการประเมินผลการปฏิบัติงาน
            </h4>
            <p class="text-muted small mb-0">
                <?php if ($isCentral): ?>
                    ภาพรวมรอบประเมินมหาวิทยาลัย และระบบติดตามสถานะการเปิดรอบการประเมินรายหน่วยงาน (Decentralized Governance)
                <?php else: ?>
                    การเปิดรอบการประเมินประจำหน่วยงาน <strong><?= Html::encode($myDepartment ? $myDepartment->name_th : '') ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($isCentral): ?>
                <?= Html::a('<i class="bi bi-plus-circle me-1"></i> สร้างรอบการประเมินมหาวิทยาลัย', ['create'], ['class' => 'btn btn-primary shadow-sm']) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$isCentral && $myDepartment && $activeCycle && isset($deptStatuses[$myDepartment->id])): 
        $myStatus = $deptStatuses[$myDepartment->id];
        $dCycle = $myStatus['deptCycle'];
        $isPending = ($dCycle->status === DepartmentEvaluationCycle::STATUS_PENDING);
        $isActive = ($dCycle->status === DepartmentEvaluationCycle::STATUS_ACTIVE);
        $isCompleted = ($dCycle->status === DepartmentEvaluationCycle::STATUS_COMPLETED);
        $isClosed = ($dCycle->status === DepartmentEvaluationCycle::STATUS_CLOSED);
    ?>
        <!-- Agency Admin Personal Department Control Card -->
        <div class="card card-rmutt shadow-sm mb-4 border-2 <?= $isPending ? 'border-warning' : ($isActive ? 'border-success' : 'border-primary') ?>">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-5">🏢</span>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark"><?= Html::encode($myDepartment->name_th) ?></h5>
                        <small class="text-muted">รอบการประเมินปัจจุบัน: <?= Html::encode($activeCycle->name_th) ?></small>
                    </div>
                </div>
                <div>
                    <?= $dCycle->statusBadge ?>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-7">
                        <?php if ($isPending): ?>
                            <div class="p-3 bg-warning-subtle rounded-3 border border-warning mb-3">
                                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle-fill text-warning me-1"></i> รอบการประเมินของหน่วยงานยังไม่เปิด</h6>
                                <p class="small text-muted mb-0">
                                    ท่านสามารถเข้าไปตรวจสอบหรือปรับแต่งเกณฑ์ตัวชี้วัด (KPI) ในเมนู <strong>"จัดการแบบประเมิน"</strong> ให้เรียบร้อย 
                                    เมื่อพร้อมแล้ว ให้กดปุ่ม <strong>"เปิดรอบการประเมิน"</strong> ด้านขวา เพื่อล็อกแบบประเมินและให้บุคลากรเริ่มประเมินตนเอง
                                </p>
                            </div>
                        <?php elseif ($isActive): ?>
                            <div class="p-3 bg-success-subtle rounded-3 border border-success mb-3">
                                <h6 class="fw-bold text-success mb-1"><i class="bi bi-shield-lock-fill me-1"></i> เปิดรอบแล้ว - โครงสร้างแบบประเมินถูกล็อกถาวร</h6>
                                <p class="small text-dark mb-0">
                                    เปิดรอบเมื่อ: <strong><?= Yii::$app->formatter->asDatetime($dCycle->opened_at, 'php:d/m/Y H:i') ?> น.</strong>
                                    <?= $dCycle->opener ? ('โดย: ' . Html::encode($dCycle->opener->displayName)) : '' ?>
                                    <br><span class="text-danger fw-semibold"><i class="bi bi-lock-fill"></i> แบบประเมินถูกล็อกการแก้ไข เพื่อความเป็นธรรมต่อผู้รับการประเมิน</span>
                                </p>
                            </div>
                        <?php elseif ($isCompleted): ?>
                            <div class="p-3 bg-primary-subtle rounded-3 border border-primary mb-3">
                                <h6 class="fw-bold text-primary mb-1"><i class="bi bi-check-circle-fill me-1"></i> ประเมินครบ 100% แล้ว</h6>
                                <p class="small text-dark mb-0">บุคลากรทุกคนในหน่วยงานได้รับการประเมินครบถ้วนแล้ว พร้อมส่งข้อมูลสู่การพิจารณาส่วนกลาง</p>
                            </div>
                        <?php endif; ?>

                        <!-- Progress Bar -->
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="fw-semibold text-muted">ความคืบหน้าการประเมินของหน่วยงาน:</span>
                            <span class="fw-bold text-dark"><?= $myStatus['evalCount'] ?> / <?= $myStatus['totalStaff'] ?> คน (<?= $myStatus['progressPct'] ?>%)</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?= $myStatus['progressPct'] >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min(100, $myStatus['progressPct']) ?>%"></div>
                        </div>
                    </div>

                    <div class="col-md-5 text-md-end">
                        <div class="d-flex flex-column flex-sm-row justify-content-md-end gap-2">
                            <?= Html::a('<i class="bi bi-file-earmark-ruled me-1"></i> ดูเกณฑ์แบบประเมิน', ['/template-builder/index'], ['class' => 'btn btn-outline-secondary']) ?>
                            <?php if ($isPending): ?>
                                <?= Html::a('<i class="bi bi-play-circle-fill me-1"></i> เปิดรอบการประเมินของหน่วยงาน', ['department-open', 'cycle_id' => $activeCycle->id, 'department_id' => $myDepartment->id], [
                                    'class' => 'btn btn-success fw-bold shadow-sm px-3',
                                    'data-method' => 'post',
                                    'data-confirm' => "⚠️ คำเตือนสำคัญ:\nเมื่อเปิดรอบการประเมินสำหรับ {$myDepartment->name_th} แล้ว\nระบบจะทำการล็อกโครงสร้างแบบประเมินถาวร (ไม่อนุญาตให้แก้ไขอีกเพื่อความเป็นธรรมต่อผู้รับการประเมิน)\nและจะเปิดให้บุคลากรเข้าทำแบบประเมินตนเองได้ทันที\n\nท่านตรวจสอบความถูกต้องของแบบประเมินครบถ้วนแล้วและต้องการเปิดรอบการประเมินใช่หรือไม่?",
                                ]) ?>
                            <?php elseif ($isActive && $myStatus['progressPct'] >= 100): ?>
                                <?= Html::a('<i class="bi bi-lock-fill me-1"></i> ปิดรอบการประเมินประจำหน่วยงาน', ['department-close', 'cycle_id' => $activeCycle->id, 'department_id' => $myDepartment->id], [
                                    'class' => 'btn btn-primary fw-bold shadow-sm',
                                    'data-method' => 'post',
                                    'data-confirm' => 'ยืนยันการปิดรอบการประเมินสำหรับหน่วยงานนี้?',
                                ]) ?>
                            <?php else: ?>
                                <?= Html::a('<i class="bi bi-speedometer2 me-1"></i> ตรวจสอบการประเมิน', ['/monitor/index', 'dept_id' => $myDepartment->id], ['class' => 'btn btn-outline-primary']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Agency Sub-Divisions Progress Breakdown (For Agency Admin) -->
    <?php if (!$isCentral && !empty($subDivisionStatuses)): ?>
        <div class="card card-rmutt shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-diagram-3-fill text-primary me-2"></i>ความคืบหน้าการประเมินแยกตามฝ่าย/งานภายในหน่วยงาน
                    </h6>
                    <small class="text-muted">
                        การติดตามรายฝ่ายภายใต้ <?= Html::encode($myDepartment ? $myDepartment->name_th : '') ?>
                    </small>
                </div>
                <span class="badge bg-light text-secondary border">ทั้งหมด <?= count($subDivisionStatuses) ?> ฝ่าย</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>ชื่อฝ่าย / งาน</th>
                            <th class="text-center" style="width: 150px;">บุคลากรทั้งหมด</th>
                            <th class="text-center" style="width: 150px;">ประเมินเสร็จสิ้น</th>
                            <th style="width: 250px;">ความคืบหน้า</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subDivisionStatuses as $sIdx => $sds): ?>
                            <tr>
                                <td><?= $sIdx + 1 ?></td>
                                <td>
                                    <strong class="text-dark"><?= Html::encode($sds['department']->name_th) ?></strong>
                                    <small class="text-muted d-block"><?= Html::encode($sds['department']->code ?: '-') ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold"><?= $sds['staffCount'] ?></span> คน
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-success"><?= $sds['evalDone'] ?></span> คน
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center small mb-1">
                                        <span class="fw-semibold text-muted"><?= $sds['evalDone'] ?> / <?= $sds['staffCount'] ?> คน</span>
                                        <span class="fw-bold text-dark"><?= $sds['progressPct'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?= $sds['progressPct'] >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min(100, $sds['progressPct']) ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Real-time Department Cycle Status Monitor Table (Central Admin Only) -->
    <?php if ($isCentral && $activeCycle && !empty($deptStatuses)): ?>
        <div class="card card-rmutt shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-buildings-fill text-primary me-2"></i>สถานะการเปิดรอบและการประเมินรายหน่วยงาน
                    </h5>
                    <small class="text-muted">
                        รอบการประเมิน: <strong><?= Html::encode($activeCycle->name_th) ?></strong> &bull; ติดตามความพร้อมและการเปิดรอบของแต่ละคณะ/สำนัก/กอง
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php
                    $pendingCount = 0;
                    $activeCount = 0;
                    $completedCount = 0;
                    foreach ($deptStatuses as $ds) {
                        if ($ds['deptCycle']->status === DepartmentEvaluationCycle::STATUS_PENDING) $pendingCount++;
                        elseif ($ds['deptCycle']->status === DepartmentEvaluationCycle::STATUS_ACTIVE) $activeCount++;
                        elseif (in_array($ds['deptCycle']->status, [DepartmentEvaluationCycle::STATUS_COMPLETED, DepartmentEvaluationCycle::STATUS_CLOSED])) $completedCount++;
                    }
                    ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5">
                        <i class="bi bi-hourglass-split me-1"></i>ยังไม่เปิด <?= $pendingCount ?> แห่ง
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                        <i class="bi bi-play-circle-fill me-1"></i>เปิดแล้ว <?= $activeCount ?> แห่ง
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5">
                        <i class="bi bi-check-circle-fill me-1"></i>ครบ/ปิดรอบ <?= $completedCount ?> แห่ง
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>หน่วยงาน / สังกัด</th>
                            <th class="text-center" style="width: 170px;">สถานะรอบประเมิน</th>
                            <th style="width: 200px;">วันเวลาที่เปิดรอบ / ผู้เปิด</th>
                            <th class="text-center" style="width: 120px;">บุคลากร</th>
                            <th style="width: 220px;">ความคืบหน้าการประเมิน</th>
                            <th class="text-end pe-4" style="width: 180px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sNum = 1; foreach ($deptStatuses as $rId => $ds): 
                            $rDept = $ds['department'];
                            $dCycle = $ds['deptCycle'];
                            $isRowPending = ($dCycle->status === DepartmentEvaluationCycle::STATUS_PENDING);
                            $isRowActive = ($dCycle->status === DepartmentEvaluationCycle::STATUS_ACTIVE);
                            $isRowCompleted = ($dCycle->status === DepartmentEvaluationCycle::STATUS_COMPLETED);
                        ?>
                            <tr class="<?= ($myRootDeptId === $rDept->id) ? 'table-primary-subtle' : '' ?>">
                                <td class="text-center fw-bold text-muted"><?= $sNum++ ?></td>
                                <td>
                                    <strong class="text-dark d-block">
                                        <?= Html::encode($rDept->name_th) ?>
                                        <?php if ($myRootDeptId === $rDept->id): ?>
                                            <span class="badge bg-primary ms-1">หน่วยงานของคุณ</span>
                                        <?php endif; ?>
                                    </strong>
                                    <small class="text-muted">รหัส: <code><?= Html::encode($rDept->code ?: '-') ?></code></small>
                                </td>
                                <td class="text-center">
                                    <?= $dCycle->statusBadge ?>
                                </td>
                                <td class="small">
                                    <?php if ($dCycle->opened_at): ?>
                                        <div class="text-dark fw-semibold">
                                            <i class="bi bi-clock-history me-1 text-muted"></i><?= Yii::$app->formatter->asDatetime($dCycle->opened_at, 'php:d/m/Y H:i') ?> น.
                                        </div>
                                        <div class="text-muted">
                                            <?= $dCycle->opener ? ('โดย: ' . Html::encode($dCycle->opener->displayName)) : '-' ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted italic"><i class="bi bi-dash-circle me-1"></i>ยังไม่เปิดรอบ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-dark fs-6"><?= $ds['totalStaff'] ?></span>
                                    <span class="text-muted small">คน</span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center small mb-1">
                                        <span class="fw-semibold text-muted"><?= $ds['evalCount'] ?> / <?= $ds['totalStaff'] ?> คน</span>
                                        <span class="fw-bold text-dark"><?= $ds['progressPct'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?= $ds['progressPct'] >= 100 ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min(100, $ds['progressPct']) ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex align-items-center gap-1.5">
                                        <?= Html::a('<i class="bi bi-file-earmark-ruled" title="ดูแบบประเมิน"></i>', ['/template-builder/index', 'department_id' => $rDept->id], ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'ดูแบบประเมิน']) ?>
                                        
                                        <?php if ($isCentral || $myRootDeptId === $rDept->id): ?>
                                            <?php if ($isRowPending): ?>
                                                <?= Html::a('<i class="bi bi-play-circle-fill me-1"></i>เปิดรอบ', ['department-open', 'cycle_id' => $activeCycle->id, 'department_id' => $rDept->id], [
                                                    'class' => 'btn btn-sm btn-success fw-semibold shadow-sm',
                                                    'data-method' => 'post',
                                                    'data-confirm' => "⚠️ ยืนยันการเปิดรอบการประเมินสำหรับ {$rDept->name_th}?\nระบบจะล็อกโครงสร้างแบบประเมินถาวรทันที และเปิดให้บุคลากรเข้าทำแบบประเมินตนเอง",
                                                    'title' => 'เปิดรอบการประเมิน'
                                                ]) ?>
                                            <?php elseif ($isRowActive): ?>
                                                <?= Html::a('<i class="bi bi-lock me-1"></i>ปิดรอบ', ['department-close', 'cycle_id' => $activeCycle->id, 'department_id' => $rDept->id], [
                                                    'class' => 'btn btn-sm btn-outline-danger',
                                                    'data-method' => 'post',
                                                    'data-confirm' => "ยืนยันการปิดรอบการประเมินสำหรับ {$rDept->name_th}?",
                                                    'title' => 'ปิดรอบประเมิน'
                                                ]) ?>
                                            <?php else: ?>
                                                <?= Html::a('<i class="bi bi-eye"></i>', ['/monitor/index', 'dept_id' => $rDept->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'ดูผลประเมิน']) ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Master Cycles List (University-wide) -->
    <?php if ($isCentral): ?>
        <div class="card card-rmutt shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-calendar-range text-primary me-2"></i>ประวัติและกรอบรอบการประเมินภาพรวมมหาวิทยาลัย (Master Cycles)
                </h6>
                <span class="badge bg-light text-secondary border">ทั้งหมด <?= count($cycles) ?> รอบ</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>ชื่อรอบการประเมิน</th>
                            <th>ปีงบประมาณ / รอบ</th>
                            <th>ช่วงเวลาของรอบ</th>
                            <th>ช่วงประเมินตนเอง</th>
                            <th>ช่วงหัวหน้าประเมิน</th>
                            <th>สถานะ</th>
                            <th class="text-center" style="width: 180px;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cycles)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลรอบการประเมิน</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cycles as $idx => $cycle): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong class="text-dark"><?= Html::encode($cycle->name_th) ?></strong>
                                        <?php if ($cycle->description): ?>
                                            <small class="text-muted d-block"><?= Html::encode($cycle->description) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">ปี <?= $cycle->fiscal_year ?> รอบที่ <?= $cycle->cycle_number ?></span>
                                    </td>
                                    <td>
                                        <small><?= Yii::$app->formatter->asDate($cycle->period_start, 'php:d/m/Y') ?> - <?= Yii::$app->formatter->asDate($cycle->period_end, 'php:d/m/Y') ?></small>
                                    </td>
                                    <td>
                                        <small><?= Yii::$app->formatter->asDate($cycle->self_assessment_start, 'php:d/m/Y H:i') ?> - <br><?= Yii::$app->formatter->asDate($cycle->self_assessment_end, 'php:d/m/Y H:i') ?></small>
                                    </td>
                                    <td>
                                        <small><?= Yii::$app->formatter->asDate($cycle->supervisor_eval_start, 'php:d/m/Y H:i') ?> - <br><?= Yii::$app->formatter->asDate($cycle->supervisor_eval_end, 'php:d/m/Y H:i') ?></small>
                                    </td>
                                    <td><?= $cycle->statusLabel ?></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $cycle->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'แก้ไขกรอบรอบ']) ?>
                                            <?php if ($cycle->status === EvaluationCycle::STATUS_DRAFT): ?>
                                                <?= Html::a('<i class="bi bi-play-fill"></i> เปิดรอบ', ['set-active', 'id' => $cycle->id], [
                                                    'class' => 'btn btn-sm btn-outline-success',
                                                    'data-method' => 'post',
                                                    'data-confirm' => 'ยืนยันการเปิดรอบการประเมินมหาวิทยาลัยนี้?',
                                                    'title' => 'เปิดใช้งานรอบประเมิน'
                                                ]) ?>
                                            <?php elseif ($cycle->status === EvaluationCycle::STATUS_ACTIVE): ?>
                                                <?= Html::a('<i class="bi bi-stop-fill"></i> ปิดรอบ', ['close', 'id' => $cycle->id], [
                                                    'class' => 'btn btn-sm btn-outline-danger',
                                                    'data-method' => 'post',
                                                    'data-confirm' => 'ยืนยันการปิดรอบการประเมินมหาวิทยาลัยนี้?',
                                                    'title' => 'ปิดรอบประเมิน'
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
    <?php endif; ?>

</div>
