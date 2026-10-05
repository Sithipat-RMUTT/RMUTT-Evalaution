<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle $cycle */
/** @var common\models\DepartmentEvaluationCycle $deptCycle */
/** @var common\models\Department $targetDept */

$this->title = 'แก้ไขกำหนดการรอบประเมิน: ' . $targetDept->name_th;
$this->params['breadcrumbs'][] = ['label' => 'รอบการประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cycle-department-update py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-pencil-square text-primary me-2"></i>แก้ไขกำหนดการและรายละเอียดรอบการประเมินประจำหน่วยงาน
            </h4>
            <p class="text-muted small mb-0">
                ปรับปรุงกำหนดเวลาประเมินตนเอง ขยายเวลา หรือแก้ไขคำชี้แจงสำหรับ <strong><?= Html::encode($targetDept->name_th) ?></strong>
            </p>
        </div>
        <div>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i> ย้อนกลับ', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
    </div>

    <?= $this->render('_department_form', [
        'cycle' => $cycle,
        'deptCycle' => $deptCycle,
        'targetDept' => $targetDept,
        'isUpdate' => true,
    ]) ?>
</div>
