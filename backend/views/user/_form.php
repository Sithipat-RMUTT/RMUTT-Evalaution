<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use common\models\User;
use backend\models\AdminUserForm;

/** @var yii\web\View $this */
/** @var backend\models\AdminUserForm $model */
/** @var common\models\Department[] $departments */
/** @var common\models\Personnel[] $eligiblePersonnel */

$isUpdate = !empty($model->id);
$deptMap = ArrayHelper::map($departments, 'id', 'name_th');
$eligiblePersonnel = $eligiblePersonnel ?? [];

$hrDeptId = null;
foreach ($departments as $d) {
    if (strtoupper((string)$d->code) === 'HR') {
        $hrDeptId = $d->id;
        break;
    }
}

$roleItems = [
    'admin' => 'Admin - เจ้าหน้าที่งานบุคคล / ผู้ดูแลระดับหน่วยงาน (ดูแลเฉพาะหน่วยงานตนเอง)',
    'central_hr' => 'Central HR Admin - เจ้าหน้าที่กองบริหารงานบุคคล (ส่วนกลาง / ดูแลภาพรวมทั้ง มทร.)',
    'superadmin' => 'Superadmin - ผู้ดูแลระบบสูงสุด (ฝ่ายไอที/เทคนิค เข้าถึงได้ทุกส่วนของระบบ)',
];
?>

