<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap5\ActiveForm;
use common\models\PersonnelType;
use common\models\Department;
use common\models\Position;
use common\models\Personnel;

/** @var yii\web\View $this */
/** @var common\models\Personnel $model */
/** @var yii\bootstrap5\ActiveForm $form */

$types = ArrayHelper::map(PersonnelType::find()->all(), 'id', 'name_th');

$isSuperAdmin = Department::isCentralAdmin();
$myDeptId = Department::getCurrentUserDeptId();
$scopedDeptIds = $myDeptId ? Department::getAllScopedDeptIds($myDeptId) : [];

if (!$isSuperAdmin && $myDeptId) {
    $deptModels = Department::find()->where(['id' => $scopedDeptIds, 'status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
} else {
    $deptModels = Department::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])->all();
}
$departments = ArrayHelper::map($deptModels, 'id', function($d) {
    return $d->parent ? $d->name_th . ' (' . $d->parent->name_th . ')' : $d->name_th;
});

$positions = ArrayHelper::map(Position::find()->all(), 'id', 'name_th');
$supervisors = ArrayHelper::map(
    Personnel::find()->where(['is_supervisor' => 1])->all(),
    'id',
    fn($p) => $p->fullName . ' (' . $p->position->name_th . ')'
);
?>

<div class="personnel-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="card card-rmutt p-4 shadow-sm">
        <div class="row g-3">
            
            <?php if ($model->isNewRecord): ?>
                <div class="col-12 p-3 bg-light rounded border mb-2">
                    <h6 class="fw-bold text-primary mb-2"><i class="bi bi-person-badge me-1"></i> บัญชีผู้ใช้งานระบบ (User Account)</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อบัญชีผู้ใช้ (Username): <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="เช่น somkiat_p">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">รหัสผ่านเริ่มต้น (Password):</label>
                            <input type="password" name="password" class="form-control" value="password123">
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <?= $form->field($model, 'employee_code')->textInput(['placeholder' => 'เช่น CIV002']) ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'prefix_th')->dropDownList([
                    'นาย' => 'นาย',
                    'นาง' => 'นาง',
                    'นางสาว' => 'นางสาว',
                    'ดร.' => 'ดร.',
                    'ผศ.ดร.' => 'ผศ.ดร.',
                    'รศ.ดร.' => 'รศ.ดร.',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'first_name_th')->textInput(['required' => true]) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'last_name_th')->textInput(['required' => true]) ?>
            </div>

            <div class="col-md-4">
                <?= $form->field($model, 'personnel_type_id')->dropDownList($types, [
                    'prompt' => '-- เลือกประเภทบุคลากร --',
                    'class' => 'form-select select2-searchable',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'department_id')->dropDownList($departments, [
                    'prompt' => '-- เลือกฝ่าย/สังกัด --',
                    'class' => 'form-select select2-searchable',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'position_id')->dropDownList($positions, [
                    'prompt' => '-- เลือกตำแหน่ง --',
                    'class' => 'form-select select2-searchable',
                ]) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'supervisor_id')->dropDownList($supervisors, [
                    'prompt' => '-- ไม่มี / เป็นผู้บริหารสูงสุด --',
                    'class' => 'form-select select2-searchable',
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'is_supervisor')->dropDownList([
                    0 => 'ไม่ใช่ผู้ประเมิน',
                    1 => 'มีบทบาทเป็นผู้ประเมิน (หัวหน้างาน)',
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'status')->dropDownList([
                    10 => 'ปกติ (Active)',
                    0 => 'ระงับการใช้งาน (Inactive)',
                ]) ?>
            </div>

            <div class="col-md-6">
                <?= $form->field($model, 'email')->textInput(['type' => 'email']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'phone')->textInput() ?>
            </div>
        </div>

        <div class="mt-4 text-end">
            <?= Html::a('ยกเลิก', ['index'], ['class' => 'btn btn-secondary me-2']) ?>
            <?= Html::submitButton('<i class="bi bi-save me-1"></i> บันทึกข้อมูลบุคลากร', ['class' => 'btn btn-primary px-4']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$js = <<<JS
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-searchable').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: function() {
                return $(this).find('option[value=""]').text() || '-- เลือก --';
            }
        });
    }
});
JS;
$this->registerJs($js);
?>
