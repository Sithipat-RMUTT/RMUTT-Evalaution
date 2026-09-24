<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use frontend\assets\AppAsset;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(
    ['charset' => Yii::$app->charset],
    'charset',
);
$this->registerMetaTag(
    [
        'name' => 'viewport',
        'content' => 'width=device-width, initial-scale=1',
    ],
);
if (!empty($this->params['meta_description'])) {
    $this->registerMetaTag(
        [
            'name' => 'description',
            'content' => $this->params['meta_description'],
        ],
    );
}
if (!empty($this->params['meta_keywords'])) {
    $this->registerMetaTag(
        [
            'name' => 'keywords',
            'content' => $this->params['meta_keywords'],
        ],
    );
}
$favVersion = @filemtime(Yii::getAlias('@webroot/favicon.ico')) ?: time();
$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/png',
    'sizes' => '32x32',
    'href' => Yii::getAlias('@web/favicon-32x32.png?v=' . $favVersion),
]);
$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/x-icon',
    'href' => Yii::getAlias('@web/favicon.ico?v=' . $favVersion),
]);
$this->registerLinkTag([
    'rel' => 'apple-touch-icon',
    'sizes' => '180x180',
    'href' => Yii::getAlias('@web/apple-touch-icon.png?v=' . $favVersion),
]);
