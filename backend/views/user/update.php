<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\AdminUserForm $model */
/** @var common\models\User $user */
/** @var common\models\Department[] $departments */

$this->title = 'แก้ไขข้อมูลผู้ดูแลระบบ: ' . $user->username;
$this->params['breadcrumbs'][] = ['label' => 'จัดการผู้ดูแลระบบ', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'แก้ไข: ' . $user->username;
?>

<div class="user-update py-3">
    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-pencil-square text-primary me-2"></i> แก้ไขข้อมูลผู้ดูแลระบบ: <span class="text-primary"><?= Html::encode($user->username) ?></span>
        </h4>
        <p class="text-muted mb-0">แก้ไขข้อมูลการติดต่อ ขอบเขตหน่วยงาน หรือรีเซ็ตรหัสผ่านสำหรับผู้ดูแลระบบ</p>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'departments' => $departments,
    ]) ?>
</div>
