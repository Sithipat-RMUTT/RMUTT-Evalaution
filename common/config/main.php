<?php

declare(strict_types=1);

return [
    'bootstrap' => [
        \common\bootstrap\MailerBootstrap::class,
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'name' => 'ระบบประเมินผลการปฏิบัติงาน',
    'timeZone' => 'Asia/Bangkok',
    'language' => 'th-TH',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'defaultRoles' => ['guest'],
        ],
    ],
];
