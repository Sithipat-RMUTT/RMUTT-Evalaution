<?php

declare(strict_types=1);

$cookieKey = getenv('COOKIE_VALIDATION_KEY') ?: getenv('FRONTEND_COOKIE_KEY');
if (empty($cookieKey)) {
    throw new \yii\base\InvalidConfigException('COOKIE_VALIDATION_KEY must be configured in environment variables for production.');
}

return [
    'components' => [
        'request' => [
            'cookieValidationKey' => $cookieKey,
        ],
    ],
];
