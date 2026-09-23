<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;
use common\models\Personnel;

$user = Yii::$app->user->identity;
$personnel = $user ? Personnel::findOne(['user_id' => $user->id]) : null;

$items = [
    [
        'label' => '<i class="bi bi-house-door-fill me-1"></i> หน้าหลัก',
        'url' => ['/site/index'],
    ],
];

if (!Yii::$app->user->isGuest) {
    $items[] = [
        'label' => '<i class="bi bi-file-earmark-check-fill me-1"></i> ระบบประเมินผล',
        'url' => ['/evaluation/index'],
    ];

    if (Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin')) {
        $items[] = [
            'label' => '<i class="bi bi-gear-fill me-1"></i> ระบบจัดการ (Admin)',
            'url' => 'http://localhost:8001',
            'linkOptions' => ['target' => '_blank'],
        ];
    }

    $displayName = $personnel ? $personnel->fullName : $user->username;
    $roleBadge = '';
    if ($personnel && $personnel->is_supervisor) {
        $roleBadge = ' <span class="badge bg-warning text-dark ms-1" style="font-size:0.75rem;">ผู้ประเมิน</span>';
    }

    $items[] = [
        'label' => '<i class="bi bi-person-circle me-1"></i> ' . Html::encode($displayName) . $roleBadge,
        'items' => [
            ['label' => 'ตำแหน่ง: ' . ($personnel ? $personnel->position->name_th : '-'), 'url' => '#', 'linkOptions' => ['class' => 'dropdown-item disabled text-muted']],
            ['label' => 'สังกัด: ' . ($personnel ? $personnel->department->name_th : '-'), 'url' => '#', 'linkOptions' => ['class' => 'dropdown-item disabled text-muted']],
            '<div class="dropdown-divider"></div>',
            [
                'label' => '<i class="bi bi-box-arrow-right text-danger me-1"></i> ออกจากระบบ',
                'url' => ['/site/logout'],
                'linkOptions' => ['data-method' => 'post', 'class' => 'text-danger'],
            ],
        ],
    ];
} else {
    $items[] = [
        'label' => '<i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ',
        'url' => ['/site/login'],
    ];
}

?>
<header id="header">
    <?php NavBar::begin([
        'brandLabel' => '<div class="d-flex align-items-center"><img src="' . Yii::$app->request->baseUrl . '/images/rmutt-logo.png" alt="RMUTT Logo" class="me-2" style="height: 40px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"><div><div class="fw-bold text-white lh-1 fs-5">ระบบประเมินผลการปฏิบัติงาน</div><small class="text-white-50" style="font-size: 0.72rem; letter-spacing: 0.02em;">มหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี (มทร.ธัญบุรี)</small></div></div>',
        'brandUrl' => ['/site/index'],
        'options' => ['class' => 'navbar navbar-expand-lg navbar-rmutt fixed-top'],
        'innerContainerOptions' => ['class' => 'container-fluid px-4'],
    ]) ?>

    <?= Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto align-items-center'],
        'encodeLabels' => false,
        'items' => $items,
    ]) ?>

    <?php NavBar::end() ?>
</header>
