<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */
/** @var string|null $portalType */

use common\widgets\Alert;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$portalType = $portalType ?? 'staff';
$isAdmin = ($portalType === 'admin');

$this->title = $isAdmin 
    ? 'เข้าสู่ระบบผู้ดูแล — ระบบประเมินผลการปฏิบัติงานบุคลากร มทร.ธัญบุรี' 
    : 'เข้าสู่ระบบ — ระบบประเมินผลการปฏิบัติงานบุคลากร มทร.ธัญบุรี';
?>

<div class="rmutt-top-accent"></div>

<main class="rmutt-login-stage">
    <div class="rmutt-bg-pattern"></div>

    <div class="rmutt-login-wrapper">
        <div class="rmutt-login-card">
            
            <!-- University Brand Header -->
            <div class="rmutt-card-header">
                <div class="rmutt-seal-wrapper">
                    <img src="<?= Yii::getAlias('@web/images/rmutt-logo.png') ?>" 
                         alt="ตราสัญลักษณ์ มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี" 
                         class="rmutt-seal-img">
                </div>
                <h1 class="rmutt-univ-title">มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี</h1>
                <div class="rmutt-univ-sub">Rajamangala University of Technology Thanyaburi</div>
                
                <div>
                    <span class="rmutt-system-pill" id="system-pill">
                        <i class="bi <?= $isAdmin ? 'bi-shield-lock-fill text-warning' : 'bi-award-fill' ?>" id="pill-icon"></i>
                        <span id="pill-text"><?= $isAdmin ? 'HR Administration Portal' : 'ระบบประเมินผลการปฏิบัติงานบุคลากร' ?></span>
                    </span>
                </div>
            </div>

            <div class="rmutt-header-divider"></div>

            <!-- Role Selector: Modern Enterprise Segmented Control (Staff / Admin) -->
            <div class="rmutt-portal-toggle-container">
                <div class="rmutt-segmented-control rmutt-portal-toggle" role="tablist" aria-label="เลือกประเภทการเข้าสู่ระบบ">
                    <button type="button" 
                            class="rmutt-segmented-btn rmutt-toggle-tab <?= !$isAdmin ? 'active' : '' ?>" 
                            id="tab-staff" 
                            data-portal="staff"
                            role="tab"
                            aria-selected="<?= !$isAdmin ? 'true' : 'false' ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>บุคลากร</span>
                    </button>
                    <button type="button" 
                            class="rmutt-segmented-btn rmutt-toggle-tab <?= $isAdmin ? 'active' : '' ?>" 
                            id="tab-admin" 
                            data-portal="admin"
                            role="tab"
                            aria-selected="<?= $isAdmin ? 'true' : 'false' ?>">
                        <i class="bi bi-shield-check"></i>
                        <span>ผู้ดูแลระบบ</span>
                    </button>
                </div>
            </div>

            <div class="form-section-title" id="form-section-title">
                <i class="bi <?= $isAdmin ? 'bi-shield-check text-warning' : 'bi-person-circle' ?>" id="section-icon"></i>
                <span id="section-text"><?= $isAdmin ? 'เข้าสู่ระบบผู้ดูแล (HR Administrator)' : 'เข้าสู่ระบบบุคลากร (Staff Portal)' ?></span>
            </div>

            <?= Alert::widget() ?>

            <!-- Login Form -->
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['autocomplete' => 'off'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"rmutt-input-wrapper\">{input}</div>\n{error}",
                    'errorOptions' => ['class' => 'rmutt-field-error'],
                ],
            ]); ?>

                <input type="hidden" name="portal_type" id="portal-type-input" value="<?= Html::encode($portalType) ?>">

                <!-- Username -->
                <?= $form->field($model, 'username', [
                    'options' => ['class' => 'rmutt-field-group'],
                    'labelOptions' => ['class' => 'rmutt-field-label'],
                    'template' => "<div class=\"rmutt-field-label\"><label for=\"loginform-username\">ชื่อผู้ใช้งาน</label><span class=\"rmutt-field-hint\" id=\"username-hint\">" . ($isAdmin ? 'HR / Admin Account' : 'RMUTT Account') . "</span></div>\n<div class=\"rmutt-input-wrapper\"><i class=\"bi " . ($isAdmin ? 'bi-shield-lock' : 'bi-person') . " rmutt-input-icon\" id=\"username-icon\"></i>{input}</div>\n{error}",
                ])->textInput([
                    'id' => 'loginform-username',
                    'class' => 'rmutt-form-control',
                    'placeholder' => $isAdmin ? 'กรอกชื่อผู้ใช้งาน หรือ Admin Account' : 'กรอกชื่อผู้ใช้งาน หรือ RMUTT Account',
                    'autofocus' => true,
                ]) ?>

                <!-- Password -->
                <?= $form->field($model, 'password', [
                    'options' => ['class' => 'rmutt-field-group'],
                    'labelOptions' => ['class' => 'rmutt-field-label'],
                    'template' => "<div class=\"rmutt-field-label\"><label for=\"loginform-password\">รหัสผ่าน</label><span class=\"rmutt-field-hint\">Password</span></div>\n<div class=\"rmutt-input-wrapper\"><i class=\"bi bi-lock rmutt-input-icon\"></i>{input}<button type=\"button\" class=\"rmutt-pwd-toggle\" id=\"pwd-toggle\" tabindex=\"-1\" title=\"แสดงหรือซ่อนรหัสผ่าน\" aria-label=\"แสดงหรือซ่อนรหัสผ่าน\"><i class=\"bi bi-eye\" id=\"pwd-icon\"></i></button></div>\n{error}",
                ])->passwordInput([
                    'id' => 'loginform-password',
                    'class' => 'rmutt-form-control',
                    'placeholder' => 'กรอกรหัสผ่าน',
                ]) ?>

                <!-- Remember Me -->
                <div class="rmutt-options-row">
                    <label class="rmutt-checkbox-label" for="loginform-rememberme">
                        <input type="hidden" name="LoginForm[rememberMe]" value="0">
                        <input type="checkbox" id="loginform-rememberme" name="LoginForm[rememberMe]" value="1" <?= $model->rememberMe ? 'checked' : '' ?>>
                        <span>จดจำการเข้าสู่ระบบ</span>
                    </label>
                    <span class="text-muted" id="cycle-badge" style="font-size: 0.78rem;"><?= $isAdmin ? 'ศูนย์ควบคุมระบบ' : 'รอบการประเมิน ๑/๒๕๖๙' ?></span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="rmutt-btn-submit" id="btn-submit" name="login-button">
                    <span class="rmutt-spinner"></span>
                    <i class="bi bi-box-arrow-in-right btn-icon"></i>
                    <span class="btn-text" id="btn-text"><?= $isAdmin ? 'เข้าสู่ระบบผู้ดูแล' : 'เข้าสู่ระบบ' ?></span>
                </button>

                <!-- RMUTT Account Notice -->
                <div class="rmutt-account-badge">
                    <i class="bi bi-info-circle-fill"></i>
                    <span id="account-badge-text"><?= $isAdmin ? 'ระบบบริหารจัดการข้อมูลการประเมินและตั้งค่าเกณฑ์ สวส.' : 'สามารถใช้ RMUTT Internet Account เพื่อเข้าสู่ระบบได้' ?></span>
                </div>

                <div class="rmutt-security-ribbon">
                    <i class="bi bi-shield-fill-check"></i>
                    <span>ระบบความปลอดภัยมาตรฐานสารสนเทศ มทร.ธัญบุรี</span>
                </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>

