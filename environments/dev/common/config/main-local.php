<?php

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'rmutt';
$dbUser = getenv('DB_USER') ?: 'rmutt_app';
$dbPass = getenv('DB_PASS');

if (($dbPass === false || $dbPass === null || $dbPass === '') && defined('YII_ENV_PROD') && YII_ENV_PROD) {
    throw new \yii\base\InvalidConfigException('DB_PASS must be configured in environment variables for production.');
}
$dbPass = $dbPass !== false && $dbPass !== null ? $dbPass : '';

return [
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'viewPath' => '@common/mail',
                'useFileTransport' => true,
            ],
        ],
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
            'username' => $dbUser,
            'password' => $dbPass,
            'charset' => 'utf8mb4',
            'tablePrefix' => '',
            'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        'mailer' => \yii\symfonymailer\Mailer::class,
    ],
];
