<?php
class Crypto
{
    private static ?string $cachedKey = null;

    private static function getKey(): string
    {
        if (self::$cachedKey !== null) {
            return self::$cachedKey;
        }

        $hex = getenv('ENCRYPTION_KEY');

        if (!$hex || strlen($hex) < 64) {
            $keyFile = DATA_PATH . '/.encryption_key';
            if (!file_exists($keyFile)) {
                $hex = bin2hex(random_bytes(32));
                file_put_contents($keyFile, $hex, LOCK_EX);
                chmod($keyFile, 0600);
            } else {
                $hex = trim(file_get_contents($keyFile));
            }
        }

        $hex = substr(trim((string) $hex), 0, 64);
        if (!preg_match('/^[a-f0-9]{64}$/i', $hex)) {
            throw new RuntimeException('Invalid encryption key format. Expected 64 hex characters.');
        }

        $bin = hex2bin($hex);
        if ($bin === false) {
            throw new RuntimeException('Failed to parse encryption key.');
        }

        self::$cachedKey = $bin;
        return self::$cachedKey;
    }

    public static function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') return $plaintext;

        $key = self::getKey();
        $iv  = random_bytes(12); // 96-bit IV for GCM
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') return $encrypted;

        $key     = self::getKey();
        $decoded = base64_decode($encrypted, true);

        if ($decoded === false || strlen($decoded) < 28) return null;

        $iv         = substr($decoded, 0, 12);
        $tag        = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plaintext === false ? null : $plaintext;
    }
}
