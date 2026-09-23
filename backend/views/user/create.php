<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\AdminUserForm $model */
/** @var common\models\Department[] $departments */

$this->title = 'เพิ่มผู้ดูแลระบบใหม่';
$this->params['breadcrumbs'][] = ['label' => 'จัดการผู้ดูแลระบบ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-create py-3">
    <div class="mb-4">
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-person-plus-fill text-primary me-2"></i> เพิ่มผู้ดูแลระบบใหม่
        </h4>
        <p class="text-muted mb-0">สร้างบัญชีผู้ใช้งานระดับผู้ดูแลระบบ สำหรับเจ้าหน้าที่งานบุคคลหรือผู้ดูแลระบบระดับหน่วยงาน</p>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'departments' => $departments,
    ]) ?>
</div>
