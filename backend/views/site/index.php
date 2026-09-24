<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;
use common\models\EvaluationCycle;

/** @var yii\web\View $this */
/** @var bool|null $isSuperAdmin */
/** @var common\models\Department|null $myDepartment */
/** @var common\models\EvaluationCycle[]|null $cycles */
/** @var common\models\EvaluationCycle|null $selectedCycle */
/** @var common\models\EvaluationCycle|null $activeCycle */
/** @var int|null $cycleId */
/** @var common\models\PersonnelType[]|null $personnelTypes */
/** @var int|null $typeId */
/** @var common\models\Department[]|null $departments */
/** @var int|null $filterDeptId */
/** @var int|null $totalPersonnel */
/** @var int|null $totalSupervisors */
/** @var int|null $evaluatedCount */
/** @var float|null $evaluationRate */
/** @var array|null $statusCounts */
/** @var array|null $gradeCounts */
/** @var array|null $gradePcts */
/** @var array|null $quotaCaps */
/** @var float|null $avgScore */
/** @var float|null $avgKpi */
/** @var float|null $avgComp */
/** @var string|null $orgTier */
/** @var string|null $orgTierBadge */
/** @var array|null $quotaStatus */
/** @var int|null $excellentCount */
/** @var float|null $excellentPct */
/** @var int|null $veryGoodCount */
/** @var float|null $veryGoodPct */
/** @var int|null $topTalentCount */
/** @var float|null $topTalentPct */
/** @var array|null $atRiskPersonnel */
/** @var int|null $atRiskCount */
/** @var float|null $atRiskPct */
/** @var array|null $topPerformers */
/** @var array|null $deptBenchmark */
/** @var array|null $deptProgress */
/** @var array|null $competencyGaps */
/** @var array|null $recentEvaluations */

$this->title = 'แผงควบคุมผลสัมฤทธิ์และสนับสนุนการตัดสินใจของผู้บริหาร - มทร.ธัญบุรี';