<div class="card card-rmutt shadow-sm p-4">
    <?php $form = ActiveForm::begin([
        'id' => 'admin-user-form',
        'options' => ['class' => 'needs-validation'],
    ]); ?>

    <?php if ($isUpdate): ?>
        <?php if (!empty($model->personnel_id)): ?>
            <div class="alert alert-info border-primary d-flex align-items-center mb-4">
                <i class="bi bi-person-check-fill fs-2 text-primary me-3"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-primary">บัญชีนี้เชื่อมโยงกับบุคลากรในระบบ</h6>
                    <div class="text-dark">
                        <strong><?= Html::encode($model->display_name) ?></strong> 
                        (Username: <code><?= Html::encode($model->username) ?></code> | อีเมล: <?= Html::encode($model->email) ?>)
                    </div>
                    <div class="small text-muted mt-1">
                        บุคลากรใช้บัญชีนี้ในการประเมินตนเองที่หน้าบ้าน และเข้าจัดการระบบหลังบ้านได้ด้วยรหัสผ่านเดียวกัน
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary d-flex align-items-center mb-4">
                <i class="bi bi-hdd-network-fill fs-2 text-secondary me-3"></i>
                <div>
                    <h6 class="fw-bold mb-1">บัญชีผู้ดูแลระบบเฉพาะกิจ (Standalone Admin)</h6>
                    <div class="small text-muted">บัญชีระบบเฉพาะที่ไม่ได้ผูกกับบุคลากรรายบุคคล</div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Mode selector: Appoint vs Standalone -->
        <div class="card bg-light border-0 p-3 mb-4">
            <label class="form-label fw-bold text-dark mb-2">
                <i class="bi bi-sliders me-1 text-primary"></i> เลือกรูปแบบการเพิ่มผู้ดูแลระบบ:
            </label>
            <div class="d-flex flex-column flex-md-row gap-3">
                <div class="form-check bg-white p-3 rounded border flex-fill shadow-sm">
                    <input class="form-check-input ms-0 me-2" type="radio" name="AdminUserForm[create_mode]" id="mode-appoint" value="appoint" <?= $model->create_mode === AdminUserForm::MODE_APPOINT ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-primary" for="mode-appoint">
                        <i class="bi bi-person-check-fill me-1"></i> แต่งตั้งจากบุคลากรในระบบ (แนะนำ)
                        <div class="small text-muted fw-normal mt-1">
                            เลือกบุคลากรที่มีอยู่แล้ว ให้ใช้บัญชีเดิมเข้าหลังบ้านได้ทันที (1 คน 1 บัญชี ประเมินตนเองและดูแลหน่วยงานได้ครบ)
                        </div>
                    </label>
                </div>
                <div class="form-check bg-white p-3 rounded border flex-fill shadow-sm">
                    <input class="form-check-input ms-0 me-2" type="radio" name="AdminUserForm[create_mode]" id="mode-standalone" value="standalone" <?= $model->create_mode === AdminUserForm::MODE_STANDALONE ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-dark" for="mode-standalone">
                        <i class="bi bi-hdd-network-fill me-1 text-secondary"></i> สร้างบัญชีระบบเฉพาะกิจ (Standalone)
                        <div class="small text-muted fw-normal mt-1">
                            สร้าง User กลางสำหรับงานไอที หรือบัญชีกลางประจำฝ่าย (เช่น it_admin, admin_arit)
                        </div>
                    </label>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$isUpdate): ?>
        <!-- SECTION 1: Appoint from existing personnel -->
        <div id="section-appoint" class="<?= $model->create_mode === AdminUserForm::MODE_APPOINT ? '' : 'd-none' ?>">
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-search me-1 text-primary"></i> ค้นหาและเลือกบุคลากรที่ต้องการแต่งตั้งเป็น Admin: <span class="text-danger">*</span>
                </label>
                <select name="AdminUserForm[personnel_id]" id="personnel-select" class="form-select select2-searchable" style="width: 100%;">
                    <option value="">-- พิมพ์ชื่อ, นามสกุล หรือรหัสบุคลากร เพื่อค้นหา --</option>
                    <?php foreach ($eligiblePersonnel as $p): ?>
                        <?php
                        $fullName = $p->fullName;
                        $posName = $p->position->name_th ?? '-';
                        $deptName = $p->department->name_th ?? '-';
                        $username = $p->user->username ?? '';
                        $email = $p->email ?: ($p->user->email ?? '');
                        $selected = ((int)$model->personnel_id === (int)$p->id) ? 'selected' : '';
                        ?>
                        <option value="<?= $p->id ?>" 
                            data-name="<?= Html::encode($fullName) ?>"
                            data-position="<?= Html::encode($posName) ?>"
                            data-dept-id="<?= $p->department_id ?>"
                            data-dept-name="<?= Html::encode($deptName) ?>"
                            data-username="<?= Html::encode($username) ?>"
                            data-email="<?= Html::encode($email) ?>"
                            <?= $selected ?>>
                            <?= Html::encode("{$p->employee_code} - {$fullName} ({$posName} | {$deptName})") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">ระบบแสดงเฉพาะบุคลากรที่ยังไม่มีสิทธิ์ผู้ดูแลระบบในปัจจุบัน</div>
                <?= Html::error($model, 'personnel_id', ['class' => 'text-danger small mt-1 d-block']) ?>
            </div>

            <!-- Personnel info preview card -->
            <div id="personnel-preview-card" class="card border-primary bg-primary bg-opacity-10 p-3 mb-4 d-none">
                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-info-circle-fill me-1"></i> ข้อมูลบุคลากรที่เลือก</h6>
                <div class="row g-2 small">
                    <div class="col-md-4"><strong>ชื่อ-นามสกุล:</strong> <span id="pv-name">-</span></div>
                    <div class="col-md-4"><strong>ตำแหน่ง:</strong> <span id="pv-position">-</span></div>
                    <div class="col-md-4"><strong>หน่วยงานสังกัดเดิม:</strong> <span id="pv-dept">-</span></div>
                    <div class="col-md-6"><strong>ชื่อผู้ใช้งานเข้าสู่ระบบ (Username):</strong> <code id="pv-username">-</code></div>
                    <div class="col-md-6"><strong>อีเมล:</strong> <span id="pv-email">-</span></div>
                </div>
                <div class="mt-2 text-success small">
                    <i class="bi bi-check2-circle me-1"></i> เมื่อบันทึก บุคลากรท่านนี้จะสามารถล็อกอินเข้าสู่ระบบหลังบ้านด้วย Username และ Password เดิมของตนเองได้ทันที
                </div>
            </div>
        </div>

        <!-- SECTION 2: Standalone User Form -->
        <div id="section-standalone" class="<?= $model->create_mode === AdminUserForm::MODE_STANDALONE ? '' : 'd-none' ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <?= $form->field($model, 'username')->textInput([
                        'id' => 'standalone-username',
                        'maxlength' => true,
                        'placeholder' => 'เช่น admin_finance, it_admin',
                    ])->hint('ตัวอักษรภาษาอังกฤษ ตัวเลข ขีดล่าง หรือขีดกลาง 3-50 ตัวอักษร') ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'display_name')->textInput([
                        'id' => 'standalone-display-name',
                        'maxlength' => true,
                        'placeholder' => 'เช่น นายสมเกียรติ รักดี (งานพัสดุ)',
                    ])->hint('ชื่อ-นามสกุล หรือหน้าที่รับผิดชอบของผู้ดูแลระบบ') ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput([
                        'id' => 'standalone-email',
                        'maxlength' => true,
                        'placeholder' => 'เช่น admin@rmutt.ac.th',
                    ]) ?>
                </div>

                <div class="col-md-6">
                    <?= $form->field($model, 'password')->passwordInput([
                        'id' => 'standalone-password',
                        'placeholder' => 'กำหนดรหัสผ่านอย่างน้อย 6 ตัวอักษร',
                        'autocomplete' => 'new-password',
                    ])->hint('รหัสผ่านเริ่มต้นสำหรับเข้าสู่ระบบ (อย่างน้อย 6 ตัวอักษร)') ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- When updating existing standalone admin -->
        <?php if (empty($model->personnel_id)): ?>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <?= $form->field($model, 'username')->textInput([
                        'maxlength' => true,
                        'disabled' => ((int)$model->id === 1),
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'display_name')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน',
                        'autocomplete' => 'new-password',
                    ])->hint('เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน') ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Role and Department Scope Settings -->
    <div class="row g-3 pt-3 border-top">
        <div class="col-md-6">
            <?= $form->field($model, 'role')->dropDownList($roleItems, [
                'id' => 'admin-role-select',
                'disabled' => ($isUpdate && (int)$model->id === 1),
            ])->hint('เลือกระดับสิทธิ์: Admin (ระดับหน่วยงาน), Central HR Admin (กองบุคคลส่วนกลาง), หรือ Superadmin (ฝ่ายไอที)') ?>
        </div>

        <div class="col-md-6" id="dept-container">
            <?= $form->field($model, 'department_id')->dropDownList($deptMap, [
                'id' => 'scope-dept-select',
                'prompt' => '-- ส่วนกลาง (เข้าถึงข้อมูลภาพรวมทุกหน่วยงาน) --',
            ])->hint('ขอบเขตหน่วยงานที่ดูแล (หากเลือกแต่งตั้งบุคลากร ระบบจะกำหนดหน่วยงานให้อัตโนมัติ)') ?>
        </div>

        <?php if ($isUpdate): ?>
            <?php if (!empty($model->personnel_id)): ?>
                <div class="col-md-6">
                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่านของบุคลากร',
                        'autocomplete' => 'new-password',
                    ])->hint('หากต้องการรีเซ็ตรหัสผ่านของบุคลากร ให้ระบุที่นี่ (เว้นว่างไว้หากใช้รหัสผ่านเดิม)') ?>
                </div>
            <?php endif; ?>

            <?php if ((int)$model->id !== 1): ?>
                <div class="col-md-6">
                    <?= $form->field($model, 'status')->dropDownList([
                        User::STATUS_ACTIVE => 'ใช้งานได้ (Active)',
                        User::STATUS_INACTIVE => 'ระงับการใช้งาน (Inactive)',
                    ]) ?>
                </div>
            <?php endif; ?>
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

