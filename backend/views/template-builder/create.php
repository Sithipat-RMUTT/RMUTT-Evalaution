<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\EvaluationTemplate $model */
/** @var common\models\Department[] $departments */
/** @var common\models\PersonnelType[] $personnelTypes */

$this->title = 'สร้างแบบประเมินใหม่ประจำหน่วยงาน';
$this->params['breadcrumbs'][] = ['label' => 'จัดการแบบประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="template-builder-create py-3">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-rmutt shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-plus-circle-fill me-2"></i> สร้างแบบประเมินประจำหน่วยงานใหม่
                    </h5>
                </div>
                <div class="card-body p-4">
                    
                    <?php $form = ActiveForm::begin(['action' => ['create']]); ?>

                    <div class="alert alert-info border-0 py-2 px-3 mb-4">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        ระบบจะเริ่มต้นสร้างหมวดมาตรฐาน <strong>หมวดผลสัมฤทธิ์ของงาน (70%)</strong> และ <strong>หมวดสมรรถนะ (30%)</strong> ให้อัตโนมัติ โดยท่านสามารถปรับแต่งตัวชี้วัดได้ในขั้นตอนถัดไป
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">หน่วยงาน / สังกัด: <span class="text-danger">*</span></label>
                        <select name="EvaluationTemplate[department_id]" class="form-select form-select-lg" required>
                            <option value="">-- เลือกหน่วยงานที่ใช้แบบประเมินนี้ --</option>
                            <option value="0">⭐ กำหนดเป็นแม่แบบมาตรฐานกลาง (ใช้กับทุกหน่วยงาน)</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept->id ?>" <?= $model->department_id == $dept->id ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($dept->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">เมื่อบุคลากรในสังกัดนี้เข้าสู่ระบบ ระบบจะดึงแบบประเมินนี้ขึ้นมาให้อัตโนมัติ</div>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'personnel_type_id')->dropDownList(
                            \yii\helpers\ArrayHelper::map($personnelTypes, 'id', 'name_th'),
                            ['prompt' => '-- เลือกประเภทบุคลากร --', 'class' => 'form-select']
                        )->label('ประเภทบุคลากรเป้าหมาย: <span class="text-danger">*</span>') ?>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'name_th')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'เช่น แบบประเมินผลการปฏิบัติงาน - ฝ่ายพัฒนาระบบสารสนเทศ (พนักงานมหาวิทยาลัย)',
                            'class' => 'form-control',
                        ])->label('ชื่อแบบประเมิน: <span class="text-danger">*</span>') ?>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'description')->textarea([
                            'rows' => 3,
                            'placeholder' => 'คำชี้แจง วัตถุประสงค์ หรือคำแนะนำในการประเมิน...',
                            'class' => 'form-control',
                        ])->label('คำชี้แจง / รายละเอียดเพิ่มเติม:') ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                        <?= Html::a('<i class="bi bi-arrow-left me-1"></i> ยกเลิก', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                        <?= Html::submitButton('<i class="bi bi-arrow-right-circle-fill me-1"></i> ถัดไป: สร้างตัวชี้วัด & สมรรถนะ', ['class' => 'btn btn-primary px-4 shadow-sm']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>
        </div>
    </div>

</div>
