<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle $cycle */
/** @var common\models\DepartmentEvaluationCycle $deptCycle */
/** @var common\models\Department $targetDept */

$this->title = 'เปิดรอบการประเมินประจำหน่วยงาน: ' . $targetDept->name_th;
$this->params['breadcrumbs'][] = ['label' => 'รอบการประเมิน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cycle-department-open py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-calendar-plus text-success me-2"></i>กำหนดวันเวลาและเปิดรอบการประเมินประจำหน่วยงาน
            </h4>
            <p class="text-muted small mb-0">
                กำหนดชื่อรอบ ช่วงเวลาประเมินตนเอง และช่วงเวลาที่หัวหน้าประเมินสำหรับ <strong><?= Html::encode($targetDept->name_th) ?></strong>
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
        'isUpdate' => false,
    ]) ?>
</div>
