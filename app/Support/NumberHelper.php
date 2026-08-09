<?php

namespace App\Support;

class NumberHelper
{
    public static function formatCryptoAmount($value, int $decimals = 8): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $numericValue = (float) $value;

        if ($numericValue == 0.0) {
            return '0';
        }

        $formatted = number_format($numericValue, $decimals, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
