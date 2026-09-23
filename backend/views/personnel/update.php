<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Personnel $model */

$this->title = 'แก้ไขข้อมูลบุคลากร: ' . $model->fullName;
?>
<div class="personnel-update py-3">
    <div class="d-flex align-items-center mb-4">
        <?= Html::a('<i class="bi bi-arrow-left me-1"></i> กลับ', ['index'], ['class' => 'btn btn-sm btn-outline-secondary me-3']) ?>
        <h4 class="fw-bold mb-0 text-dark"><?= Html::encode($this->title) ?></h4>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
