<?php
namespace common\services;

use Yii;
use yii\base\InvalidConfigException;

final class DataProtectionService
{
    private static function secret(string $envName, string $paramName): string
    {
        $secret = getenv($envName);
        if (!$secret || strlen($secret) < 32 || str_contains($secret, 'GENERATE_') || str_contains($secret, 'replace-with')) {
            $secret = Yii::$app->params[$paramName] ?? '';
        }
        if ($secret === '' || strlen($secret) < 32 || str_contains($secret, 'GENERATE_') || str_contains($secret, 'replace-with')) {
            throw new InvalidConfigException("Security configuration error: {$envName} must be configured in environment or params with at least 32 characters.");
        }
        return $secret;
    }

    private static function key(): string
    {
        return hash('sha256', self::secret('DATA_ENCRYPTION_KEY', 'dataEncryptionKey'), true);
    }

    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return $plain;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return $encoded;
        }
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 28) {
            throw new \RuntimeException('Encrypted data is malformed');
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new \RuntimeException('Encrypted data could not be decrypted with the configured key');
        }
        return $plain;
    }

    public static function blindIndex(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }
        return hash_hmac('sha256', $plain, hash('sha256', self::secret('DATA_BLIND_INDEX_KEY', 'dataBlindIndexKey'), true));
    }
}
