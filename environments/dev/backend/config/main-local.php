<?php

declare(strict_types=1);

$cookieKey = getenv('COOKIE_VALIDATION_KEY') ?: getenv('BACKEND_COOKIE_KEY');
if (empty($cookieKey)) {
    if (defined('YII_ENV_PROD') && YII_ENV_PROD) {
        throw new \yii\base\InvalidConfigException('COOKIE_VALIDATION_KEY must be configured in environment variables for production.');
    }
    $cookieKey = 'dev_rmutt_cookie_key_shared_evaluation_system_2026';
}

$config = [
    'components' => [
        'request' => [
            'cookieValidationKey' => $cookieKey,
        ],
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;
