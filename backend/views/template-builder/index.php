<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $adminCtx */
/** @var bool $isSuperAdmin */
/** @var common\models\Department|null $targetDepartment */
/** @var int|null $targetDeptId */
/** @var common\models\Department[] $departments */
/** @var common\models\EvaluationCycle|null $activeCycle */
/** @var common\models\PersonnelType[] $personnelTypes */
/** @var array $templateMatrix */

$this->title = 'จัดการเกณฑ์และแบบประเมินผลการปฏิบัติงาน';
$this->params['breadcrumbs'][] = $this->title;

$deptName = $targetDepartment ? $targetDepartment->name_th : 'แบบฟอร์มมาตรฐานกลาง มหาวิทยาลัย';
?>

<div class="template-builder-index py-3">

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-sliders text-primary me-2"></i>จัดการเกณฑ์และแบบประเมินผลการปฏิบัติงาน
                </h4>
                <?php if ($targetDepartment): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fs-6">
                        <i class="bi bi-building me-1"></i><?= Html::encode($targetDepartment->name_th) ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1.5 fs-6">
                        <i class="bi bi-star-fill me-1"></i>แบบฟอร์มมาตรฐานกลางของมหาวิทยาลัย
                    </span>
                <?php endif; ?>
                <?php if ($activeCycle): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fs-6">
                        <i class="bi bi-calendar-check me-1"></i><?= Html::encode($activeCycle->name_th) ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                <?php if ($targetDepartment): ?>
                    หน่วยงานสามารถปรับแต่งตัวชี้วัด (KPI) ค่าน้ำหนัก และเกณฑ์คะแนนให้สอดคล้องกับภาระงานจริงของ <strong><?= Html::encode($targetDepartment->name_th) ?></strong> หากกลุ่มใดไม่ได้ปรับแต่ง ระบบจะใช้แบบฟอร์มมาตรฐานกลางของมหาวิทยาลัยโดยอัตโนมัติ
                <?php else: ?>
                    กำหนดโครงสร้างและตัวชี้วัดของแบบฟอร์มมาตรฐานกลาง (มหาวิทยาลัย) สำหรับใช้เป็นต้นแบบให้ทุกหน่วยงาน
                <?php endif; ?>
            </p>
        </div>

        <?php if ($isSuperAdmin && !empty($departments)): ?>
            <div class="d-flex align-items-center gap-2">
                <form method="get" action="<?= Url::to(['/template-builder/index']) ?>" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="r" value="template-builder/index">
                    <span class="small text-muted fw-bold text-nowrap"><i class="bi bi-building me-1"></i>สลับหน่วยงาน:</span>
                    <select name="department_id" class="form-select form-select-sm" style="min-width: 280px;" onchange="this.form.submit()">
                        <option value="central" <?= empty($targetDeptId) ? 'selected' : '' ?>>⭐ แบบฟอร์มมาตรฐานกลาง (มหาวิทยาลัย)</option>
                        <optgroup label="หน่วยงาน / คณะ / สำนัก">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d->id ?>" <?= $targetDeptId == $d->id ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($d->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Card: 4 Personnel Types Only -->
    <div class="card card-rmutt shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-check2-circle text-primary me-2"></i>เกณฑ์แบบประเมินสำหรับบุคลากร ๔ กลุ่มในสังกัด
                </h6>
                <small class="text-muted">
                    เลือกปรับแต่งตัวชี้วัดภาระงาน (KPI) เฉพาะหน่วยงาน หรือใช้แบบฟอร์มมาตรฐานกลางของมหาวิทยาลัย
                </small>
            </div>
            <span class="badge bg-light text-secondary border">ทั้งหมด 4 กลุ่มบุคลากร</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th style="width: 240px;" class="ps-4">กลุ่มประเภทบุคลากร</th>
                        <th>สถานะแบบประเมินที่ใช้งานจริง</th>
                        <th style="width: 220px;">โครงสร้างคะแนน & ตัวชี้วัด</th>
                        <th class="text-end pe-4" style="width: 380px;">การจัดการเกณฑ์ของหน่วยงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templateMatrix as $ptId => $item): 
                        $pt = $item['personnelType'];
                        $customTpl = $item['customTemplate'];
                        $centralTpl = $item['centralTemplate'];
                        $activeTpl = $item['activeTemplate'];
                        $isCustom = $item['isCustom'];
                        $itemCount = $item['itemCount'];
                        $compCount = $item['compCount'];
                        $weightStr = $item['weightStr'];
                    ?>
                        <tr>
                            <!-- 1. Personnel Type -->
                            <td class="ps-4">
                                <div class="fw-bold text-dark fs-6"><?= Html::encode($pt->name_th) ?></div>
                                <span class="badge bg-light text-secondary border font-monospace mt-1">
                                    <?= Html::encode($pt->code) ?>
                                </span>
                            </td>

                            <!-- 2. Active Template Status -->
                            <td>
                                <?php if ($isCustom && $customTpl): ?>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                                            <i class="bi bi-check-circle-fill me-1"></i>แบบประเมินเฉพาะหน่วยงาน (ปรับแต่งแล้ว)
                                        </span>
                                    </div>
                                    <div class="fw-semibold text-dark fs-6">
                                        <?= Html::encode($customTpl->name_th) ?>
                                    </div>
                                    <small class="text-muted">
                                        รหัส: <code><?= Html::encode($customTpl->code) ?></code>
                                        <?php if ($customTpl->activeVersion): ?>
                                            • <span class="badge bg-light text-dark border"><?= Html::encode($customTpl->activeVersion->version_label) ?></span>
                                        <?php endif; ?>
                                    </small>
                                <?php elseif ($centralTpl): ?>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-secondary border px-2.5 py-1.5">
                                            <i class="bi bi-shield-check me-1 text-primary"></i>ใช้แบบฟอร์มมาตรฐานกลาง (ค่าเริ่มต้น)
                                        </span>
                                    </div>
                                    <div class="text-dark fw-medium fs-6">
                                        <?= Html::encode($centralTpl->name_th) ?>
                                    </div>
                                    <small class="text-muted">
                                        รหัส: <code><?= Html::encode($centralTpl->code) ?></code>
                                        • มหาวิทยาลัยกำหนด
                                    </small>
                                <?php else: ?>
                                    <span class="text-danger fw-semibold">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>ยังไม่มีแบบประเมินในระบบ
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- 3. Scoring Structure -->
                            <td>
                                <div class="small fw-semibold text-dark">
                                    สัดส่วน: <?= !empty($weightStr) ? implode(' / ', $weightStr) : '70% / 30%' ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-list-check me-1"></i><?= $itemCount ?> ตัวชี้วัด (KPI)
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-person-badge me-1"></i><?= $compCount ?> ด้านสมรรถนะ
                                </div>
                            </td>

                            <!-- 4. Actions -->
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-end">
                                    <?php if ($isCustom && $customTpl): ?>
                                        <!-- Case A: Department has customized template -->
                                        <?= Html::a('<i class="bi bi-pencil-square me-1"></i>ปรับแต่งตัวชี้วัด (KPI)', ['/template-builder/builder', 'id' => $customTpl->id], [
                                            'class' => 'btn btn-sm btn-primary fw-semibold shadow-sm',
                                            'title' => 'ปรับแต่งตัวชี้วัด ค่าน้ำหนัก และเกณฑ์คะแนนตามภาระงานของหน่วยงาน',
                                        ]) ?>

                                        <?= Html::a('<i class="bi bi-eye"></i>', ['/template-builder/preview', 'id' => $customTpl->id], [
                                            'class' => 'btn btn-sm btn-outline-secondary',
                                            'title' => 'ดูตัวอย่างแบบฟอร์ม',
                                            'target' => '_blank',
                                        ]) ?>

                                        <?= Html::a('<i class="bi bi-arrow-counterclockwise me-1"></i>คืนค่าเป็นแบบส่วนกลาง', ['/template-builder/reset-to-central', 'personnel_type_id' => $pt->id, 'department_id' => $targetDeptId], [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'data-method' => 'post',
                                            'data-confirm' => "ยืนยันการคืนค่าเป็นแบบฟอร์มมาตรฐานกลางของมหาวิทยาลัยสำหรับกลุ่ม '{$pt->name_th}'?\n\nการปรับแต่งตัวชี้วัดเฉพาะของหน่วยงานจะถูกยกเลิก และบุคลากรจะกลับไปใช้แบบประเมินมาตรฐานกลางทันที",
                                            'title' => 'ยกเลิกการปรับแต่งเฉพาะหน่วยงานและกลับไปใช้แบบมาตรฐานกลาง',
                                        ]) ?>
                                    <?php elseif ($centralTpl): ?>
                                        <!-- Case B: Department is using central template -->
                                        <?= Html::a('<i class="bi bi-sliders me-1"></i>นำแบบส่วนกลางมาปรับแต่งเกณฑ์ของหน่วยงาน', ['/template-builder/customize', 'personnel_type_id' => $pt->id, 'department_id' => $targetDeptId], [
                                            'class' => 'btn btn-sm btn-primary fw-semibold shadow-sm',
                                            'title' => 'คัดลอกแบบฟอร์มมาตรฐานกลางมาปรับแต่งตัวชี้วัดและเกณฑ์คะแนนเฉพาะหน่วยงานทันที',
                                        ]) ?>

                                        <?= Html::a('<i class="bi bi-eye me-1"></i>ดูฟอร์มกลาง', ['/template-builder/preview', 'id' => $centralTpl->id], [
                                            'class' => 'btn btn-sm btn-outline-secondary',
                                            'title' => 'ดูตัวอย่างแบบฟอร์มมาตรฐานกลาง',
                                            'target' => '_blank',
                                        ]) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-light-subtle py-3 px-4 border-top">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i class="bi bi-info-circle-fill text-primary fs-6"></i>
                <div>
                    <strong>แนวปฏิบัติ:</strong> หากหน่วยงานมีตัวชี้วัดเฉพาะด้าน (เช่น งานพัฒนาระบบ งานบริการห้องสมุด งานห้องปฏิบัติการ) ให้คลิก <strong>"นำแบบส่วนกลางมาปรับแต่งเกณฑ์ของหน่วยงาน"</strong> เพื่อเพิ่ม/ลดตัวชี้วัดได้ตามต้องการ หากกลุ่มใดไม่มีตัวชี้วัดเฉพาะทาง ระบบจะใช้แบบฟอร์มมาตรฐานกลางของมหาวิทยาลัยโดยอัตโนมัติ
                </div>
            </div>
        </div>
    </div>

</div>
