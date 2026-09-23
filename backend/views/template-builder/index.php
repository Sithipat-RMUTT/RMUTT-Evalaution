<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\EvaluationTemplate;

/** @var yii\web\View $this */
/** @var common\models\EvaluationTemplate[] $templates */
/** @var common\models\Department[] $departments */
/** @var common\models\PersonnelType[] $personnelTypes */
/** @var int|null $selectedDepartmentId */
/** @var int|null $selectedPersonnelTypeId */

$this->title = 'จัดการแบบประเมินและตัวชี้วัด (Dynamic Form & KPI Builder)';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="template-builder-index py-3">

    <?php 
    $isSuper = isset($adminCtx) && $adminCtx['isSuperAdmin'];
    $myDept = isset($adminCtx) ? $adminCtx['department'] : null;
    ?>

    <?php if (!$isSuper && $myDept): ?>
        <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-shield-lock-fill fs-4 me-3 text-primary"></i>
            <div>
                <div class="fw-bold fs-6">สิทธิ์ผู้ดูแลระบบประจำหน่วยงาน: <?= Html::encode($myDept->name_th) ?></div>
                <small class="text-muted">ท่านสามารถสร้างและปรับแต่งแบบประเมินสำหรับบุคลากรในสังกัด <strong><?= Html::encode($myDept->name_th) ?></strong> ของท่านได้โดยตรง</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header & Action Bar -->
    <div class="card card-rmutt shadow-sm mb-4 border-0 bg-primary text-white p-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h3 class="fw-bold mb-1 text-white">
                    <i class="bi bi-file-earmark-ruled-fill me-2"></i> ระบบจัดการแบบประเมินประจำหน่วยงาน
                </h3>
                <p class="mb-0 text-white-50">
                    สร้าง ปรับแต่งตัวชี้วัดภาระงาน (KPI) สัดส่วนค่าน้ำหนัก และเกณฑ์คะแนน PDCA ประจำแต่ละฝ่าย/หน่วยงาน
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <?= Html::a('<i class="bi bi-plus-circle-fill me-1"></i> สร้างแบบประเมินใหม่', ['create'], ['class' => 'btn btn-light text-primary fw-bold shadow-sm px-3']) ?>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card card-rmutt shadow-sm mb-4 border-0 bg-light">
        <div class="card-body p-3">
            <form method="get" action="<?= Url::to(['index']) ?>" class="row g-2 align-items-center">
                <input type="hidden" name="r" value="template-builder/index">
                <?php if ($isSuper): ?>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary mb-1">กรองตามหน่วยงาน / สังกัด (Superadmin):</label>
                        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- แสดงทุกหน่วยงาน (ทั้งมหาวิทยาลัย) --</option>
                            <option value="default" <?= $selectedDepartmentId === 'default' ? 'selected' : '' ?>>⭐ แบบประเมินมาตรฐานกลาง</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept->id ?>" <?= strval($selectedDepartmentId) === strval($dept->id) ? 'selected' : '' ?>>
                                    🏢 <?= Html::encode($dept->name_th) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-secondary mb-1">หน่วยงานของคุณ:</label>
                        <input type="text" class="form-control form-control-sm bg-white" value="🏢 <?= $myDept ? Html::encode($myDept->name_th) : 'ส่วนกลาง' ?>" readonly disabled>
                    </div>
                <?php endif; ?>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary mb-1">กรองตามประเภทบุคลากร:</label>
                    <select name="personnel_type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกประเภทบุคลากร --</option>
                        <?php foreach ($personnelTypes as $pt): ?>
                            <option value="<?= $pt->id ?>" <?= strval($selectedPersonnelTypeId) === strval($pt->id) ? 'selected' : '' ?>>
                                📝 <?= Html::encode($pt->name_th) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <?php if ($selectedDepartmentId || $selectedPersonnelTypeId): ?>
                        <a href="<?= Url::to(['index']) ?>" class="btn btn-sm btn-outline-secondary w-100 mt-md-4">
                            <i class="bi bi-x-circle me-1"></i> ล้างตัวกรอง
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates List Table -->
    <div class="card card-rmutt shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>หน่วยงาน / สังกัด</th>
                        <th>ชื่อแบบประเมิน</th>
                        <th>ประเภทบุคลากร</th>
                        <th>สัดส่วนน้ำหนัก (หมวด)</th>
                        <th>จำนวน KPI</th>
                        <th>เวอร์ชัน</th>
                        <th class="text-center" style="width: 220px;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 text-muted d-block mb-2"></i>
                                ไม่พบแบบประเมินที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $idx => $tpl): 
                            $version = $tpl->activeVersion ?: ($tpl->versions ? $tpl->versions[0] : null);
                            $sections = $version ? $version->sections : [];
                            $itemCount = 0;
                            $weightStr = [];
                            foreach ($sections as $s) {
                                $itemCount += count($s->items);
                                $weightStr[] = number_format($s->weight, 0) . '%';
                            }
                            $compCount = $version ? count($version->competencyDefinitions) : 0;
                        ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <?php if ($tpl->department): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                            <i class="bi bi-building me-1"></i> <?= Html::encode($tpl->department->name_th) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6">
                                            <i class="bi bi-star-fill me-1"></i> แม่แบบมาตรฐานกลาง
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= Html::encode($tpl->name_th) ?></div>
                                    <small class="text-muted">รหัส: <code><?= Html::encode($tpl->code) ?></code></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= Html::encode($tpl->personnelType->name_th) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($weightStr)): ?>
                                        <span class="fw-semibold text-secondary"><?= implode(' / ', $weightStr) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-dark border border-info-subtle">
                                        <?= $itemCount ?> ตัวชี้วัด | <?= $compCount ?> สมรรถนะ
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= $version ? Html::encode($version->version_label) : 'v1.0' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?= Html::a('<i class="bi bi-pencil-square"></i> แก้ไขฟอร์ม', ['builder', 'id' => $tpl->id], [
                                            'class' => 'btn btn-outline-primary',
                                            'title' => 'ปรับแต่งตัวชี้วัดและเกณฑ์คะแนน',
                                        ]) ?>
                                        <button type="button" class="btn btn-outline-success" onclick="openCloneModal(<?= $tpl->id ?>, '<?= Html::encode($tpl->name_th) ?>')" title="คัดลอกไปใช้กับหน่วยงานอื่น">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                        <?= Html::a('<i class="bi bi-eye"></i>', ['preview', 'id' => $tpl->id], [
                                            'class' => 'btn btn-outline-info',
                                            'title' => 'พรีวิวฟอร์ม',
                                            'target' => '_blank',
                                        ]) ?>
                                        <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $tpl->id], [
                                            'class' => 'btn btn-outline-danger',
                                            'title' => 'ลบแบบประเมิน',
                                            'data-method' => 'post',
                                            'data-confirm' => "คุณแน่ใจหรือไม่ว่าต้องการลบแบบประเมิน '{$tpl->name_th}' ?",
                                        ]) ?>
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

