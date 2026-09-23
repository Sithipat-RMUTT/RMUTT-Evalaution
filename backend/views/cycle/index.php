<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\EvaluationCycle;

/** @var yii\web\View $this */
/** @var common\models\EvaluationCycle[] $cycles */

$this->title = 'จัดการรอบการประเมินผลการปฏิบัติราชการ';
?>

<div class="cycle-index py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-calendar3 text-primary me-2"></i> จัดการรอบการประเมิน</h4>
            <p class="text-muted mb-0">สร้าง เปิดใช้งาน และกำหนดช่วงเวลาการประเมินของแต่ละรอบ</p>
        </div>
        <?= Html::a('<i class="bi bi-plus-circle me-1"></i> สร้างรอบการประเมินใหม่', ['create'], ['class' => 'btn btn-primary shadow-sm']) ?>
    </div>

    <div class="card card-rmutt shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>ชื่อรอบการประเมิน</th>
                        <th>ปีงบประมาณ / รอบ</th>
                        <th>ช่วงเวลาของรอบ</th>
                        <th>ช่วงประเมินตนเอง</th>
                        <th>ช่วงหัวหน้าประเมิน</th>
                        <th>สถานะ</th>
                        <th class="text-center" style="width: 200px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cycles)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลรอบการประเมิน</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cycles as $idx => $cycle): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <strong class="text-dark"><?= Html::encode($cycle->name_th) ?></strong>
                                    <?php if ($cycle->description): ?>
                                        <small class="text-muted d-block"><?= Html::encode($cycle->description) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">ปี <?= $cycle->fiscal_year ?> รอบที่ <?= $cycle->cycle_number ?></span>
                                </td>
                                <td>
                                    <small><?= Yii::$app->formatter->asDate($cycle->period_start, 'php:d/m/Y') ?> - <?= Yii::$app->formatter->asDate($cycle->period_end, 'php:d/m/Y') ?></small>
                                </td>
                                <td>
                                    <small><?= Yii::$app->formatter->asDate($cycle->self_assessment_start, 'php:d/m/Y H:i') ?> - <br><?= Yii::$app->formatter->asDate($cycle->self_assessment_end, 'php:d/m/Y H:i') ?></small>
                                </td>
                                <td>
                                    <small><?= Yii::$app->formatter->asDate($cycle->supervisor_eval_start, 'php:d/m/Y H:i') ?> - <br><?= Yii::$app->formatter->asDate($cycle->supervisor_eval_end, 'php:d/m/Y H:i') ?></small>
                                </td>
                                <td><?= $cycle->statusLabel ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $cycle->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'แก้ไข']) ?>
                                        <?php if ($cycle->status === EvaluationCycle::STATUS_DRAFT): ?>
                                            <?= Html::a('<i class="bi bi-play-fill"></i> เปิดรอบ', ['set-active', 'id' => $cycle->id], [
                                                'class' => 'btn btn-sm btn-outline-success',
                                                'data-method' => 'post',
                                                'data-confirm' => 'ยืนยันการเปิดรอบการประเมินนี้?',
                                                'title' => 'เปิดใช้งานรอบประเมิน'
                                            ]) ?>
                                        <?php elseif ($cycle->status === EvaluationCycle::STATUS_ACTIVE): ?>
                                            <?= Html::a('<i class="bi bi-stop-fill"></i> ปิดรอบ', ['close', 'id' => $cycle->id], [
                                                'class' => 'btn btn-sm btn-outline-danger',
                                                'data-method' => 'post',
                                                'data-confirm' => 'ยืนยันการปิดรอบการประเมินนี้?',
                                                'title' => 'ปิดรอบประเมิน'
                                            ]) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
