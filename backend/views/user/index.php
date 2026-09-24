<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\User[] $users */
/** @var bool $canManage */

$this->title = 'จัดการผู้ใช้งานระดับผู้ดูแลระบบ (Admin Users)';
?>

<div class="user-index py-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-shield-lock-fill text-primary me-2"></i> ผู้ใช้งานระบบ (Admin Users)
            </h4>
            <p class="text-muted mb-0">จัดการบัญชีผู้ดูแลระบบทั้งที่แต่งตั้งจากบุคลากรในระบบ และบัญชีระบบเฉพาะกิจ (สิทธิ์เฉพาะ Superadmin)</p>
        </div>
        <?php if ($canManage): ?>
            <div>
                <?= Html::a('<i class="bi bi-person-plus-fill me-1"></i> เพิ่มผู้ดูแลระบบใหม่', ['create'], [
                    'class' => 'btn btn-primary shadow-sm px-3',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card card-rmutt shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>ชื่อบัญชี (Username)</th>
                        <th>ผู้ดูแลระบบ / ข้อมูลบุคลากร</th>
                        <th>อีเมล</th>
                        <th>บทบาทระบบ (Role)</th>
                        <th>ขอบเขตหน่วยงาน (Scope)</th>
                        <th>สถานะ</th>
                        <?php if ($canManage): ?>
                            <th class="text-center" style="width: 170px;">การจัดการ</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="<?= $canManage ? 8 : 7 ?>" class="text-center py-4 text-muted">ไม่พบข้อมูลผู้ดูแลระบบ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $idx => $u): ?>
                            <?php
                            $roles = array_keys(Yii::$app->authManager->getRolesByUser($u->id));
                            $roleLabel = implode(', ', $roles);
                            $badgeClass = 'bg-primary';
                            $scopeDesc = $u->department ? $u->department->name_th : 'ส่วนกลาง (ทุกหน่วยงาน)';
                            $isPersonnel = !empty($u->personnel);
                            
                            if (in_array('superadmin', $roles, true)) {
                                $badgeClass = 'bg-danger';
                                $roleLabel = 'Superadmin';
                                $scopeDesc = 'ส่วนกลาง (สิทธิ์สูงสุดทั้งระบบ)';
                            } elseif ($u->username === 'admin_hr' || ($u->department && $u->department->code === 'HR')) {
                                $badgeClass = 'bg-primary';
                                $roleLabel = 'Central HR Admin';
                                $scopeDesc = 'กองบริหารงานบุคคล (ภาพรวมมหาวิทยาลัย)';
                            } elseif ($u->department) {
                                $badgeClass = 'bg-info text-dark';
                                $roleLabel = 'Department Admin';
                                $scopeDesc = $u->department->name_th;
                            }
                            $currUserId = (Yii::$app->has('user') && !Yii::$app->user->isGuest) ? Yii::$app->user->id : 0;
                            $isSelf = ((int)$u->id === (int)$currUserId);
                            $isPrimaryAdmin = ((int)$u->id === 1);
                            ?>
                            <tr>
                                <td class="text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <strong class="text-dark"><i class="bi bi-person-badge me-1"></i><?= Html::encode($u->username) ?></strong>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">บัญชีปัจจุบัน</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isPersonnel): ?>
                                        <div class="fw-bold text-primary">
                                            <i class="bi bi-person-check-fill me-1"></i><?= Html::encode($u->personnel->fullName) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= Html::encode($u->personnel->position->name_th ?? '') ?> | รหัส: <?= Html::encode($u->personnel->employee_code) ?>
                                        </div>
                                        <span class="badge bg-light text-primary border" style="font-size: 0.7rem;">
                                            <i class="bi bi-link-45deg me-1"></i>แต่งตั้งจากบุคลากร
                                        </span>
                                    <?php else: ?>
                                        <div><strong><?= Html::encode($u->display_name ?: '-') ?></strong></div>
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                            <i class="bi bi-hdd-network me-1"></i>บัญชีระบบเฉพาะ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::encode($u->email) ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= Html::encode($roleLabel) ?></span>
                                </td>
                                <td>
                                    <span class="text-secondary small"><i class="bi bi-building me-1"></i><?= Html::encode($scopeDesc) ?></span>
                                </td>
                                <td>
                                    <?php if ($u->status == \common\models\User::STATUS_ACTIVE): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>ใช้งานได้</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ปิดใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canManage): ?>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $u->id], [
                                                'class' => 'btn btn-outline-primary',
                                                'title' => 'แก้ไขข้อมูล',
                                            ]) ?>

                                            <?php if (!$isSelf && !$isPrimaryAdmin): ?>
                                                <?= Html::a(
                                                    $u->status == \common\models\User::STATUS_ACTIVE ? '<i class="bi bi-slash-circle"></i>' : '<i class="bi bi-check-circle"></i>',
                                                    ['toggle-status', 'id' => $u->id],
                                                    [
                                                        'class' => $u->status == \common\models\User::STATUS_ACTIVE ? 'btn btn-outline-warning' : 'btn btn-outline-success',
                                                        'title' => $u->status == \common\models\User::STATUS_ACTIVE ? 'ระงับการใช้งาน' : 'เปิดใช้งาน',
                                                        'data-method' => 'post',
                                                        'data-confirm' => 'คุณต้องการ' . ($u->status == \common\models\User::STATUS_ACTIVE ? 'ระงับ' : 'เปิด') . 'การใช้งานบัญชี ' . Html::encode($u->username) . ' ใช่หรือไม่?',
                                                    ]
                                                ) ?>
                                                <?php if ($isPersonnel): ?>
                                                    <?= Html::a('<i class="bi bi-person-dash"></i>', ['delete', 'id' => $u->id], [
                                                        'class' => 'btn btn-outline-danger',
                                                        'title' => 'ถอดถอนสิทธิ์ผู้ดูแลระบบ (บัญชีบุคลากรยังคงอยู่)',
                                                        'data-method' => 'post',
                                                        'data-confirm' => 'คุณต้องการถอดถอนสิทธิ์ผู้ดูแลระบบของ "' . Html::encode($u->personnel->fullName) . '" ใช่หรือไม่? (บัญชีและข้อมูลบุคลากรทั่วไปจะยังคงใช้งานได้ตามปกติ)',
                                                    ]) ?>
                                                <?php else: ?>
                                                    <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $u->id], [
                                                        'class' => 'btn btn-outline-danger',
                                                        'title' => 'ลบบัญชีผู้ดูแลระบบเฉพาะกิจ',
                                                        'data-method' => 'post',
                                                        'data-confirm' => 'คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีผู้ดูแลระบบ "' . Html::encode($u->username) . '" ออกจากระบบ?',
                                                    ]) ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary disabled" title="บัญชีหลักหรือบัญชีที่กำลังใช้งานไม่สามารถปิดหรือลบได้"><i class="bi bi-lock"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
