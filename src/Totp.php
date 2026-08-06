<?php
class Totp
{
    public static function generateSecret(): string
    {
        return Base32::encode(random_bytes(20));
    }

    public static function getCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice = $timeSlice ?? (int) floor(time() / 30);
        $secretKey = Base32::decode($secret);
        $time      = pack('N*', 0) . pack('N*', $timeSlice);
        $hm        = hash_hmac('sha1', $time, $secretKey, true);
        $offset    = ord($hm[19]) & 0x0f;
        $value     = unpack('N', substr($hm, $offset, 4))[1] & 0x7fffffff;
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code         = trim($code);
        $currentSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (self::getCode($secret, $currentSlice + $i) === $code) {
                return true;
            }
        }
        return false;
    }

    public static function getQrUri(string $secret, string $email, string $issuer = 'Key Wallet'): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        return "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode($issuer) . '&digits=6&period=30';
    }
}
