<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use common\models\EvaluationCycle;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle $model */
/** @var yii\bootstrap5\ActiveForm $form */
?>

<div class="evaluation-cycle-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="card card-rmutt p-4 shadow-sm">
        <div class="row g-3">
            <div class="col-md-8">
                <?= $form->field($model, 'name_th')->textInput(['maxlength' => true, 'placeholder' => 'เช่น การประเมินผลการปฏิบัติราชการ รอบที่ 1/2569']) ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'fiscal_year')->textInput(['type' => 'number']) ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'cycle_number')->dropDownList([1 => 'รอบที่ 1', 2 => 'รอบที่ 2']) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'period_start')->textInput(['type' => 'date']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'period_end')->textInput(['type' => 'date']) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'self_assessment_start')->textInput(['type' => 'datetime-local']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'self_assessment_end')->textInput(['type' => 'datetime-local']) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'supervisor_eval_start')->textInput(['type' => 'datetime-local']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'supervisor_eval_end')->textInput(['type' => 'datetime-local']) ?>
            </div>

            <div class="col-12">
                <div class="alert alert-info py-2 px-3 mb-0 small border-0 bg-info-subtle text-info-emphasis">
                    <i class="bi bi-info-circle me-1"></i> <strong>คำแนะนำ:</strong> ช่วงเวลาประเมินตนเองและช่วงเวลาที่หัวหน้าประเมินสามารถกำหนดให้มีระยะเวลาทับซ้อนกัน (Overlap) หรือเปิดพร้อมกันได้ เพื่อให้หัวหน้าสามารถเริ่มประเมินบุคลากรที่ส่งประเมินตนเองแล้วได้ทันที
                </div>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'status')->dropDownList([
                    EvaluationCycle::STATUS_DRAFT => 'ร่าง (Draft)',
                    EvaluationCycle::STATUS_ACTIVE => 'เปิดประเมินตนเอง (Active)',
                    EvaluationCycle::STATUS_EVALUATION => 'หัวหน้าประเมิน (Supervisor Evaluation)',
                    EvaluationCycle::STATUS_CLOSED => 'ปิดรอบประเมิน (Closed)',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'description')->textInput() ?>
            </div>
        </div>

        <div class="mt-4 text-end">
            <?= Html::a('ยกเลิก', ['index'], ['class' => 'btn btn-secondary me-2']) ?>
            <?= Html::submitButton('<i class="bi bi-save me-1"></i> บันทึกข้อมูล', ['class' => 'btn btn-primary px-4']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