<?php
$hrDeptJson = json_encode($hrDeptId);

$js = <<<JS
$(document).ready(function() {
    function initSelect2() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('#personnel-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- พิมพ์ชื่อ, นามสกุล หรือรหัสบุคลากร เพื่อค้นหา --',
                allowClear: true,
                language: {
                    noResults: function() { return 'ไม่พบข้อมูลบุคลากร'; }
                }
            });
        }
    }
    initSelect2();

    function updatePersonnelPreview() {
        var opt = $('#personnel-select').find('option:selected');
        var val = $('#personnel-select').val();
        if (val && opt.length) {
            $('#pv-name').text(opt.data('name') || '-');
            $('#pv-position').text(opt.data('position') || '-');
            $('#pv-dept').text(opt.data('dept-name') || '-');
            $('#pv-username').text(opt.data('username') || '-');
            $('#pv-email').text(opt.data('email') || '-');
            $('#personnel-preview-card').removeClass('d-none');

            var deptId = opt.data('dept-id');
            if (deptId && $('#admin-role-select').val() === 'admin') {
                $('#scope-dept-select').val(deptId);
            }
        } else {
            $('#personnel-preview-card').addClass('d-none');
        }
    }

    $('#personnel-select').on('change select2:select select2:clear', updatePersonnelPreview);
    updatePersonnelPreview();

    $('input[name="AdminUserForm[create_mode]"]').on('change', function() {
        var mode = $(this).val();
        if (mode === 'appoint') {
            $('#section-appoint').removeClass('d-none');
            $('#section-standalone').addClass('d-none');
            $('#standalone-username').prop('disabled', true);
            $('#standalone-display-name').prop('disabled', true);
            $('#standalone-email').prop('disabled', true);
            $('#standalone-password').prop('disabled', true);
            $('#personnel-select').prop('disabled', false);
            initSelect2();
            updatePersonnelPreview();
        } else {
            $('#section-appoint').addClass('d-none');
            $('#section-standalone').removeClass('d-none');
            $('#standalone-username').prop('disabled', false);
            $('#standalone-display-name').prop('disabled', false);
            $('#standalone-email').prop('disabled', false);
            $('#standalone-password').prop('disabled', false);
            $('#personnel-select').prop('disabled', true);
            $('#personnel-preview-card').addClass('d-none');
        }
    });

    var hrDeptId = {$hrDeptJson};

    // When role changes
    $('#admin-role-select').on('change', function() {
        var role = $(this).val();
        if (role === 'superadmin') {
            $('#scope-dept-select').val('');
        } else if (role === 'central_hr') {
            if (hrDeptId) {
                $('#scope-dept-select').val(hrDeptId);
            } else {
                $('#scope-dept-select').val('');
            }
        } else if ($('#mode-appoint').is(':checked')) {
            updatePersonnelPreview();
        }
    });

    // Trigger initial mode state
    var initialMode = $('input[name="AdminUserForm[create_mode]"]:checked').val() || 'appoint';
    if (initialMode === 'appoint') {
        $('#standalone-username').prop('disabled', true);
        $('#standalone-display-name').prop('disabled', true);
        $('#standalone-email').prop('disabled', true);
        $('#standalone-password').prop('disabled', true);
    }
});
JS;
$this->registerJs($js);
?>
