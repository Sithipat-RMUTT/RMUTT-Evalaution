<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Evaluation;

/** @var yii\web\View $this */
/** @var common\models\Evaluation $evaluation */
/** @var common\models\Personnel $personnel */
/** @var common\models\TemplateVersion $templateVersion */
/** @var common\models\EvaluationSection[] $sections */
/** @var common\models\CompetencyDefinition[] $competencies */
/** @var common\models\EvaluationAnswer[] $selfAnswers */
/** @var common\models\EvaluationAnswer[] $supAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $selfCompAnswers */
/** @var common\models\EvaluationCompetencyAnswer[] $supCompAnswers */
/** @var common\models\EvidenceFile[] $evidenceFiles */
/** @var common\models\EvaluationResult $result */

$this->title = 'ตรวจสอบแบบประเมิน: ' . $personnel->fullName;
$details = $result ? $result->calculation_details : [];
?>

<div class="monitor-view py-3">

    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i> กลับหน้ารายการ', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            <h4 class="fw-bold mb-0 text-dark"><?= Html::encode($this->title) ?></h4>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-calculator me-1"></i> คำนวณคะแนนใหม่ (Recalculate)', ['recalculate', 'id' => $evaluation->id], [
                'class' => 'btn btn-outline-primary',
                'data-method' => 'post',
                'data-confirm' => 'ยืนยันการคำนวณคะแนนของแบบประเมินนี้ใหม่?'
            ]) ?>

            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#adminReturnModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> ปลดล็อก / ส่งกลับแก้ไข
            </button>
        </div>
    </div>

    <!-- Personnel & Cycle Info -->
    <div class="card card-rmutt p-4 shadow-sm mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">ชื่อ-สกุล</small>
                <strong><?= Html::encode($personnel->fullName) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">ตำแหน่ง / สังกัด</small>
                <div><?= Html::encode($personnel->position->name_th) ?></div>
                <small class="text-muted"><?= Html::encode($personnel->department->name_th) ?></small>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">ประเภทบุคลากร</small>
                <span class="badge bg-light text-dark border"><?= Html::encode($personnel->personnelType->name_th) ?></span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">สถานะ</small>
                <div><?= $evaluation->statusLabel ?></div>
            </div>
        </div>
    </div>



    <!-- Score Summary Card -->
    <?php if ($evaluation->isCompleted() && $result): ?>
        <div class="card card-rmutt p-4 shadow-sm mb-4 border-start border-primary border-4">
            <div class="row align-items-center">
                <div class="col-md-3 border-end text-center">
                    <div class="text-muted small fw-bold mb-1">คะแนนรวมสุทธิ (100%)</div>
                    <div class="display-5 fw-bold text-primary"><?= number_format($result->final_percentage, 2) ?>%</div>
                    <div class="mt-2"><?= $result->performanceBadge ?></div>
                </div>
                <?php
                $pTypeCode = $personnel->personnelType ? $personnel->personnelType->code : '';
                $isUnivOrGovt = in_array($pTypeCode, ['CIVIL', 'UNIVERSITY', 'GOVT'], true);
                $perfLabelWeight = $isUnivOrGovt ? '70%' : ($pTypeCode === 'SPECIAL' ? '55 คะแนน' : '70%');
                $compLabelWeight = $isUnivOrGovt ? '30%' : ($pTypeCode === 'SPECIAL' ? '45 คะแนน' : '30%');
                ?>
                <div class="col-md-9 ps-md-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-award-fill text-warning me-1"></i> สรุปผลคะแนนการประเมิน</h6>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small">ภาระงานหลัก/ผลงาน (สัดส่วน <?= $perfLabelWeight ?>)</div>
                                <div class="fs-5 fw-bold text-dark mt-1">
                                    <?= $result->supervisor_performance_score !== null ? number_format($result->supervisor_performance_score, 2) : '-' ?> 
                                    <span class="fs-6 text-muted fw-normal">คะแนน</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small">สมรรถนะ/คุณลักษณะ (สัดส่วน <?= $compLabelWeight ?>)</div>
                                <div class="fs-5 fw-bold text-dark mt-1">
                                    <?= $result->supervisor_competency_score !== null ? number_format($result->supervisor_competency_score, 2) : '-' ?> 
                                    <span class="fs-6 text-muted fw-normal">คะแนน</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small">คะแนนประเมินตนเอง</div>
                                <div class="fs-5 fw-bold text-secondary mt-1">
                                    <?= $result->self_total_score !== null ? number_format($result->self_total_score, 2) : '-' ?> 
                                    <span class="fs-6 text-muted fw-normal">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> คำนวณล่าสุด: <?= Yii::$app->formatter->asDatetime($result->calculated_at ?: $result->updated_at) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mb-4">
            <i class="bi bi-hourglass-split me-1"></i> แบบประเมินนี้อยู่ระหว่างขั้นตอน: <strong><?= $evaluation->statusLabel ?></strong> (ผลคะแนนสรุปและเกรดจะแสดงเมื่อการประเมินเสร็จสมบูรณ์แล้วเท่านั้น)
        </div>
    <?php endif; ?>

    <!-- Main Work Breakdown -->
    <?php 
    $mainWorkDetails = $details['form2']['main_work']['items'] ?? [];
    if (!empty($mainWorkDetails)): ?>
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="fw-bold text-primary mb-0">๑. ภาระงานหลัก (ค่าน้ำหนัก 80%)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>กิจกรรม / โครงการ / งานที่ปฏิบัติ</th>
                            <th style="width: 100px;">น้ำหนัก (%)</th>
                            <th style="width: 130px;">ตนเอง (PDCA)</th>
                            <th style="width: 130px;">หัวหน้า (PDCA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mainWorkDetails as $mIdx => $mItem): ?>
                            <tr>
                                <td class="text-center"><?= $mIdx + 1 ?></td>
                                <td><?= nl2br(Html::encode($mItem['title'])) ?></td>
                                <td class="text-center"><?= $mItem['weight'] ?>%</td>
                                <td class="text-center">ระดับ <?= $mItem['self_pdca'] ?></td>
                                <td class="text-center fw-bold">ระดับ <?= $mItem['sup_pdca'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Competencies Breakdown -->
    <?php if (!empty($competencies)): ?>
        <div class="card card-rmutt shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="fw-bold text-primary mb-0">๒. พฤติกรรมการปฏิบัติราชการ / สมรรถนะ (ค่าน้ำหนัก 20%)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>สมรรถนะ</th>
                            <th style="width: 100px;">ประเภท</th>
                            <th style="width: 120px;">ระดับคาดหวัง</th>
                            <th style="width: 120px;">ประเมินตนเอง</th>
                            <th style="width: 120px;">หัวหน้าประเมิน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competencies as $cIdx => $comp): 
                            $selfAns = $selfCompAnswers[$comp->id] ?? null;
                            $supAns = $supCompAnswers[$comp->id] ?? null;
                        ?>
                            <tr>
                                <td class="text-center"><?= $cIdx + 1 ?></td>
                                <td>
                                    <strong><?= Html::encode($comp->name_th) ?></strong>
                                    <?php if (!empty($comp->definition)): ?>
                                        <small class="text-muted d-block"><?= Html::encode($comp->definition) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $comp->competency_type === 'core' ? 'สมรรถนะหลัก' : 'สมรรถนะเฉพาะ' ?></span>
                                </td>
                                <td class="text-center">ระดับ <?= $comp->expected_level ?></td>
                                <td class="text-center"><?= $selfAns ? 'ระดับ ' . $selfAns->level_value : '-' ?></td>
                                <td class="text-center fw-bold text-primary"><?= $supAns ? 'ระดับ ' . $supAns->level_value : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Supervisor Comments -->
    <div class="card card-rmutt shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="fw-bold text-dark mb-0">๓. ความเห็นและข้อเสนอแนะของผู้บังคับบัญชา</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <strong>๑) จุดเด่น:</strong>
                <p class="mb-0 text-dark"><?= nl2br(Html::encode($evaluation->supervisor_comment_strength ?: '-')) ?></p>
            </div>
            <div class="mb-3">
                <strong>๒) จุดที่ควรปรับปรุง:</strong>
                <p class="mb-0 text-dark"><?= nl2br(Html::encode($evaluation->supervisor_comment_improvement ?: '-')) ?></p>
            </div>
            <div>
                <strong>๓) ข้อเสนอแนะการพัฒนา:</strong>
                <p class="mb-0 text-dark"><?= nl2br(Html::encode($evaluation->supervisor_comment_suggestion ?: '-')) ?></p>
            </div>
        </div>
    </div>

</div>

<!-- Admin Return Modal -->
<?php
$csrfParam = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf';
$csrfToken = Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '';
?>
<div class="modal fade" id="adminReturnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= Url::to(['admin-return', 'id' => $evaluation->id]) ?>" method="post">
                <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-1"></i> ปลดล็อก / ส่งแบบประเมินกลับแก้ไข</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ระบุเหตุผลการส่งกลับโดย Admin:</label>
                        <textarea name="return_reason" class="form-control" rows="4" required placeholder="ระบุเหตุผลที่ผู้ดูแลระบบส่งแบบประเมินกลับให้แก้ไข..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยันส่งกลับ</button>
                </div>
            </form>
        </div>
    </div>
</div>
