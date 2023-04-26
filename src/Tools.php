<?php

namespace MaximeRenou\BingAI;

class Tools
{
    public static $debug = false;

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

    public static function request($url, $headers = [], $data = null, $return_request = false)
    {
        $request = curl_init();
        
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HTTPHEADER, $headers);

        if (! is_null($data)) {
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_POSTFIELDS, $data);
        }

        $data = curl_exec($request);
        $url = curl_getinfo($request, CURLINFO_EFFECTIVE_URL);
        curl_close($request);

        if ($return_request) {
            return [$data, $request, $url];
        }

        return $data;
    }
}