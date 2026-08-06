<?php
class Base32
{
    private static string $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function encode(string $data): string
    {
        $dataSize = strlen($data);
        $res = '';
        $remainder = 0;
        $remainderSize = 0;

        for ($i = 0; $i < $dataSize; $i++) {
            $b = ord($data[$i]);
            $remainder = ($remainder << 8) | $b;
            $remainderSize += 8;
            while ($remainderSize > 4) {
                $remainderSize -= 5;
                $c = $remainder >> $remainderSize;
                $remainder &= (1 << $remainderSize) - 1;
                $res .= self::$alphabet[$c];
            }
        }
        if ($remainderSize > 0) {
            $remainder <<= (5 - $remainderSize);
            $res .= self::$alphabet[$remainder];
        }

        return $res;
    }

    public static function decode(string $data): string
    {
        $data = strtoupper($data);
        $dataSize = strlen($data);
        $buf = 0;
        $bufSize = 0;
        $res = '';

        for ($i = 0; $i < $dataSize; $i++) {
            $c = $data[$i];
            if ($c === '=') break;
            $b = strpos(self::$alphabet, $c);
            if ($b === false) continue;
            $buf = ($buf << 5) | $b;
            $bufSize += 5;
            if ($bufSize >= 8) {
                $bufSize -= 8;
                $res .= chr($buf >> $bufSize);
                $buf &= (1 << $bufSize) - 1;
            }
        }

        return $res;
    }
}
