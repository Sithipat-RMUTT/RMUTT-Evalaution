<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use common\models\DepartmentEvaluationCycle;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle $cycle */
/** @var common\models\DepartmentEvaluationCycle $deptCycle */
/** @var common\models\Department $targetDept */
/** @var bool $isUpdate */

$isUpdate = $isUpdate ?? false;
$isPending = ($deptCycle->status === DepartmentEvaluationCycle::STATUS_PENDING);
?>

<div class="department-cycle-form">
    <?php $form = ActiveForm::begin([
        'id' => 'dept-cycle-form',
        'enableClientValidation' => true,
    ]); ?>

    <div class="card card-rmutt shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-building text-primary me-2"></i><?= Html::encode($targetDept->name_th) ?>
                </h5>
                <small class="text-muted">
                    กรอบรอบมหาวิทยาลัย: <strong><?= Html::encode($cycle->name_th) ?></strong> &bull; ปีงบประมาณ <?= $cycle->fiscal_year ?> (รอบที่ <?= $cycle->cycle_number ?>)
                </small>
            </div>
            <div>
                <?= $deptCycle->statusBadge ?>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <?= $form->field($deptCycle, 'name_th')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'เช่น การประเมินผลการปฏิบัติราชการ รอบที่ 1/2569 (' . $targetDept->name_th . ')',
                        'class' => 'form-control form-control-lg fw-semibold',
                    ])->hint('กำหนดชื่อรอบการประเมินสำหรับหน่วยงานของท่าน (ใช้แสดงผลในพอร์ทัลบุคลากรและรายงาน)') ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted fw-bold small">ปีงบประมาณ</label>
                    <div class="form-control form-control-lg bg-light text-muted fw-bold"><?= $cycle->fiscal_year ?></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted fw-bold small">รอบที่</label>
                    <div class="form-control form-control-lg bg-light text-muted fw-bold">รอบที่ <?= $cycle->cycle_number ?></div>
                </div>

                <div class="col-12"><hr class="my-2 text-muted"></div>

                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="bi bi-calendar-range text-primary me-1"></i> ๑. ช่วงเวลาของรอบการประเมิน (Period)
                    </h6>
                    <p class="small text-muted mb-0">กำหนดช่วงเวลาการปฏิบัติงานที่นำมาคิดคำนวณผลการประเมิน</p>
                </div>

                <div class="col-md-6">
                    <?= $form->field($deptCycle, 'period_start')->textInput(['type' => 'date']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($deptCycle, 'period_end')->textInput(['type' => 'date']) ?>
                </div>

                <div class="col-12"><hr class="my-2 text-muted"></div>

                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="bi bi-clock-history text-success me-1"></i> ๒. กำหนดการขั้นตอนการประเมินของหน่วยงาน
                    </h6>
                    <p class="small text-muted mb-0">กำหนดวันและเวลาเริ่มต้น-สิ้นสุดสำหรับแต่ละขั้นตอน บุคลากรและหัวหน้าจะทำรายการได้ตามช่วงเวลานี้</p>
                </div>

                <!-- Self-assessment dates -->
                <div class="col-md-6">
                    <?= $form->field($deptCycle, 'self_assessment_start')->textInput([
                        'type' => 'datetime-local',
                    ])->label('<i class="bi bi-person-fill text-primary me-1"></i> เริ่มต้นประเมินตนเอง (Self-Assessment Start)') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($deptCycle, 'self_assessment_end')->textInput([
                        'type' => 'datetime-local',
                    ])->label('<i class="bi bi-person-fill text-danger me-1"></i> สิ้นสุดประเมินตนเอง (Self-Assessment Deadline)') ?>
                </div>

                <!-- Supervisor eval dates -->
                <div class="col-md-6">
                    <?= $form->field($deptCycle, 'supervisor_eval_start')->textInput([
                        'type' => 'datetime-local',
                    ])->label('<i class="bi bi-person-check-fill text-primary me-1"></i> เริ่มต้นหัวหน้าประเมิน (Supervisor Eval Start)') ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($deptCycle, 'supervisor_eval_end')->textInput([
                        'type' => 'datetime-local',
                    ])->label('<i class="bi bi-person-check-fill text-danger me-1"></i> สิ้นสุดหัวหน้าประเมิน (Supervisor Eval Deadline)') ?>
                </div>

                <div class="col-12">
                    <div class="alert alert-info py-2.5 px-3 mb-0 small border-0 bg-info-subtle text-info-emphasis rounded-3">
                        <i class="bi bi-info-circle-fill me-1"></i> <strong>คำแนะนำ:</strong> ช่วงเวลาประเมินตนเองและช่วงเวลาที่หัวหน้าประเมินสามารถกำหนดให้มีระยะเวลาทับซ้อนกัน (Overlap) หรือเปิดพร้อมกันได้ เพื่อให้หัวหน้าสามารถเริ่มตรวจประเมินบุคลากรที่ส่งแบบประเมินตนเองแล้วได้ทันทีโดยไม่ต้องรอให้หมดเขตประเมินตนเอง
                    </div>
                </div>

                <div class="col-12"><hr class="my-2 text-muted"></div>

                <div class="col-12">
                    <?= $form->field($deptCycle, 'notes')->textarea([
                        'rows' => 3,
                        'placeholder' => 'ระบุข้อความชี้แจง แนวปฏิบัติ หรือคำแนะนำเฉพาะของหน่วยงานสำหรับบุคลากรในสังกัด (ถ้ามี)',
                    ])->label('<i class="bi bi-chat-left-text me-1"></i> คำชี้แจง / หมายเหตุสำหรับบุคลากรในหน่วยงาน') ?>
                </div>
            </div>

            <?php if ($isPending): ?>
                <div class="alert alert-warning border-warning bg-warning-subtle d-flex align-items-center gap-3 p-3 mt-4 rounded-3">
                    <i class="bi bi-shield-lock-fill fs-2 text-warning-emphasis flex-shrink-0"></i>
                    <div class="small">
                        <strong class="d-block text-dark fs-6">⚠️ ข้อตกลงและคำเตือนสำคัญก่อนเปิดรอบการประเมิน:</strong>
                        เมื่อท่านกดปุ่ม <strong>"ยืนยันและเปิดรอบการประเมิน"</strong> ระบบจะทำการล็อกโครงสร้างแบบประเมิน (KPI) ของหน่วยงานถาวรทันที และเปิดให้บุคลากรในสังกัดเข้าทำแบบประเมินตนเองตามวันเวลาที่กำหนดข้างต้น
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                <?= Html::a('<i class="bi bi-arrow-left me-1"></i> ยกเลิก / กลับหน้ารอบการประเมิน', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                
                <div>
                    <?php if ($isPending): ?>
                        <?= Html::submitButton('<i class="bi bi-play-circle-fill me-1"></i> ยืนยันและเปิดรอบการประเมิน', [
                            'class' => 'btn btn-success fw-bold px-4 shadow-sm btn-lg fs-6',
                            'data-confirm' => "⚠️ ยืนยันการเปิดรอบการประเมินสำหรับ {$targetDept->name_th} ตามกำหนดการที่ระบุ?\nระบบจะล็อกโครงสร้างแบบประเมินถาวร และเปิดให้บุคลากรเข้าประเมินตนเองทันที",
                        ]) ?>
                    <?php else: ?>
                        <?= Html::submitButton('<i class="bi bi-save me-1"></i> บันทึกการเปลี่ยนแปลงกำหนดการ', [
                            'class' => 'btn btn-primary fw-bold px-4 shadow-sm btn-lg fs-6',
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
