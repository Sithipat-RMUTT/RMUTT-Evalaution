<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;

$items = [];

if (!Yii::$app->user->isGuest) {
    $controllerId = Yii::$app->controller ? Yii::$app->controller->id : '';
    $isSuperAdmin = Yii::$app->user->can('superadmin');
    $isCentral = \common\models\Department::isCentralAdmin();
    $canManageTemplate = Yii::$app->user->can('admin') || $isSuperAdmin || Yii::$app->user->can('central_hr') || Yii::$app->user->can('division_head');

    $items = [
        [
            'label' => '<i class="bi bi-speedometer2 me-1"></i> Dashboard',
            'url' => ['/site/index'],
            'active' => $controllerId === 'site',
        ],
        [
            'label' => '<i class="bi bi-ui-checks-grid me-1 text-primary"></i> ติดตามการประเมิน',
            'url' => ['/monitor/index'],
            'active' => $controllerId === 'monitor',
        ],
        [
            'label' => '<i class="bi bi-bar-chart-line-fill me-1 text-success"></i> รายงานสรุปผล/คะแนน',
            'url' => ['/report/index'],
            'active' => $controllerId === 'report',
        ],
        [
            'label' => '<i class="bi bi-people-fill me-1"></i> ข้อมูลบุคลากร',
            'url' => ['/personnel/index'],
            'active' => $controllerId === 'personnel',
        ],
    ];

    if ($canManageTemplate) {
        $items[] = [
            'label' => '<i class="bi bi-file-earmark-ruled-fill me-1 text-warning"></i> จัดการแบบประเมิน',
            'url' => ['/template-builder/index'],
            'active' => $controllerId === 'template-builder',
        ];
    }

    if ($isCentral || $isSuperAdmin) {
        $systemItems = [];
        if ($isSuperAdmin) {
            $systemItems[] = [
                'label' => '<i class="bi bi-shield-lock-fill me-1 text-primary"></i> จัดการผู้ดูแลระบบ (Admin Users)',
                'url' => ['/user/index'],
                'active' => $controllerId === 'user',
            ];
        }
        $systemItems[] = [
            'label' => '<i class="bi bi-calendar3 me-1"></i> รอบการประเมิน',
            'url' => ['/cycle/index'],
            'active' => $controllerId === 'cycle',
        ];

        $items[] = [
            'label' => '<i class="bi bi-gear-fill me-1"></i> จัดการระบบ',
            'items' => $systemItems,
        ];
    }

    $items[] = [
        'label' => '<i class="bi bi-box-arrow-up-right me-1"></i> พอร์ทัลบุคลากร',
        'url' => 'http://127.0.0.1:8000/index.php',
        'linkOptions' => ['target' => '_blank'],
    ];

    $items[] = [
        'label' => '<i class="bi bi-person-fill-gear me-1"></i> ' . Html::encode(Yii::$app->user->identity->username),
        'items' => [
            [
                'label' => '<i class="bi bi-box-arrow-right text-danger me-1"></i> ออกจากระบบ',
                'url' => ['/site/logout'],
                'linkOptions' => ['data-method' => 'post', 'class' => 'text-danger'],
            ],
        ],
    ];
} else {
    $items = [
        [
            'label' => '<i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ Admin',
            'url' => ['/site/login'],
        ],
    ];
}

?>
<header id="header">
    <?php NavBar::begin([
        'brandLabel' => '<div class="d-flex align-items-center"><img src="' . Yii::$app->request->baseUrl . '/images/rmutt-logo.png" alt="RMUTT Logo" class="me-2" style="height: 38px; width: auto; object-fit: contain;"><div><div class="fw-bold lh-1 text-white">ระบบประเมินผลการปฏิบัติงาน</div><small class="text-white-50" style="font-size: 0.72rem;">ระบบบริหารจัดการงานบุคคล (HR Admin)</small></div></div>',
        'brandUrl' => ['/site/index'],
        'options' => ['class' => 'navbar navbar-expand-lg navbar-admin fixed-top'],
        'innerContainerOptions' => ['class' => 'container-fluid px-4'],
    ]) ?>

    <?= Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto align-items-center'],
        'encodeLabels' => false,
        'items' => $items,
    ]) ?>

    <?php NavBar::end() ?>
</header>