</main>

<?php
$js = <<<JS
(function () {
    // Portal / Role Tab Switching
    var tabStaff = document.getElementById('tab-staff');
    var tabAdmin = document.getElementById('tab-admin');
    var portalInput = document.getElementById('portal-type-input');
    var sectionIcon = document.getElementById('section-icon');
    var sectionText = document.getElementById('section-text');
    var usernameHint = document.getElementById('username-hint');
    var usernameIcon = document.getElementById('username-icon');
    var usernameInput = document.getElementById('loginform-username');
    var submitBtnText = document.getElementById('btn-text');
    var accountBadgeText = document.getElementById('account-badge-text');
    var cycleBadge = document.getElementById('cycle-badge');
    var pillIcon = document.getElementById('pill-icon');
    var pillText = document.getElementById('pill-text');

    function setPortal(portal) {
        if (portal === 'admin') {
            if (tabAdmin) { tabAdmin.classList.add('active'); tabAdmin.setAttribute('aria-selected', 'true'); }
            if (tabStaff) { tabStaff.classList.remove('active'); tabStaff.setAttribute('aria-selected', 'false'); }
            if (portalInput) portalInput.value = 'admin';

            if (sectionIcon) sectionIcon.className = 'bi bi-shield-check text-warning';
            if (sectionText) sectionText.textContent = 'เข้าสู่ระบบผู้ดูแล (HR Administrator)';
            if (usernameHint) usernameHint.textContent = 'HR / Admin Account';
            if (usernameIcon) usernameIcon.className = 'bi bi-shield-lock rmutt-input-icon';
            if (usernameInput) usernameInput.placeholder = 'กรอกชื่อผู้ใช้งาน หรือ Admin Account';
            if (submitBtnText) submitBtnText.textContent = 'เข้าสู่ระบบผู้ดูแล';
            if (accountBadgeText) accountBadgeText.textContent = 'ระบบบริหารจัดการข้อมูลการประเมินและตั้งค่าเกณฑ์ สวส.';
            if (cycleBadge) cycleBadge.textContent = 'ศูนย์ควบคุมระบบ';
            if (pillIcon) pillIcon.className = 'bi bi-shield-lock-fill text-warning';
            if (pillText) pillText.textContent = 'HR Administration Portal';
        } else {
            if (tabStaff) { tabStaff.classList.add('active'); tabStaff.setAttribute('aria-selected', 'true'); }
            if (tabAdmin) { tabAdmin.classList.remove('active'); tabAdmin.setAttribute('aria-selected', 'false'); }
            if (portalInput) portalInput.value = 'staff';

            if (sectionIcon) sectionIcon.className = 'bi bi-person-circle';
            if (sectionText) sectionText.textContent = 'เข้าสู่ระบบบุคลากร (Staff Portal)';
            if (usernameHint) usernameHint.textContent = 'RMUTT Account';
            if (usernameIcon) usernameIcon.className = 'bi bi-person rmutt-input-icon';
            if (usernameInput) usernameInput.placeholder = 'กรอกชื่อผู้ใช้งาน หรือ RMUTT Account';
            if (submitBtnText) submitBtnText.textContent = 'เข้าสู่ระบบ';
            if (accountBadgeText) accountBadgeText.textContent = 'สามารถใช้ RMUTT Internet Account เพื่อเข้าสู่ระบบได้';
            if (cycleBadge) cycleBadge.textContent = 'รอบการประเมิน ๑/๒๕๖๙';
            if (pillIcon) pillIcon.className = 'bi bi-award-fill';
            if (pillText) pillText.textContent = 'ระบบประเมินผลการปฏิบัติงานบุคลากร';
        }

        if (window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('portal', portal);
            window.history.replaceState(null, '', url.toString());
        }
    }

    if (tabStaff && tabAdmin) {
        tabStaff.addEventListener('click', function () {
            setPortal('staff');
        });
        tabAdmin.addEventListener('click', function () {
            setPortal('admin');
        });
    }

    // Password visibility toggle
    var toggleBtn = document.getElementById('pwd-toggle');
    var passwordInput = document.getElementById('loginform-password');
    var eyeIcon = document.getElementById('pwd-icon');

    if (toggleBtn && passwordInput && eyeIcon) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            toggleBtn.setAttribute('aria-label', isPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
            passwordInput.focus();
        });
    }

    // Submit state loading feedback
    var loginForm = $('#login-form');
    var submitBtn = $('#btn-submit');

    if (loginForm.length && submitBtn.length) {
        loginForm.on('beforeSubmit', function () {
            submitBtn.prop('disabled', true).addClass('is-loading');
            submitBtn.find('.btn-icon').hide();
            submitBtn.find('.btn-text').text('กำลังเข้าสู่ระบบ...');
            return true;
        });

        loginForm.on('afterValidate', function (event, messages, errorAttributes) {
            if (errorAttributes.length > 0) {
                submitBtn.prop('disabled', false).removeClass('is-loading');
                submitBtn.find('.btn-icon').show();
                var currentPortal = portalInput ? portalInput.value : 'staff';
                submitBtn.find('.btn-text').text(currentPortal === 'admin' ? 'เข้าสู่ระบบผู้ดูแล' : 'เข้าสู่ระบบ');
            }
        });
    }
})();
JS;
$this->registerJs($js);
?>