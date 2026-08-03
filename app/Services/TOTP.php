<?php

namespace App\Services;

class TOTP
{
    public static function generateSecret($length = 16)
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // base32
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[ord($bytes[$i]) % strlen($validChars)];
        }
        return $secret;
    }

    public static function getOtpAuthUrl($issuer, $accountName, $secret, $algorithm = 'SHA1', $digits = 6, $period = 30)
    {
        $issuerEnc = rawurlencode($issuer);
        $label = rawurlencode($issuer . ':' . $accountName);
        $secretEnc = $secret;
        return "otpauth://totp/{$label}?secret={$secretEnc}&issuer={$issuerEnc}&algorithm={$algorithm}&digits={$digits}&period={$period}";
    }

    public static function verifyCode($secret, $code, $discrepancy = 1, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = floor(time() / 30);
        }

        $code = str_pad((string) $code, 6, '0', STR_PAD_LEFT);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculated = self::hotp($secret, $timestamp + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    protected static function hotp($secret, $counter, $digits = 6)
    {
        $key = self::base32Decode($secret);
        $counterBytes = pack('J', $counter); // 64-bit big endian (PHP 7.0+)
        // pack('J') produces machine dependent endianness; ensure big-endian
        if (strrev(pack('P', 1)) === pack('P', 1)) {
            // little endian machine: convert
            $counterBytes = strrev($counterBytes);
        }

        $hash = hash_hmac('sha1', $counterBytes, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24) |
                  ((ord($hash[$offset + 1]) & 0xff) << 16) |
                  ((ord($hash[$offset + 2]) & 0xff) << 8) |
                  (ord($hash[$offset + 3]) & 0xff);
        $otp = $binary % pow(10, $digits);
        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    protected static function base32Decode($secret)
    {
        if (empty($secret)) return '';
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6,4,3,1,0];
        if (!in_array($paddingCharCount, $allowedValues)) return false;

        $secret = str_replace('=', '', $secret);
        $secret = strtoupper($secret);
        $binaryString = '';
        for ($i = 0; $i < strlen($secret); $i++) {
            $c = $secret[$i];
            if (!isset($base32charsFlipped[$c])) return false;
            $binaryString .= str_pad(decbin($base32charsFlipped[$c]), 5, '0', STR_PAD_LEFT);
        }

        $eightBits = str_split($binaryString, 8);
        $decoded = '';
        foreach ($eightBits as $bits) {
            if (strlen($bits) === 8) {
                $decoded .= chr(bindec($bits));
            }
        }
        return $decoded;
    }
}
