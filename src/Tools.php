<?php

namespace MaximeRenou\BingAI;

class Tools
{
    public static bool $debug = false;

    public static function generateUUID()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function debug($message, $data = null)
    {
        if (self::$debug)
            echo "[DEBUG] $message\n";
    }
}