// Safe fallbacks for resilient rendering
$isSuperAdmin = $isSuperAdmin ?? true;
$myDepartment = $myDepartment ?? null;
$cycles = $cycles ?? [];
$selectedCycle = $selectedCycle ?? $activeCycle ?? null;
$cycleId = $cycleId ?? ($selectedCycle ? $selectedCycle->id : null);
$personnelTypes = $personnelTypes ?? [];
$typeId = $typeId ?? null;
$departments = $departments ?? [];
$filterDeptId = $filterDeptId ?? null;
$totalPersonnel = $totalPersonnel ?? 0;
$totalSupervisors = $totalSupervisors ?? 0;
$evaluatedCount = $evaluatedCount ?? ($statusCounts['completed'] ?? 0);
$evaluationRate = $evaluationRate ?? ($totalPersonnel > 0 ? round(($evaluatedCount / $totalPersonnel) * 100, 1) : 0);
$statusCounts = $statusCounts ?? [];
$gradeCounts = $gradeCounts ?? ['ดีเด่น' => 0, 'ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0, 'ต้องปรับปรุง' => 0];
$gradePcts = $gradePcts ?? ['ดีเด่น' => 0, 'ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0, 'ต้องปรับปรุง' => 0];
$quotaCaps = $quotaCaps ?? ['ดีเด่น' => 15.0, 'ดีมาก' => 35.0, 'ดี' => 35.0, 'พอใช้' => 10.0, 'ต้องปรับปรุง' => 5.0];
$avgScore = $avgScore ?? 0;
$avgKpi = $avgKpi ?? 0;
$avgComp = $avgComp ?? 0;
$orgTier = $orgTier ?? 'ดี';
$orgTierBadge = $orgTierBadge ?? 'bg-info text-dark';
$quotaStatus = $quotaStatus ?? [
    'is_over_quota' => false,
    'excellent_count' => ($gradeCounts['ดีเด่น'] ?? 0),
    'excellent_pct' => ($gradePcts['ดีเด่น'] ?? 0),
    'ceiling_pct' => 15.0,
    'message' => 'สัดส่วนกลุ่มผลงานดีเด่นสอดคล้องกับกรอบวงเงินงบประมาณเลื่อนเงินเดือน (≤15%)',
];
$excellentCount = $excellentCount ?? ($gradeCounts['ดีเด่น'] ?? 0);
$excellentPct = $excellentPct ?? ($gradePcts['ดีเด่น'] ?? 0);
$veryGoodCount = $veryGoodCount ?? ($gradeCounts['ดีมาก'] ?? 0);
$veryGoodPct = $veryGoodPct ?? ($gradePcts['ดีมาก'] ?? 0);
$topTalentCount = $topTalentCount ?? ($excellentCount + $veryGoodCount);
$topTalentPct = $topTalentPct ?? round($excellentPct + $veryGoodPct, 1);
$atRiskPersonnel = $atRiskPersonnel ?? [];
$atRiskCount = $atRiskCount ?? count($atRiskPersonnel);
$atRiskPct = $atRiskPct ?? ($evaluatedCount > 0 ? round(($atRiskCount / $evaluatedCount) * 100, 1) : 0);
$topPerformers = $topPerformers ?? [];
$deptBenchmark = $deptBenchmark ?? [];
$deptProgress = $deptProgress ?? [];
$competencyGaps = $competencyGaps ?? [];
$recentEvaluations = $recentEvaluations ?? [];
?>

<div class="site-index py-2">

    <!-- Section 1: Executive Portal Header & Strategic Scope -->
    <div class="card card-rmutt border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-navy text-white px-2.5 py-1 small fw-semibold" style="background-color: #07152b;">
                            <i class="bi bi-shield-shaded text-warning me-1"></i> HR Executive Decision Portal
                        </span>
                        <?php if ($selectedCycle): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 small fw-semibold">
                                <i class="bi bi-calendar2-check me-1"></i> <?= Html::encode($selectedCycle->name_th) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!$isSuperAdmin && $myDepartment): ?>
                            <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1 small">
                                <i class="bi bi-building me-1"></i> ฝ่ายสังกัด: <?= Html::encode($myDepartment->name_th) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 small">
                                <i class="bi bi-globe me-1"></i> ข้อมูลภาพรวมมหาวิทยาลัย
                            </span>
                        <?php endif; ?>
                    </div>
                    <h2 class="h3 fw-bold mb-1 text-dark">
                        แผงควบคุมผลสัมฤทธิ์และสนับสนุนการตัดสินใจของผู้บริหาร
                    </h2>
                    <p class="text-muted small mb-0">
                        การวิเคราะห์ผลสัมฤทธิ์เชิงยุทธศาสตร์ กรอบโควตาเลื่อนขั้นเงินเดือน และช่องว่างสมรรถนะบุคลากร มทร.ธัญบุรี
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?= Html::a('<i class="bi bi-ui-checks-grid me-1"></i> ติดตามแบบประเมิน', ['/monitor/index'], ['class' => 'btn btn-outline-primary shadow-sm fw-semibold btn-sm']) ?>
                    <?= Html::a('<i class="bi bi-bar-chart-line-fill me-1"></i> รายงานสรุป/คะแนน', ['/report/index'], ['class' => 'btn btn-outline-secondary shadow-sm fw-semibold btn-sm']) ?>
                    <?php if ($isSuperAdmin): ?>
                        <?= Html::a('<i class="bi bi-gear-fill me-1"></i> รอบประเมิน', ['/cycle/index'], ['class' => 'btn btn-outline-dark shadow-sm btn-sm']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Executive Filter Toolbar (GET Form) -->
            <form method="get" action="<?= Url::to(['/site/index']) ?>" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted fw-bold mb-1"><i class="bi bi-calendar3 me-1"></i> เลือกรอบการประเมิน</label>
                    <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($cycles as $c): ?>
                            <option value="<?= $c->id ?>" <?= $c->id == $cycleId ? 'selected' : '' ?>>
                                ปีงบ <?= $c->fiscal_year ?> รอบที่ <?= $c->cycle_number ?> (<?= Html::encode(mb_substr($c->name_th, 0, 32)) ?>...)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 col-lg-3">
                    <label class="form-label small text-muted fw-bold mb-1"><i class="bi bi-person-badge me-1"></i> ประเภทบุคลากร</label>
                    <select name="type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- บุคลากรทุกประเภท --</option>
                        <?php foreach ($personnelTypes as $pt): ?>
                            <option value="<?= $pt->id ?>" <?= $pt->id == $typeId ? 'selected' : '' ?>>
                                <?= Html::encode($pt->name_th) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!empty($departments)): ?>
                    <div class="col-md-3 col-lg-3">
                        <label class="form-label small text-muted fw-bold mb-1"><i class="bi bi-diagram-3 me-1"></i> หน่วยงาน / ฝ่าย</label>
                        <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- ทุกหน่วยงานในสิทธิ์ --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d->id ?>" <?= $d->id == $filterDeptId ? 'selected' : '' ?>>
                                    <?= Html::encode($d->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-2 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3 flex-fill">
                        <i class="bi bi-funnel-fill me-1"></i> วิเคราะห์ผล
                    </button>
                    <a href="<?= Url::to(['/site/index']) ?>" class="btn btn-sm btn-outline-secondary" title="ล้างตัวกรอง">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Section 2: Executive Budget Quota & Grade Inflation Notification Banner -->
    <?php if ($quotaStatus['is_over_quota']): ?>
        <div class="alert alert-warning border-2 border-warning shadow-sm d-flex align-items-center mb-4 rounded-3 p-3">
            <div class="bg-warning text-dark rounded-circle p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                    <span>ข้อสังเกตเชิงบริหาร: สัดส่วนกลุ่มผลงาน "ดีเด่น" เกินกรอบวงเงินงบประมาณเลื่อนเงินเดือน</span>
                    <span class="badge bg-danger">เกรดเฟ้อ (Grade Inflation Alert)</span>
                </div>
                <div class="small text-muted mt-1">
                    สัดส่วนผู้ที่ได้ผลการประเมินระดับดีเด่น (≥95%) คิดเป็น <strong><?= $quotaStatus['excellent_pct'] ?>%</strong> (จำนวน <?= $quotaStatus['excellent_count'] ?> คน) ซึ่งสูงกว่าเพดานกรอบโควตางบประมาณเลื่อนขั้นเงินเดือนปกติ (เพดานไม่เกิน <strong>15.0%</strong>) 
                    แนะนำให้ผู้บริหารและคณะกรรมการกลั่นกรองทบทวนการตัดเกรดของหน่วยงานก่อนลงนามอนุมัติเลื่อนเงินเดือน
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success border-2 border-success-subtle shadow-sm d-flex align-items-center mb-4 rounded-3 p-3 bg-success-subtle">
            <div class="bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="bi bi-shield-check fs-4"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold text-success fs-6">
                    สถานะกรอบวงเงินงบประมาณ: การกระจายตัวของผลการประเมินสอดคล้องกับกรอบวงเงินเลื่อนเงินเดือน
                </div>
                <div class="small text-muted mt-1">
                    สัดส่วนผลงานระดับดีเด่นอยู่ที่ <strong><?= $quotaStatus['excellent_pct'] ?>%</strong> (จำนวน <?= $quotaStatus['excellent_count'] ?> คน) ซึ่งอยู่ภายใต้เพดานกรอบงบประมาณเลื่อนขั้นเงินเดือน (เพดานไม่เกิน <strong>15.0%</strong>) ฐานข้อมูลพร้อมสำหรับการพิจารณาผลตอบแทน
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section 3: High-Impact Strategic KPI Cards (4 Outcome Pillars) -->
    <div class="row g-3 mb-4">

        <!-- 1. Org Overall Performance Score -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-primary h-100">
                <i class="bi bi-award-fill text-primary stat-icon"></i>
                <div class="text-muted small fw-bold">คะแนนเฉลี่ยผลสัมฤทธิ์องค์กร</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="display-6 fw-bold text-dark"><?= number_format($avgScore, 2) ?>%</span>
                    <span class="badge <?= $orgTierBadge ?> px-2 py-1 small fw-semibold"><?= $orgTier ?></span>
                </div>
                <div class="mt-2 text-muted small border-top pt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>ภารกิจหลัก (KPI):</span>
                        <strong class="text-dark"><?= number_format($avgKpi, 2) ?>%</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>สมรรถนะ (Competency):</span>
                        <strong class="text-dark"><?= number_format($avgComp, 2) ?>%</strong>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="bi bi-flag-fill text-primary me-1"></i> เกณฑ์เป้าหมายองค์กร: <strong>≥ 80.0%</strong>
                </div>
            </div>
        </div>

        <!-- 2. Merit Quota & Top Talents -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-success h-100">
                <i class="bi bi-star-fill text-warning stat-icon"></i>
                <div class="text-muted small fw-bold">กลุ่มดาวเด่น & โควตาเลื่อนขั้น</div>
                <div class="display-6 fw-bold text-success mt-1">
                    <?= number_format($topTalentCount) ?> <span class="fs-6 fw-normal text-muted">คน (<?= $topTalentPct ?>%)</span>
                </div>
                <div class="mt-2 border-top pt-2 small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span>ระดับดีเด่น (≥95%):</span>
                        <strong class="<?= $excellentPct > 15.0 ? 'text-danger' : 'text-success' ?>"><?= $excellentCount ?> คน (<?= $excellentPct ?>%)</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>ระดับดีมาก (85-94%):</span>
                        <strong class="text-dark"><?= $veryGoodCount ?> คน (<?= $veryGoodPct ?>%)</strong>
                    </div>
                </div>
                <div class="mt-2 small">
                    <?php if ($excellentPct > 15.0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-exclamation-circle me-1"></i> เกินเพดานกรอบโควตา 15%</span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i> อยู่ในกรอบโควตางบประมาณ</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. At-Risk / Underperformers -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-danger h-100">
                <i class="bi bi-shield-slash-fill text-danger stat-icon"></i>
                <div class="text-muted small fw-bold">กลุ่มที่ต้องพัฒนาเร่งด่วน (At-Risk)</div>
                <div class="display-6 fw-bold text-danger mt-1">
                    <?= number_format($atRiskCount) ?> <span class="fs-6 fw-normal text-muted">คน (<?= $atRiskPct ?>%)</span>
                </div>
                <div class="mt-2 border-top pt-2 small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span>คะแนนต่ำกว่า 70%:</span>
                        <strong class="text-danger"><?= $atRiskCount ?> คน</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>ระดับพอใช้ / ปรับปรุง:</span>
                        <strong class="text-danger"><?= ($gradeCounts['พอใช้'] ?? 0) + ($gradeCounts['ต้องปรับปรุง'] ?? 0) ?> คน</strong>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">
                        <i class="bi bi-lightning-charge-fill me-1"></i> ต้องทำแผน PIP เร่งด่วน
                    </span>
                </div>
            </div>
        </div>

        <!-- 4. Decision Readiness & Coverage -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card border-top border-4 border-info h-100">
                <i class="bi bi-clipboard2-check-fill text-info stat-icon"></i>
                <div class="text-muted small fw-bold">ความพร้อมการตัดสินใจรอบนี้</div>
                <div class="display-6 fw-bold text-dark mt-1">
                    <?= number_format($evaluatedCount) ?> <span class="fs-6 fw-normal text-muted">/ <?= number_format($totalPersonnel ?: $evaluatedCount) ?> คน</span>
                </div>
                <div class="progress mt-2" style="height: 7px;">
                    <div class="progress-bar bg-info" style="width: <?= $evaluationRate ?>%"></div>
                </div>
                <div class="mt-2 text-muted small d-flex justify-content-between border-top pt-2">
                    <span>ประเมินเสร็จแล้ว:</span>
                    <strong class="text-primary"><?= $evaluationRate ?>%</strong>
                </div>
                <div class="mt-2 small text-muted">
                    <i class="bi bi-people me-1"></i> ผู้ประเมินทั้งหมด: <strong><?= $totalSupervisors ?></strong> ท่าน
                </div>
            </div>
        </div>

    </div>

    <!-- Section 4: Visual Decision Analytics (Charts) -->
    <div class="row g-3 mb-4">

        <!-- Chart 1: Grade Curve vs Salary Merit Quota Caps -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="fw-bold text-dark"><i class="bi bi-bar-chart-steps text-primary me-2"></i> สัดส่วนผลการประเมินจริง เทียบกรอบโควตาเลื่อนเงินเดือน</div>
                        <small class="text-muted">เปรียบเทียบสัดส่วนผลงานจริง (%) กับเพดานกรอบวงเงินงบประมาณเลื่อนเงินเดือน</small>
                    </div>
                    <span class="badge bg-light text-muted border">Merit Quota Benchmark</span>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div style="height: 290px; width: 100%;">
                        <canvas id="chartQuotaBenchmark"></canvas>
                    </div>
                    <div class="mt-3 p-2.5 bg-light rounded-3 small text-muted border">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> <strong>เกณฑ์มาตรฐาน ก.พ.อ. / มทร.ธัญบุรี:</strong> กำหนดกรอบกลุ่ม <em>ดีเด่น</em> ไม่เกิน 15% เพื่อป้องกันปัญหาเกรดเฟ้อ และให้สอดคล้องกับกรอบวงเงินเลื่อนเงินเดือนร้อยละที่ได้รับจัดสรร
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Cross-Department Performance Benchmark -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="fw-bold text-dark"><i class="bi bi-trophy-fill text-warning me-2"></i> การจัดอันดับผลสัมฤทธิ์เฉลี่ยรายหน่วยงาน (Cross-Department Benchmark)</div>
                        <small class="text-muted">เปรียบเทียบคะแนนเฉลี่ยรวม (%) ระหว่างฝ่าย/หน่วยงาน</small>
                    </div>
                    <span class="badge bg-light text-muted border">Performance Ranking</span>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div style="height: 290px; width: 100%;">
                        <canvas id="chartDeptBenchmark"></canvas>
                    </div>
                    <div class="mt-3 p-2.5 bg-light rounded-3 small text-muted border">
                        <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>ข้อเสนอแนะเชิงบริหาร:</strong> ใช้เปรียบเทียบความเข้มงวดในการประเมินและจัดสรรโควตาร้อยละเลื่อนเงินเดือนระดับฝ่ายให้เกิดความเป็นธรรม
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Section 5: Executive Decision Table 1 - Department Merit & Quota Allocation Matrix -->
    <div class="card card-rmutt shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <div>
                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-diagram-3-fill me-2"></i> เมทริกซ์ผลสัมฤทธิ์และการจัดสรรโควตารายหน่วยงาน (Department Merit Matrix)</h6>
                <small class="text-muted">ข้อมูลสนับสนุนการพิจารณาจัดสรรโควตาร้อยละเลื่อนเงินเดือนและการกลั่นกรองผลคะแนนระดับฝ่าย</small>
            </div>
            <?= Html::a('<i class="bi bi-file-earmark-excel me-1"></i> ส่งออกข้อมูลกลั่นกรอง', ['/report/export-csv', 'cycle_id' => $cycleId], ['class' => 'btn btn-sm btn-outline-secondary fw-semibold']) ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">อันดับ</th>
                        <th>หน่วยงาน / ฝ่าย</th>
                        <th class="text-center" style="width: 130px;">ประเมินแล้ว / ทั้งหมด</th>
                        <th class="text-center" style="width: 140px;">คะแนนเฉลี่ยรวม</th>
                        <th class="text-center" style="width: 170px;">เฉลี่ย KPI / สมรรถนะ</th>
                        <th class="text-center" style="width: 140px;">สัดส่วนดีเด่น (≥95%)</th>
                        <th class="text-center" style="width: 110px;">กลุ่มเสี่ยง (&lt;70%)</th>
                        <th class="text-center" style="width: 160px;">สถานะกรอบโควตา</th>
                        <th>คำแนะนำเชิงบริหาร</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deptBenchmark)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">ยังไม่มีข้อมูลการประเมินในรอบและหน่วยงานที่เลือก</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deptBenchmark as $idx => $bm): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge rounded-circle <?= $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary text-white' : ($idx === 2 ? 'bg-primary-subtle text-primary' : 'bg-light text-dark border')) ?> p-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <?= $idx + 1 ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-dark d-block"><?= Html::encode($bm['department']->name_th) ?></strong>
                                    <small class="text-muted"><?= Html::encode($bm['department']->code ?: '-') ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold text-dark"><?= $bm['evaluated_count'] ?></span>
                                    <span class="text-muted">/ <?= $bm['total_staff'] ?> คน</span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold fs-6 text-primary"><?= number_format($bm['avg_score'], 2) ?>%</div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-primary" style="width: <?= min(100, $bm['avg_score']) ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-center small">
                                    <div>KPI: <strong><?= number_format($bm['avg_kpi'], 2) ?>%</strong></div>
                                    <div class="text-muted">สมรรถนะ: <strong><?= number_format($bm['avg_comp'], 2) ?>%</strong></div>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold <?= $bm['excellent_pct'] > 15.0 ? 'text-danger' : 'text-success' ?>">
                                        <?= $bm['excellent_pct'] ?>%
                                    </div>
                                    <small class="text-muted"><?= $bm['excellent_count'] ?> คน (เพดาน 15%)</small>
                                </td>
                                <td class="text-center">
                                    <?php if ($bm['at_risk_count'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                            <?= $bm['at_risk_count'] ?> คน
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success">0 คน</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($bm['is_over_quota']): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> เกินกรอบโควตา
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> สอดคล้องกรอบ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if ($bm['is_over_quota']): ?>
                                        <span class="text-danger fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i> คณะกรรมการต้องกลั่นกรองตัดเกรดใหม่</span>
                                    <?php elseif ($bm['at_risk_count'] > 0): ?>
                                        <span class="text-warning-emphasis"><i class="bi bi-exclamation-circle me-1"></i> จัดทำแผน PIP สำหรับกลุ่มเสี่ยง</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-check2 me-1"></i> จัดสรรโควตาตามผลสัมฤทธิ์ปกติ</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 6: Actionable Drill-Down Panels (Two Columns) -->
    <div class="row g-3 mb-4">

        <!-- Column 1: At-Risk Staff / PIP Action List -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100 border-top border-4 border-danger">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="fw-bold text-danger mb-0"><i class="bi bi-exclamation-octagon-fill me-2"></i> บัญชีรายชื่อกลุ่มที่ต้องพัฒนาเร่งด่วน (PIP Action List)</h6>
                        <small class="text-muted">บุคลากรที่ได้ผลงาน &lt; 70% หรือระดับพอใช้/ปรับปรุง ที่ผู้บริหารต้องสั่งการ</small>
                    </div>
                    <span class="badge bg-danger"><?= count($atRiskPersonnel) ?> ท่าน</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-admin align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ผู้รับการประเมิน</th>
                                <th>ฝ่ายสังกัด</th>
                                <th class="text-center">คะแนน / ระดับ</th>
                                <th class="text-center">คำสั่งการบริหาร</th>
                                <th class="text-center">ตรวจสอบ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($atRiskPersonnel)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                                        ไม่พบบุคลากรที่ผลงานต่ำกว่าเกณฑ์มาตรฐานในรอบนี้
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($atRiskPersonnel as $riskEval): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                                    <?= mb_substr($riskEval->personnel->first_name_th ?: 'U', 0, 1) ?>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block"><?= Html::encode($riskEval->personnel->fullName) ?></strong>
                                                    <small class="text-muted"><?= Html::encode($riskEval->personnel->position ? $riskEval->personnel->position->name_th : '-') ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= Html::encode($riskEval->personnel->department ? $riskEval->personnel->department->name_th : '-') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-danger"><?= number_format($riskEval->result->final_percentage, 2) ?>%</div>
                                            <?= $riskEval->result->performanceBadge ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-clipboard-pulse me-1"></i> ต้องทำ PIP
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?= Html::a('<i class="bi bi-eye"></i>', ['/monitor/view', 'id' => $riskEval->id], ['class' => 'btn btn-sm btn-outline-danger', 'title' => 'ดูข้อเท็จจริงในแบบประเมิน']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Column 2: Top Talents & Merit Candidates -->
        <div class="col-lg-6">
            <div class="card card-rmutt shadow-sm h-100 border-top border-4 border-success">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="fw-bold text-success mb-0"><i class="bi bi-stars me-2"></i> บุคลากรดาวเด่นผลงานยอดเยี่ยม (Top Talents)</h6>
                        <small class="text-muted">บุคลากรที่ได้คะแนนสูงสุดสำหรับการพิจารณาเลื่อนขั้นพิเศษและ Talent Pool</small>
                    </div>
                    <span class="badge bg-success"><?= count($topPerformers) ?> ท่าน</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-admin align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ผู้รับการประเมิน</th>
                                <th>ฝ่ายสังกัด</th>
                                <th class="text-center">คะแนน / ระดับ</th>
                                <th class="text-center">ข้อเสนอเชิงบริหาร</th>
                                <th class="text-center">ตรวจสอบ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topPerformers)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        ยังไม่มีข้อมูลบุคลากรที่ได้คะแนนระดับดีมากหรือดีเด่นในรอบนี้
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topPerformers as $idx => $topEval): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                                    <?= $idx + 1 ?>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block"><?= Html::encode($topEval->personnel->fullName) ?></strong>
                                                    <small class="text-muted"><?= Html::encode($topEval->personnel->position ? $topEval->personnel->position->name_th : '-') ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= Html::encode($topEval->personnel->department ? $topEval->personnel->department->name_th : '-') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-success"><?= number_format($topEval->result->final_percentage, 2) ?>%</div>
                                            <?= $topEval->result->performanceBadge ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($topEval->result->final_percentage >= 95.0): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-award-fill me-1"></i> เลื่อนขั้นพิเศษ / Talent
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="bi bi-star me-1"></i> โควตาเลื่อนขั้นปกติ
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= Html::a('<i class="bi bi-eye"></i>', ['/monitor/view', 'id' => $topEval->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'ดูรายละเอียดผลงาน']) ?>
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

    <!-- Section 7: Strategic Competency Gap Analysis for HRD Budgeting -->
    <?php if (!empty($competencyGaps)): ?>
        <div class="card card-rmutt shadow-sm mb-4 border-top border-4 border-primary">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-lightbulb-fill text-warning me-2"></i> ช่องว่างสมรรถนะองค์กร เพื่อจัดสรรงบประมาณพัฒนาบุคลากร (HRD Budgeting & Training Roadmap)</h6>
                    <small class="text-muted">สมรรถนะที่มีคะแนนเฉลี่ยต่ำที่สุดในองค์กร เพื่อใช้ตัดสินใจอนุมัติงบประมาณและหลักสูตรฝึกอบรมประจำปี</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Competency Gap Priority</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>ชื่อสมรรถนะ</th>
                            <th style="width: 140px;">ประเภท</th>
                            <th class="text-center" style="width: 130px;">ระดับคาดหวัง</th>
                            <th class="text-center" style="width: 130px;">คะแนนเฉลี่ยจริง</th>
                            <th class="text-center" style="width: 130px;">ช่องว่าง (Gap)</th>
                            <th>ข้อเสนอแนะหลักสูตรฝึกอบรมประจำปี (HRD Recommendations)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competencyGaps as $gIdx => $gap): ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $gIdx + 1 ?></td>
                                <td>
                                    <strong class="text-dark"><?= Html::encode($gap['name_th']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= Html::encode($gap['type'] ?: 'Core') ?></span>
                                </td>
                                <td class="text-center font-monospace">Level <?= $gap['expected'] ?></td>
                                <td class="text-center font-monospace fw-bold text-primary"><?= number_format($gap['actual'], 2) ?></td>
                                <td class="text-center">
                                    <?php if ($gap['gap'] < 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                            <?= number_format($gap['gap'], 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            +<?= number_format($gap['gap'], 2) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?php if ($gap['gap'] < 0): ?>
                                        <span class="text-danger fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i> จัดสรรงบประมาณพัฒนาทักษะหลักสูตรนี้เร่งด่วน</span>
                                    <?php else: ?>
                                        <span class="text-success"><i class="bi bi-check2 me-1"></i> ผลงานผ่านเกณฑ์ความคาดหวัง จัดอบรมระดับก้าวหน้า</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Visual Decision Analytics Scripts (Chart.js) -->
<?php
// Quota & Actual Distribution Data
$actualDistribution = [
    $gradePcts['ดีเด่น'] ?? 0,
    $gradePcts['ดีมาก'] ?? 0,
    $gradePcts['ดี'] ?? 0,
    $gradePcts['พอใช้'] ?? 0,
    $gradePcts['ต้องปรับปรุง'] ?? 0,
];

$quotaCeilings = [
    $quotaCaps['ดีเด่น'] ?? 15.0,
    $quotaCaps['ดีมาก'] ?? 35.0,
    $quotaCaps['ดี'] ?? 35.0,
    $quotaCaps['พอใช้'] ?? 10.0,
    $quotaCaps['ต้องปรับปรุง'] ?? 5.0,
];

$quotaDataJson = json_encode([
    'labels' => ['ดีเด่น (≥95%)', 'ดีมาก (85-94%)', 'ดี (75-84%)', 'พอใช้ (65-74%)', 'ต้องปรับปรุง (<65%)'],
    'actual' => $actualDistribution,
    'quota' => $quotaCeilings,
]);

// Department Benchmark Data
$deptLabels = [];
$deptAvgScores = [];
$deptColors = [];
foreach ($deptBenchmark as $bmItem) {
    $deptLabels[] = mb_substr($bmItem['department']->name_th, 0, 24) . (mb_strlen($bmItem['department']->name_th) > 24 ? '...' : '');
    $deptAvgScores[] = $bmItem['avg_score'];
    $deptColors[] = $bmItem['avg_score'] >= 85.0 ? '#10b981' : ($bmItem['avg_score'] >= 75.0 ? '#0284c7' : '#f59e0b');
}

$deptDataJson = json_encode([
    'labels' => $deptLabels,
    'scores' => $deptAvgScores,
    'colors' => $deptColors,
]);

$chartScript = <<<JS
$(function() {
    // 1. Quota vs Actual Distribution Chart
    const quotaData = {$quotaDataJson};
    const ctxQuota = document.getElementById('chartQuotaBenchmark');
    if (ctxQuota) {
        new Chart(ctxQuota, {
            type: 'bar',
            data: {
                labels: quotaData.labels,
                datasets: [
                    {
                        label: 'สัดส่วนจริง (Actual %)',
                        data: quotaData.actual,
                        backgroundColor: '#0284c7',
                        borderRadius: 6,
                        borderWidth: 0,
                    },
                    {
                        label: 'กรอบโควตาเพดาน (Quota Limit %)',
                        data: quotaData.quota,
                        backgroundColor: 'rgba(245, 158, 11, 0.35)',
                        borderColor: '#d97706',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderDash: [4, 4]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + '%'; },
                            font: { family: 'Sarabun' }
                        }
                    },
                    x: {
                        ticks: { font: { family: 'Sarabun', size: 11 } }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Sarabun', size: 12 }, padding: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Cross-Department Benchmark Chart
    const deptData = {$deptDataJson};
    const ctxDept = document.getElementById('chartDeptBenchmark');
    if (ctxDept && deptData.labels.length > 0) {
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: deptData.labels,
                datasets: [{
                    label: 'คะแนนเฉลี่ยรวม (%)',
                    data: deptData.scores,
                    backgroundColor: deptData.colors,
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + '%'; },
                            font: { family: 'Sarabun' }
                        }
                    },
                    y: {
                        ticks: { font: { family: 'Sarabun', size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'คะแนนเฉลี่ย: ' + context.parsed.x + '%';
                            }
                        }
                    }
                }
            }
        });
    }
});
JS;
$this->registerJs($chartScript);
?>
