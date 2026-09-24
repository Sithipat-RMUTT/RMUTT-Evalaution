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

$favVersion = @filemtime(Yii::getAlias('@webroot/favicon.ico')) ?: time();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Yii::getAlias('@web/favicon-32x32.png?v=' . $favVersion) ?>">
    <link rel="icon" type="image/x-icon" href="<?= Yii::getAlias('@web/favicon.ico?v=' . $favVersion) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Yii::getAlias('@web/apple-touch-icon.png?v=' . $favVersion) ?>">
    <?php $this->head() ?>
</head>
<body class="rmutt-login-page">
<?php $this->beginBody() ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();