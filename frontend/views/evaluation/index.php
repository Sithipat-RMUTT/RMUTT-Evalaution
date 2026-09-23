<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;

/** @var yii\web\View $this */
/** @var common\models\Personnel $personnel */
/** @var common\models\EvaluationCycle $activeCycle */
/** @var common\models\Evaluation[] $myEvaluations */
/** @var common\models\Evaluation[] $teamEvaluations */
/** @var bool $isSupervisor */

$this->title = 'ระบบประเมินผลการปฏิบัติงาน';
?>

<div class="evaluation-index py-3">

    <!-- Header Banner -->
    <div class="eval-banner shadow-sm mb-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white text-primary rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 60px; height: 60px;">
                        <i class="bi bi-person-badge-fill fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-white fw-bold"><?= Html::encode($personnel->fullName) ?></h3>
                        <p class="mb-0 text-white-50">
                            <strong>ตำแหน่ง:</strong> <?= Html::encode($personnel->position->name_th) ?> (<?= Html::encode($personnel->position->level_label ?: '-') ?>) | 
                            <strong>สังกัด:</strong> <?= Html::encode($personnel->department->name_th) ?> | 
                            <span class="badge bg-light text-dark fw-semibold"><?= Html::encode($personnel->personnelType->name_th) ?></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5 col-xl-4 text-lg-end">
                <?php if ($activeCycle): ?>
                    <div class="eval-cycle-box bg-white text-dark rounded-3 p-2 px-3 text-start d-inline-block shadow-sm">
                        <div class="fw-bold text-primary mb-1" style="font-size: 0.88rem; line-height: 1.35;">
                            <i class="bi bi-clock-history me-1 text-primary"></i> <?= Html::encode($activeCycle->name_th) ?>
                        </div>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-calendar-event me-1 text-secondary"></i> สิ้นสุดประเมินตนเอง: <?= Yii::$app->formatter->asDate($activeCycle->self_assessment_end, 'php:d M Y H:i น.') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="badge bg-secondary p-2">ไม่มีรอบการประเมินที่เปิดใช้งาน</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Only show team tab if supervisor) -->
    <ul class="nav nav-tabs nav-tabs-rmutt" id="evalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="my-eval-tab" data-bs-toggle="tab" data-bs-target="#my-eval" type="button" role="tab">
                <i class="bi bi-person-fill me-1"></i> การประเมินของฉัน (ประเมินตนเอง)
                <span class="badge bg-primary ms-1"><?= count($myEvaluations) ?></span>
            </button>
        </li>
        <?php if ($isSupervisor): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="team-eval-tab" data-bs-toggle="tab" data-bs-target="#team-eval" type="button" role="tab">
                    <i class="bi bi-people-fill me-1"></i> ประเมินบุคลากรในความรับผิดชอบ (ในฐานะผู้ประเมิน)
                    <?php 
                    $pendingCount = count(array_filter($teamEvaluations, fn($e) => in_array($e->status, [Evaluation::STATUS_SUBMITTED, Evaluation::STATUS_SUPERVISOR_REVIEW])));
                    if ($pendingCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pendingCount ?> รอตรวจ</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-1"><?= count($teamEvaluations) ?></span>
                    <?php endif; ?>
                </button>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="evalTabsContent">
        
        <!-- TAB 1: My Evaluations -->
        <div class="tab-pane fade show active" id="my-eval" role="tabpanel">

            <?php if (empty($myEvaluations)): ?>
                <div class="card card-rmutt text-center p-5">
                    <i class="bi bi-journal-x text-muted display-3 mb-3"></i>
                    <h5 class="text-muted">ยังไม่มีรายการประเมินในขณะนี้</h5>
                    <p class="text-muted">เมื่อสำนักฯ เปิดรอบการประเมิน ระบบจะสร้างรายการประเมินให้ท่านโดยอัตโนมัติ</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($myEvaluations as $eval): ?>
                        <div class="col-12 mb-3">
                            <div class="card card-rmutt card-rmutt-highlight shadow-sm">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-lg-7">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <?= $eval->statusLabel ?>
                                                <span class="badge bg-light text-secondary border">
                                                    <?= Html::encode($eval->templateVersion->template->name_th) ?>
                                                </span>
                                            </div>
                                            <h4 class="fw-bold mb-1 text-dark"><?= Html::encode($eval->cycle->name_th) ?></h4>
                                            <p class="text-muted mb-2 small">
                                                <strong>ช่วงเวลาประเมิน:</strong> <?= Yii::$app->formatter->asDate($eval->cycle->period_start, 'php:d/m/Y') ?> ถึง <?= Yii::$app->formatter->asDate($eval->cycle->period_end, 'php:d/m/Y') ?> |
                                                <strong>ผู้บังคับบัญชาผู้ประเมิน:</strong> <?= $eval->evaluator ? Html::encode($eval->evaluator->fullName) : '<span class="text-warning">ยังไม่ได้ระบุ</span>' ?>
                                            </p>
                                            
                                            <?php if ($eval->status === Evaluation::STATUS_RETURNED && !empty($eval->return_reason)): ?>
                                                <div class="alert alert-danger py-2 px-3 mb-0 mt-2">
                                                    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> ข้อความแจ้งแก้ไขจากหัวหน้า:</strong>
                                                    <?= Html::encode($eval->return_reason) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                                            <?php if ($eval->isCompleted() && $eval->result && $eval->result->final_percentage !== null): ?>
                                                <div class="d-inline-block text-center me-3 p-2 rounded bg-light border">
                                                    <small class="text-muted d-block">คะแนนรวม</small>
                                                    <span class="fs-4 fw-bold text-primary"><?= number_format($eval->result->final_percentage, 2) ?>%</span>
                                                    <div><?= $eval->result->performanceBadge ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="btn-group">
                                                <?php if (in_array($eval->status, [Evaluation::STATUS_DRAFT, Evaluation::STATUS_SELF_ASSESSMENT, Evaluation::STATUS_RETURNED])): ?>
                                                    <?= Html::a('<i class="bi bi-pencil-square me-1"></i> กรอกแบบประเมินตนเอง', ['self-assess', 'id' => $eval->id], ['class' => 'btn btn-primary px-4 shadow-sm']) ?>
                                                <?php elseif ($eval->status === Evaluation::STATUS_COMPLETED): ?>
                                                    <?= Html::a('<i class="bi bi-eye-fill me-1"></i> ดูผลการประเมิน', ['view', 'id' => $eval->id], ['class' => 'btn btn-outline-primary px-3']) ?>
                                                    <?= Html::a('<i class="bi bi-pen-fill me-1"></i> ลงนามรับทราบผล', ['acknowledge', 'id' => $eval->id], [
                                                        'class' => 'btn btn-success px-3 shadow-sm',
                                                        'data-method' => 'post',
                                                        'data-confirm' => "ข้าพเจ้ายืนยันรับทราบผลการประเมินผลการปฏิบัติราชการ คะแนนสุทธิ: " . number_format($eval->result ? $eval->result->final_percentage : 0, 2) . "%\n\nต้องการบันทึกการรับทราบผลใช่หรือไม่?"
                                                    ]) ?>
                                                <?php else: ?>
                                                    <?= Html::a('<i class="bi bi-eye-fill me-1"></i> ดูผลการประเมิน', ['view', 'id' => $eval->id], ['class' => 'btn btn-outline-primary px-3']) ?>
                                                    <?= Html::a('<i class="bi bi-arrow-repeat me-1"></i> รีเซ็ตเพื่อลองใหม่', ['reset', 'id' => $eval->id], [
                                                        'class' => 'btn btn-outline-warning px-3',
                                                        'data-method' => 'post',
                                                        'data-confirm' => 'คุณต้องการรีเซ็ตแบบประเมินเพื่อเริ่มกรอกทดสอบใหม่อีกครั้งใช่หรือไม่?'
                                                    ]) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: Team Evaluations (Supervisor / Division Head) -->
        <?php if ($isSupervisor): ?>
            <div class="tab-pane fade" id="team-eval" role="tabpanel">
                
                <!-- Quick Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-rmutt p-3 border-start border-primary border-4 shadow-sm">
                            <div class="text-muted small">บุคลากรในความรับผิดชอบทั้งหมด</div>
                            <div class="fs-3 fw-bold text-dark"><?= count($teamEvaluations) ?> คน</div>
                            <small class="text-muted"><?= !empty($isDivisionHead) ? '🏢 ระดับฝ่าย (ทั้งฝ่าย)' : '🧑‍💼 ระดับงาน' ?></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-rmutt p-3 border-start border-warning border-4 shadow-sm">
                            <div class="text-muted small">รอตรวจประเมิน</div>
                            <div class="fs-3 fw-bold text-warning">
                                <?= count(array_filter($teamEvaluations, fn($e) => in_array($e->status, [Evaluation::STATUS_SUBMITTED_L1, Evaluation::STATUS_SUBMITTED_L2, Evaluation::STATUS_SUBMITTED, Evaluation::STATUS_SUPERVISOR_REVIEW]))) ?> คน
                            </div>
                            <small class="text-warning-emphasis">รอ L1: <?= count(array_filter($teamEvaluations, fn($e) => $e->status === Evaluation::STATUS_SUBMITTED_L1)) ?> | รอ L2: <?= count(array_filter($teamEvaluations, fn($e) => $e->status === Evaluation::STATUS_SUBMITTED_L2)) ?></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-rmutt p-3 border-start border-success border-4 shadow-sm">
                            <div class="text-muted small">ประเมินเสร็จสิ้น / รับทราบผล</div>
                            <div class="fs-3 fw-bold text-success">
                                <?= count(array_filter($teamEvaluations, fn($e) => in_array($e->status, [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED]))) ?> คน
                            </div>
                            <small class="text-success">ประเมินครบ 100%</small>
                        </div>
                    </div>
                </div>

                <div class="card card-rmutt shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                        <span class="fw-bold text-dark fs-6">
                            <i class="bi bi-list-check me-1 text-primary"></i> 
                            <?= !empty($isDivisionHead) ? 'รายชื่อบุคลากรทั้งหมดในฝ่ายพัฒนาระบบสารสนเทศ (หัวหน้าฝ่าย L2)' : 'รายชื่อบุคลากรในงานที่รับผิดชอบ (หัวหน้างาน L1)' ?>
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-eval align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>ชื่อ-สกุล</th>
                                    <th>ตำแหน่ง / ฝ่าย</th>
                                    <th>ประเภทบุคลากร</th>
                                    <th>สายการประเมิน</th>
                                    <th>สถานะ</th>
                                    <th>คะแนนสรุป</th>
                                    <th class="text-center" style="width: 170px;">การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($teamEvaluations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">ไม่พบบุคลากรในความรับผิดชอบที่ต้องประเมินในขณะนี้</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($teamEvaluations as $idx => $tEval): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= Html::encode($tEval->personnel->fullName) ?></div>
                                                <small class="text-muted">รหัส: <?= Html::encode($tEval->personnel->employee_code ?: '-') ?></small>
                                            </td>
                                            <td>
                                                <div><?= Html::encode($tEval->personnel->position->name_th) ?></div>
                                                <small class="text-muted"><?= Html::encode($tEval->personnel->department->name_th) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= Html::encode($tEval->personnel->personnelType->name_th) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <span class="text-muted">L1:</span> <?= $tEval->evaluatorL1 ? Html::encode($tEval->evaluatorL1->fullName) : '<span class="text-muted">-</span>' ?><br>
                                                    <span class="text-muted">L2:</span> <?= $tEval->evaluatorL2 ? Html::encode($tEval->evaluatorL2->fullName) : '<span class="text-muted">-</span>' ?>
                                                </div>
                                            </td>
                                            <td><?= $tEval->statusLabel ?></td>
                                            <td>
                                                <?php if ($tEval->isCompleted() && $tEval->result && $tEval->result->final_percentage !== null): ?>
                                                    <span class="fw-bold text-primary"><?= number_format($tEval->result->final_percentage, 2) ?>%</span>
                                                    <?= $tEval->result->performanceBadge ?>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">รอประเมินเสร็จสิ้น</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($tEval->status === Evaluation::STATUS_SUBMITTED_L1): ?>
                                                    <?php if (!empty($isDivisionHead)): ?>
                                                        <?= Html::a('<i class="bi bi-pencil-square me-1"></i> ตรวจขั้นต้น (L1)', ['supervisor-assess', 'id' => $tEval->id], ['class' => 'btn btn-sm btn-outline-warning shadow-sm']) ?>
                                                    <?php else: ?>
                                                        <?= Html::a('<i class="bi bi-check2-square me-1"></i> ตรวจประเมิน (L1)', ['supervisor-assess', 'id' => $tEval->id], ['class' => 'btn btn-sm btn-primary shadow-sm']) ?>
                                                    <?php endif; ?>
                                                <?php elseif ($tEval->status === Evaluation::STATUS_SUBMITTED_L2): ?>
                                                    <?php 
                                                        $isSameL1L2 = ($tEval->evaluator_l1_id && $tEval->evaluator_l2_id && $tEval->evaluator_l1_id === $tEval->evaluator_l2_id)
                                                            || ($tEval->evaluatorL1 && $tEval->evaluatorL2 && $tEval->evaluatorL1->fullName === $tEval->evaluatorL2->fullName);
                                                        $canAssessL2 = !empty($isDivisionHead) 
                                                            || ($tEval->evaluator_l2_id === $personnel->id)
                                                            || ($tEval->evaluatorL2 && $tEval->evaluatorL2->user_id === Yii::$app->user->id)
                                                            || ($isSameL1L2 && $tEval->evaluator_l1_id === $personnel->id)
                                                            || Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');
                                                    ?>
                                                    <?php if ($canAssessL2): ?>
                                                        <?= Html::a('<i class="bi bi-shield-check me-1"></i> ประเมินตัดสิน (L2)', ['supervisor-assess', 'id' => $tEval->id], ['class' => 'btn btn-sm btn-primary shadow-sm']) ?>
                                                    <?php else: ?>
                                                        <?= Html::a('<i class="bi bi-eye me-1"></i> ส่งต่อ L2 แล้ว', ['view', 'id' => $tEval->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                                    <?php endif; ?>
                                                <?php elseif (in_array($tEval->status, [Evaluation::STATUS_SUBMITTED, Evaluation::STATUS_SUPERVISOR_REVIEW, Evaluation::STATUS_EVALUATED])): ?>
                                                    <?= Html::a('<i class="bi bi-check2-square me-1"></i> ตรวจประเมิน', ['supervisor-assess', 'id' => $tEval->id], ['class' => 'btn btn-sm btn-primary shadow-sm']) ?>
                                                <?php elseif (in_array($tEval->status, [Evaluation::STATUS_COMPLETED, Evaluation::STATUS_ACKNOWLEDGED])): ?>
                                                    <?= Html::a('<i class="bi bi-eye me-1"></i> ดูผลสรุป', ['view', 'id' => $tEval->id], ['class' => 'btn btn-sm btn-outline-success']) ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">รอกรอกประเมินตนเอง</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
