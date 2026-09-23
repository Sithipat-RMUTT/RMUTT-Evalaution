<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'dataEncryptionKey' => getenv('DATA_ENCRYPTION_KEY') ?: null,
    'dataBlindIndexKey' => getenv('DATA_BLIND_INDEX_KEY') ?: null,
];
