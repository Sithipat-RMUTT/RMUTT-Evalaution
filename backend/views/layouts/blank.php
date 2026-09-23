<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

// Register login.css with dynamic cache-busting timestamp so browser always loads latest styles
$cssVersion = @filemtime(Yii::getAlias('@webroot/css/login.css')) ?: time();
$this->registerCssFile('@web/css/login.css?v=' . $cssVersion, [
    'depends' => [AppAsset::class],
]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="rmutt-login-page">
<?php $this->beginBody() ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();