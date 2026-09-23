<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace frontend\assets;

use common\assets\ColorModeAsset;
use yii\bootstrap5\BootstrapAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
        'css/site.css?v=20260908',
        'css/rmutt-theme.css?v=20260908',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        ColorModeAsset::class,
    ];
}
