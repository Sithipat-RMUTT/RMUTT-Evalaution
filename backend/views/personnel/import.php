<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Department[] $departments */
/** @var int|null $targetDeptId */
/** @var bool $isSuperAdmin */

$this->title = 'นำเข้าข้อมูลบุคลากรจากระบบกลาง (Import Personnel)';
$this->params['breadcrumbs'][] = ['label' => 'ข้อมูลบุคลากร', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="personnel-import">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-3 border-bottom gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i><?= Html::encode($this->title) ?>
            </h3>
            <p class="text-muted mb-0">
                นำเข้าข้อมูลบุคลากรจากไฟล์ CSV จากระบบ HR กลางของมหาวิทยาลัย ทุกคนจะได้รับสิทธิ์ตั้งต้นเป็น <strong>บุคลากร (Personnel)</strong> เท่ากัน และระบบจะจับคู่แบบประเมินให้อัตโนมัติ
            </p>
        </div>
        <a href="<?= Url::to(['hierarchy', 'dept_id' => $targetDeptId]) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3-fill me-1"></i> ผังสายการประเมิน
        </a>
    </div>

    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>อัปโหลดไฟล์ข้อมูล (CSV)
                    </h5>
                    <a href="<?= Url::to('@web/sample_personnel.csv') ?>" class="btn btn-sm btn-outline-primary" download>
                        <i class="bi bi-download me-1"></i> ดาวน์โหลดไฟล์ตัวอย่าง
                    </a>
                </div>
                <div class="card-body p-4">
                    <?= Html::beginForm(['import'], 'post', ['enctype' => 'multipart/form-data']) ?>

                    <?php if ($isSuperAdmin): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">เลือกฝ่าย/สังกัดเป้าหมาย (หากในไฟล์ไม่ระบุ):</label>
                            <select class="form-select" name="target_dept_id">
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d->id ?>" <?= ((int)$targetDeptId === (int)$d->id) ? 'selected' : '' ?>>
                                        <?= Html::encode($d->name_th) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="target_dept_id" value="<?= Html::encode($targetDeptId) ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="form-label fw-bold">เลือกไฟล์ CSV:</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">รองรับไฟล์ <code>.csv</code> เข้ารหัส UTF-8 (แนะนำบันทึกแบบ CSV UTF-8 จาก Microsoft Excel)</div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-upload me-1"></i> เริ่มนำเข้าข้อมูล
                    </button>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>

        <!-- Format Guide -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 bg-light">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>รูปแบบโครงสร้างคอลัมน์ในไฟล์ CSV
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อคอลัมน์</th>
                                    <th>จำเป็น?</th>
                                    <th>ตัวอย่างค่า</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>employee_code</code></td>
                                    <td><span class="badge bg-danger">จำเป็น</span></td>
                                    <td>CIV001, UNV001</td>
                                </tr>
                                <tr>
                                    <td><code>username</code></td>
                                    <td><span class="badge bg-danger">จำเป็น</span></td>
                                    <td>prasert_r</td>
                                </tr>
                                <tr>
                                    <td><code>email</code></td>
                                    <td><span class="badge bg-danger">จำเป็น</span></td>
                                    <td>prasert_r@rmutt.ac.th</td>
                                </tr>
                                <tr>
                                    <td><code>prefix_th</code></td>
                                    <td>ไม่จำเป็น</td>
                                    <td>นาย, นาง, นางสาว, ดร.</td>
                                </tr>
                                <tr>
                                    <td><code>first_name_th</code></td>
                                    <td><span class="badge bg-danger">จำเป็น</span></td>
                                    <td>ประเสริฐ</td>
                                </tr>
                                <tr>
                                    <td><code>last_name_th</code></td>
                                    <td><span class="badge bg-danger">จำเป็น</span></td>
                                    <td>ราชการดี</td>
                                </tr>
                                <tr>
                                    <td><code>personnel_type_code</code></td>
                                    <td><span class="badge bg-danger">จำเป็น</span></td>
                                    <td><strong>CIVIL</strong>, <strong>UNIVERSITY</strong>, <strong>GOVT</strong>, <strong>SPECIAL</strong></td>
                                </tr>
                                <tr>
                                    <td><code>position_name</code></td>
                                    <td>ไม่จำเป็น</td>
                                    <td>นักวิชาการคอมพิวเตอร์</td>
                                </tr>
                                <tr>
                                    <td><code>department_code</code></td>
                                    <td>ไม่จำเป็น</td>
                                    <td>ARIT-IT, ARIT-LIB</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3 py-2 px-3 small mb-0">
                        <i class="bi bi-lightbulb me-1"></i> <strong>การแมปแบบประเมินอัตโนมัติ:</strong><br>
                        ระบบจะจับคู่แม่แบบการประเมินจาก <code>personnel_type_code</code> ให้อัตโนมัติ บุคลากรจะได้รับแบบฟอร์มที่ถูกต้องทันทีเมื่อเริ่มรอบการประเมิน
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