<!-- Modal 1-Click Clone Template -->
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cloneForm" action="<?= Url::to(['clone']) ?>" method="post">
                <input type="hidden" name="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfParam : '_csrf' ?>" value="<?= Yii::$app->request instanceof \yii\web\Request ? Yii::$app->request->csrfToken : '' ?>">
                <input type="hidden" name="id" id="cloneSourceId" value="">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-copy me-1"></i> คัดลอกแบบประเมินไปใช้กับหน่วยงานอื่น</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        ระบบจะคัดลอกโครงสร้างหมวด ตัวชี้วัด KPI เกณฑ์ PDCA 1-5 และสมรรถนะทั้งหมดไปยังหน่วยงานใหม่ทันที
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">แบบประเมินต้นทาง:</label>
                        <input type="text" id="cloneSourceName" class="form-control" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">เลือกหน่วยงานเป้าหมาย (Department): <span class="text-danger">*</span></label>
                        <select name="target_department_id" class="form-select" required>
                            <option value="">-- เลือกหน่วยงานที่ต้องการนำไปใช้ --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept->id ?>">🏢 <?= Html::encode($dept->name_th) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อแบบประเมินใหม่ (เว้นว่างไว้เพื่อตั้งชื่ออัตโนมัติ):</label>
                        <input type="text" name="new_name" class="form-control" placeholder="เช่น แบบประเมินผลการปฏิบัติงาน - ฝ่ายบริการสารสนเทศ">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle-fill me-1"></i> ยืนยันคัดลอกแบบประเมิน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCloneModal(id, name) {
    document.getElementById('cloneSourceId').value = id;
    document.getElementById('cloneSourceName').value = name;
    var modal = new bootstrap.Modal(document.getElementById('cloneModal'));
    modal.show();
}
</script>
