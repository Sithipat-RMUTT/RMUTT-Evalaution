<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use common\models\User;

/** @var yii\web\View $this */
/** @var backend\models\AdminUserForm $model */
/** @var common\models\Department[] $departments */
/** @var bool $isUpdate */

$isUpdate = !empty($model->id);
$deptMap = ArrayHelper::map($departments, 'id', 'name_th');
?>

<div class="card card-rmutt shadow-sm p-4">
    <?php $form = ActiveForm::begin([
        'id' => 'admin-user-form',
        'options' => ['class' => 'needs-validation'],
    ]); ?>

    <div class="row g-3">
        <div class="col-md-6">
            <?= $form->field($model, 'username')->textInput([
                'maxlength' => true,
                'placeholder' => 'เช่น admin_finance, it_admin',
                'disabled' => ($isUpdate && (int)$model->id === 1), // Primary admin cannot change username
            ])->hint('ตัวอักษรภาษาอังกฤษ ตัวเลข ขีดล่าง หรือขีดกลาง 3-50 ตัวอักษร') ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'display_name')->textInput([
                'maxlength' => true,
                'placeholder' => 'เช่น นายสมเกียรติ รักดี (งานพัสดุ)',
            ])->hint('ชื่อ-นามสกุล หรือหน้าที่รับผิดชอบของผู้ดูแลระบบ') ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'email')->textInput([
                'maxlength' => true,
                'placeholder' => 'เช่น admin@rmutt.ac.th',
            ]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'password')->passwordInput([
                'placeholder' => $isUpdate ? 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน' : 'กำหนดรหัสผ่านอย่างน้อย 6 ตัวอักษร',
                'autocomplete' => 'new-password',
            ])->hint($isUpdate ? 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน' : 'รหัสผ่านเริ่มต้นสำหรับเข้าสู่ระบบ (อย่างน้อย 6 ตัวอักษร)') ?>
        </div>

        <div class="col-md-6">
            <?php
            $roleItems = [
                'admin' => 'Admin - เจ้าหน้าที่งานบุคคล / ผู้ดูแลระดับหน่วยงาน',
                'superadmin' => 'Superadmin - ผู้ดูแลระบบสูงสุด (เข้าถึงได้ทุกส่วนของระบบ)',
            ];
            ?>
            <?= $form->field($model, 'role')->dropDownList($roleItems, [
                'id' => 'admin-role-select',
                'disabled' => ($isUpdate && (int)$model->id === 1), // Primary admin stays superadmin
            ])->hint('เลือกระดับสิทธิ์การเข้าถึงของผู้ดูแลระบบ') ?>
        </div>

        <div class="col-md-6" id="dept-container">
            <?= $form->field($model, 'department_id')->dropDownList($deptMap, [
                'prompt' => '-- ส่วนกลาง (เข้าถึงข้อมูลภาพรวมทุกหน่วยงาน) --',
            ])->hint('เลือกหน่วยงานที่ผู้ดูแลระบบท่านนี้มีหน้าที่ดูแล (หากเป็น Superadmin หรือเจ้าหน้าที่ส่วนกลางให้เลือกส่วนกลาง)') ?>
        </div>

        <?php if ($isUpdate && (int)$model->id !== 1): ?>
            <div class="col-md-6">
                <?= $form->field($model, 'status')->dropDownList([
                    User::STATUS_ACTIVE => 'ใช้งานได้ (Active)',
                    User::STATUS_INACTIVE => 'ระงับการใช้งาน (Inactive)',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
        <?= Html::a('<i class="bi bi-arrow-left me-1"></i> ยกเลิก / กลับหน้ารายการ', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::submitButton($isUpdate ? '<i class="bi bi-check-circle me-1"></i> บันทึกการแก้ไข' : '<i class="bi bi-person-plus-fill me-1"></i> บันทึกเพิ่มผู้ดูแลระบบ', [
            'class' => 'btn btn-primary px-4 shadow-sm',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
