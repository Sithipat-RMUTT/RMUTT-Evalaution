<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var bool $isGuest */
/** @var common\models\Personnel|null $personnel */
/** @var common\models\EvaluationCycle|null $activeCycle */
/** @var common\models\Evaluation|null $myEvaluation */
/** @var common\models\Evaluation[] $myEvaluations */
/** @var common\models\Evaluation[] $teamEvaluations */
/** @var common\models\Evaluation[] $pendingReviews */
/** @var bool $isSupervisor */

use common\models\Evaluation;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'หน้าหลัก - ระบบประเมินผลการปฏิบัติงาน มทร.ธัญบุรี';
?>

<div class="site-index py-2">

<?php if ($isGuest): ?>
    <!-- ====================================================================
         GUEST LANDING HERO
         ==================================================================== -->
    <div class="dash-profile-card text-center text-lg-start mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 mb-2">
                    <span class="dash-pill-type">
                        <i class="bi bi-award-fill text-warning"></i>
                        สำนักวิทยบริการและเทคโนโลยีสารสนเทศ (ARIT)
                    </span>
                </div>
                <h1 class="display-6 fw-bold mb-2 text-white">ระบบประเมินผลการปฏิบัติงานบุคลากร</h1>
                <p class="lead opacity-90 mb-4 text-white-50 fs-6">
                    มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี (RMUTT Evaluation System)<br>
                    รองรับแบบประเมินผลสัมฤทธิ์และสมรรถนะครบทั้ง ๔ ประเภทบุคลากร ตามประกาศเกณฑ์ สวส. มทร.ธัญบุรี
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <?= Html::a('<i class="bi bi-box-arrow-in-right me-2"></i> เข้าสู่ระบบเพื่อเริ่มการประเมิน', ['/site/login'], ['class' => 'btn btn-primary btn-lg fw-bold px-4 shadow']) ?>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <img src="<?= Yii::$app->request->baseUrl ?>/images/rmutt-logo.png" 
                     alt="RMUTT Seal" 
                     style="height: 150px; width: auto; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));">
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ====================================================================
         LOGGED-IN STAFF DASHBOARD
         ==================================================================== -->

    <!-- 1. Executive Profile Banner -->
    <div class="dash-profile-card">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="dash-profile-avatar">
                        <i class="bi bi-person-fill text-warning fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h3 mb-0 text-white fw-bold"><?= Html::encode($personnel ? $personnel->fullName : Yii::$app->user->identity->username) ?></h2>
                            <?php if ($personnel && $personnel->personnelType): ?>
                                <span class="dash-pill-type">
                                    <i class="bi bi-person-badge"></i>
                                    <?= Html::encode($personnel->personnelType->name_th) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($isSupervisor): ?>
                                <span class="dash-pill-supervisor">
                                    <i class="bi bi-award-fill"></i>
                                    ผู้ประเมิน (Supervisor)
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 text-white-50 small">
                            <?php if ($personnel): ?>
                                <span class="me-3"><i class="bi bi-briefcase me-1 text-white-50"></i> <strong>ตำแหน่ง:</strong> <?= Html::encode($personnel->position ? $personnel->position->name_th : '-') ?></span>
                                <span class="me-3"><i class="bi bi-building me-1 text-white-50"></i> <strong>สังกัด:</strong> <?= Html::encode($personnel->department ? $personnel->department->name_th : '-') ?></span>
                                <span><i class="bi bi-upc-scan me-1 text-white-50"></i> <strong>รหัส:</strong> <?= Html::encode($personnel->employee_code ?: '-') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?= Html::a('<i class="bi bi-archive-fill me-1"></i> ประวัติการประเมินทั้งหมด (' . count($myEvaluations) . ' รอบ)', ['/evaluation/index'], ['class' => 'btn btn-outline-light btn-sm px-3 shadow-sm']) ?>
            </div>
        </div>
    </div>

    <!-- 2. Supervisor Hub / Pending Tasks Queue -->
    <?php if ($isSupervisor): ?>
        <div class="dash-supervisor-queue shadow-sm">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-1 text-dark fw-bold">
                        <i class="bi bi-people-fill text-primary me-2"></i> 
                        งานกำกับดูแลแบบประเมินผู้ใต้บังคับบัญชา (Supervisor Hub)
                    </h5>
                    <div class="small">
                        <?php if (!empty($pendingReviews)): ?>
                            <span class="badge bg-warning text-dark fw-bold me-1"><i class="bi bi-exclamation-circle-fill me-1"></i> มีรายการรอตรวจประเมิน <?= count($pendingReviews) ?> ท่าน</span>
                            <span class="text-muted">โปรดตรวจสอบและลงผลคะแนนประเมิน</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="bi bi-check-circle-fill me-1"></i> ปัจจุบันไม่มีแบบประเมินค้างตรวจ</span>
                            <span class="text-muted">บุคลากรในสังกัดของท่านทั้งหมด <strong><?= count($teamEvaluations) ?></strong> ท่าน</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?= Html::a('จัดการแบบประเมินลูกทีม (' . count($teamEvaluations) . ' ท่าน) <i class="bi bi-arrow-right ms-1"></i>', ['/evaluation/index', '#' => 'team-eval'], ['class' => 'btn btn-sm btn-primary fw-bold px-3 shadow-sm']) ?>
            </div>

            <?php if (!empty($pendingReviews)): ?>
                <div class="table-responsive bg-white rounded-3 border">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>บุคลากรผู้รับการประเมิน</th>
                                <th>ตำแหน่ง / ฝ่าย</th>
                                <th>สถานะปัจจุบัน</th>
                                <th class="text-end" style="width: 130px;">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pendingReviews, 0, 4) as $idx => $pe): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong><?= Html::encode($pe->personnel->fullName) ?></strong>
                                        <small class="text-muted d-block"><?= Html::encode($pe->personnel->employee_code ?: '-') ?></small>
                                    </td>
                                    <td>
                                        <div><?= Html::encode($pe->personnel->position ? $pe->personnel->position->name_th : '-') ?></div>
                                        <small class="text-muted"><?= Html::encode($pe->personnel->department ? $pe->personnel->department->name_th : '-') ?></small>
                                    </td>
                                    <td><?= $pe->statusLabel ?></td>
                                    <td class="text-end">
                                        <?= Html::a('<i class="bi bi-pencil-fill me-1"></i> ตรวจประเมิน', ['/evaluation/supervisor-assess', 'id' => $pe->id], ['class' => 'btn btn-sm btn-warning text-dark shadow-sm fw-bold']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 3. Active Evaluation Cycle Container -->
    <?php if ($activeCycle): ?>
        <?php
        // Determine Stepper State
        $currStatus = $myEvaluation ? $myEvaluation->status : Evaluation::STATUS_SELF_ASSESSMENT;
        $isStep1Done = in_array($currStatus, [Evaluation::STATUS_SUBMITTED, Evaluation::STATUS_SUPERVISOR_REVIEW, Evaluation::STATUS_SUBMITTED_L1, Evaluation::STATUS_SUBMITTED_L2, Evaluation::STATUS_COMPLETED]);
        $isStep2Done = in_array($currStatus, [Evaluation::STATUS_SUBMITTED_L1, Evaluation::STATUS_SUBMITTED_L2, Evaluation::STATUS_COMPLETED]);
        $isStep3Done = in_array($currStatus, [Evaluation::STATUS_SUBMITTED_L2, Evaluation::STATUS_COMPLETED]);
        $isStep4Done = ($currStatus === Evaluation::STATUS_COMPLETED);

        $step1Class = $isStep1Done ? 'is-done' : 'is-active';
        $step2Class = $isStep2Done ? 'is-done' : ($isStep1Done && !$isStep2Done ? 'is-active' : '');
        $step3Class = $isStep3Done ? 'is-done' : ($isStep2Done && !$isStep3Done ? 'is-active' : '');
        $step4Class = $isStep4Done ? 'is-done' : ($isStep3Done && !$isStep4Done ? 'is-active' : '');
        ?>

        <div class="dash-cycle-card">
            <div class="cycle-header">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 fw-semibold">
                        <i class="bi bi-broadcast me-1"></i> รอบการประเมินที่เปิดใช้งาน
                    </span>
                    <h3 class="h4 fw-bold mb-1 text-dark"><?= Html::encode($activeCycle->name_th) ?></h3>
                    <div class="text-muted small">
                        <i class="bi bi-calendar3 me-1"></i>
                        รอบการประเมิน: รอบที่ <?= Html::encode($activeCycle->cycle_number . '/' . $activeCycle->fiscal_year) ?> | 
                        <i class="bi bi-clock me-1 ms-2"></i>
                        สิ้นสุดประเมินตนเอง: <strong><?= Yii::$app->formatter->asDate($activeCycle->self_assessment_end, 'php:d M Y H:i น.') ?></strong>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($myEvaluation): ?>
                        <?= $myEvaluation->statusLabel ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4-Stage Stepper -->
            <div class="dash-stepper">
                <div class="dash-step-item <?= $step1Class ?>">
                    <div class="dash-step-circle">
                        <i class="bi <?= $isStep1Done ? 'bi-check-lg' : 'bi-pencil-fill' ?>"></i>
                    </div>
                    <div class="dash-step-title">๑. ประเมินตนเอง</div>
                    <div class="dash-step-sub"><?= $isStep1Done ? 'ส่งเรียบร้อย' : 'อยู่ระหว่างดำเนินการ' ?></div>
                </div>

                <div class="dash-step-item <?= $step2Class ?>">
                    <div class="dash-step-circle">
                        <i class="bi <?= $isStep2Done ? 'bi-check-lg' : 'bi-person-check-fill' ?>"></i>
                    </div>
                    <div class="dash-step-title">๒. ผู้ประเมินชั้นต้น (L1)</div>
                    <div class="dash-step-sub"><?= $isStep2Done ? 'ตรวจแล้ว' : ($isStep1Done ? 'รอตรวจ' : 'รอดำเนินการ') ?></div>
                </div>

                <div class="dash-step-item <?= $step3Class ?>">
                    <div class="dash-step-circle">
                        <i class="bi <?= $isStep3Done ? 'bi-check-lg' : 'bi-shield-check' ?>"></i>
                    </div>
                    <div class="dash-step-title">๓. ผู้ประเมินเหนือขึ้นไป (L2)</div>
                    <div class="dash-step-sub"><?= $isStep3Done ? 'ตรวจแล้ว' : ($isStep2Done ? 'รอตรวจ' : 'รอดำเนินการ') ?></div>
                </div>

                <div class="dash-step-item <?= $step4Class ?>">
                    <div class="dash-step-circle">
                        <i class="bi <?= $isStep4Done ? 'bi-check-lg' : 'bi-award-fill' ?>"></i>
                    </div>
                    <div class="dash-step-title">๔. ประกาศผลคะแนน</div>
                    <div class="dash-step-sub"><?= $isStep4Done ? 'เสร็จสมบูรณ์' : 'รอดำเนินการ' ?></div>
                </div>
            </div>

            <!-- Result Summary Card if Completed -->
            <?php if ($myEvaluation && $myEvaluation->isCompleted() && $myEvaluation->result): ?>
                <div class="dash-score-card">
                    <div class="row align-items-center g-3">
                        <div class="col-md-4 text-center text-md-start border-end">
                            <div class="text-success small fw-bold mb-1">ผลคะแนนรวมสุทธิ (Final Net Score)</div>
                            <div class="dash-score-badge">
                                <?= number_format((float)$myEvaluation->result->final_percentage, 2) ?><span class="fs-4 fw-bold">%</span>
                            </div>
                            <div class="mt-2"><?= $myEvaluation->result->performanceBadge ?></div>
                        </div>
                        <div class="col-md-5">
                            <div class="small fw-bold text-dark mb-2">สัดส่วนคะแนนตามเกณฑ์ สวส. มทร.ธัญบุรี:</div>
                            <div class="d-flex justify-content-between small text-dark mb-1">
                                <span><i class="bi bi-check2-circle text-success me-1"></i> ด้านผลสัมฤทธิ์ของงาน:</span>
                                <strong class="text-dark"><?= number_format((float)($myEvaluation->result->supervisor_performance_score ?? $myEvaluation->result->self_performance_score ?? 0), 2) ?> คะแนน</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-dark mb-1">
                                <span><i class="bi bi-check2-circle text-info me-1"></i> ด้านสมรรถนะ / พฤติกรรม:</span>
                                <strong class="text-dark"><?= number_format((float)($myEvaluation->result->supervisor_competency_score ?? $myEvaluation->result->self_competency_score ?? 0), 2) ?> คะแนน</strong>
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end text-center">
                            <?= Html::a('<i class="bi bi-file-earmark-text-fill me-1"></i> ดูรายงานสรุปคะแนน', ['/evaluation/view', 'id' => $myEvaluation->id], ['class' => 'btn btn-success fw-bold px-3 py-2 shadow-sm']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Button Bar -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
                <div class="small text-muted">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    <?php if (!$myEvaluation || $myEvaluation->status === Evaluation::STATUS_SELF_ASSESSMENT): ?>
                        กรุณากรอกภาระงาน คะแนนประเมินตนเอง และแนบหลักฐานให้ครบถ้วนก่อนส่งให้หัวหน้าตรวจ
                    <?php elseif (in_array($myEvaluation->status, [Evaluation::STATUS_SUBMITTED, Evaluation::STATUS_SUPERVISOR_REVIEW, Evaluation::STATUS_SUBMITTED_L1])): ?>
                        แบบประเมินของท่านถูกส่งเข้าสู่ระบบแล้ว อยู่ระหว่างการพิจารณาของผู้บังคับบัญชา
                    <?php else: ?>
                        การประเมินรอบนี้เสร็จสมบูรณ์เรียบร้อยแล้ว
                    <?php endif; ?>
                </div>

                <div>
                    <?php if (!$myEvaluation || $myEvaluation->status === Evaluation::STATUS_SELF_ASSESSMENT || $myEvaluation->status === Evaluation::STATUS_RETURNED): ?>
                        <?= Html::a('<i class="bi bi-pencil-square me-2"></i> เข้าทำแบบประเมินตนเอง', ['/evaluation/self-assess', 'id' => $myEvaluation ? $myEvaluation->id : 0], ['class' => 'btn btn-primary btn-lg fw-bold px-4 shadow']) ?>
                    <?php else: ?>
                        <?= Html::a('<i class="bi bi-eye me-2"></i> ตรวจดูแบบประเมินของฉัน', ['/evaluation/view', 'id' => $myEvaluation->id], ['class' => 'btn btn-primary btn-lg fw-bold px-4 shadow-sm']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="card card-rmutt p-5 text-center text-muted">
            <i class="bi bi-calendar-x display-4 mb-3 text-secondary"></i>
            <h5>ขณะนี้ยังไม่มีรอบการประเมินที่เปิดใช้งาน</h5>
            <p class="small mb-0">เมื่อกองบริหารงานบุคคลหรือสำนักฯ ประกาศเปิดรอบการประเมิน ระบบจะแสดงแบบประเมินให้ท่านโดยอัตโนมัติ</p>
        </div>
    <?php endif; ?>

<?php endif; ?>

    <!-- ====================================================================
         EVALUATION CRITERIA & GUIDELINES CARDS (HIGH-CONTRAST & CLEAR)
         ==================================================================== -->
    <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
        <h5 class="fw-bold text-dark mb-0">
            <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i> 
            โครงสร้างแบบประเมินตามประกาศเกณฑ์ สวส. มทร.ธัญบุรี
        </h5>
        <small class="text-muted">เกณฑ์มาตรฐานรอบการประเมินปี ๒๕๖๙</small>
    </div>

    <div class="row g-3">
        <!-- 1. Civil Servant -->
        <div class="col-md-6 col-lg-3">
            <div class="dash-guide-card border-top border-4 border-primary">
                <span class="dash-guide-pill bg-primary-subtle text-primary">สัดส่วน ๗๐ : ๓๐</span>
                <h6 class="fw-bold text-primary mb-2">๑. ข้าราชการพลเรือน</h6>
                <div class="small text-secondary mb-2">
                    <strong>แบบที่ ๒: ผลสัมฤทธิ์ของงาน (๗๐%)</strong>
                    <div class="text-muted ms-2">• ภาระงานหลัก PDCA 1-5 (น้ำหนัก ๘๐%)</div>
                    <div class="text-muted ms-2">• นโยบายสำนักฯ และ มหาวิทยาลัย (๑๕%)</div>
                    <div class="text-muted ms-2">• คู่มือปฏิบัติงาน / ผลงานวิชาการ (๕%)</div>
                </div>
                <div class="small text-secondary">
                    <strong>แบบที่ ๓: สมรรถนะ (๓๐%)</strong>
                    <div class="text-muted ms-2">• สมรรถนะหลัก 4 ด้าน + ประจำสายงาน 3 ด้าน</div>
                </div>
            </div>
        </div>

        <!-- 2. University Employee -->
        <div class="col-md-6 col-lg-3">
            <div class="dash-guide-card border-top border-4 border-info">
                <span class="dash-guide-pill bg-info-subtle text-info">สัดส่วน ๗๐ : ๓๐</span>
                <h6 class="fw-bold text-info mb-2">๒. พนักงานมหาวิทยาลัย</h6>
                <div class="small text-secondary mb-2">
                    <strong>แบบที่ ๒: ผลสัมฤทธิ์ของงาน (๗๐%)</strong>
                    <div class="text-muted ms-2">• ภาระงานหลัก PDCA 1-5 (น้ำหนัก ๘๐%)</div>
                    <div class="text-muted ms-2">• นโยบายสำนักฯ และ มหาวิทยาลัย (๑๕%)</div>
                    <div class="text-muted ms-2">• คู่มือปฏิบัติงาน / ผลงานวิชาการ (๕%)</div>
                </div>
                <div class="small text-secondary">
                    <strong>แบบที่ ๓: สมรรถนะ (๓๐%)</strong>
                    <div class="text-muted ms-2">• สมรรถนะหลัก 4 ด้าน + ประจำสายงาน 3 ด้าน</div>
                </div>
            </div>
        </div>

        <!-- 3. Government Employee -->
        <div class="col-md-6 col-lg-3">
            <div class="dash-guide-card border-top border-4 border-secondary">
                <span class="dash-guide-pill bg-secondary-subtle text-secondary">สัดส่วน ๗๐ : ๓๐</span>
                <h6 class="fw-bold text-secondary mb-2">๓. พนักงานราชการทั่วไป</h6>
                <div class="small text-secondary mb-2">
                    <strong>ส่วนที่ ๒: ผลสัมฤทธิ์ของงาน (๗๐%)</strong>
                    <div class="text-muted ms-2">• ภาระงานหลัก ๔ ปัจจัย (น้ำหนัก ๘๐%)</div>
                    <div class="text-muted ms-2">• ภาระงานรอง ๑๐ ข้อ (น้ำหนัก ๒๐%)</div>
                </div>
                <div class="small text-secondary">
                    <strong>ส่วนที่ ๓: พฤติกรรม / สมรรถนะ (๓๐%)</strong>
                    <div class="text-muted ms-2">• ๕ สมรรถนะพฤติกรรมมาตรฐาน</div>
                </div>
            </div>
        </div>

        <!-- 4. Special Revenue Employee -->
        <div class="col-md-6 col-lg-3">
            <div class="dash-guide-card border-top border-4 border-success">
                <span class="dash-guide-pill bg-success-subtle text-success">สัดส่วน ๕๕ : ๔๕</span>
                <h6 class="fw-bold text-success mb-2">๔. พนักงานพิเศษเงินรายได้</h6>
                <div class="small text-secondary mb-2">
                    <strong>ด้านที่ ๑: ผลงานตามภาระหน้าที่ (๕๕%)</strong>
                    <div class="text-muted ms-2">• 5 ปัจจัยหลัก (50 คะแนน) + งานรอง (5 คะแนน)</div>
                </div>
                <div class="small text-secondary">
                    <strong>ด้านที่ ๒: คุณลักษณะการทำงาน (๔๕%)</strong>
                    <div class="text-muted ms-2">• 7 ปัจจัยคุณลักษณะ (วินัย, ความรับผิดชอบ ฯลฯ)</div>
                    <div class="text-muted ms-2">• เกณฑ์ตัดสิน: &ge;๗๐% พิจารณาจ้างต่อ</div>
                </div>
            </div>
        </div>
    </div>

</div>

