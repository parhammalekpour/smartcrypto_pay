<?php

namespace App\Support;

class NumberHelper
{
    public static function formatCryptoAmount($value, int $decimals = 8): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '0';
        }

        if (stripos($normalized, 'e') !== false) {
            $normalized = self::scientificToDecimal($normalized);
        }

        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return '0';
        }

        if (function_exists('bcadd')) {
            $rounded = bcadd($normalized, '0', $decimals);
            $rounded = preg_replace('/(\.\d*?)0+$/', '$1', $rounded);
            $rounded = preg_replace('/\.$/', '', $rounded);

            return $rounded === '-0' ? '0' : $rounded;
        }

        $numericValue = (float) $normalized;
        if ($numericValue == 0.0) {
            return '0';
        }

        $formatted = number_format($numericValue, $decimals, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    private static function scientificToDecimal(string $value): string
    {
        if (!preg_match('/^([+-]?\d*\.?\d+)[eE]([+-]?\d+)$/', trim($value), $matches)) {
            return $value;
        }

        [$mantissa, $exponent] = [$matches[1], (int) $matches[2]];
        $sign = '';
        if (str_starts_with($mantissa, '+') || str_starts_with($mantissa, '-')) {
            $sign = $mantissa[0];
            $mantissa = substr($mantissa, 1);
        }

        if (strpos($mantissa, '.') === false) {
            $intPart = $mantissa;
            $fracPart = '';
        } else {
            [$intPart, $fracPart] = explode('.', $mantissa, 2);
        }

        $digits = $intPart . $fracPart;
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return '0';
        }

        $decimalPosition = strlen($intPart) + $exponent;
        if ($decimalPosition <= 0) {
            return $sign . '0.' . str_repeat('0', abs($decimalPosition)) . $digits;
        }

        if ($decimalPosition >= strlen($digits)) {
            return $sign . $digits . str_repeat('0', $decimalPosition - strlen($digits));
        }

        $left = substr($digits, 0, $decimalPosition);
        $right = substr($digits, $decimalPosition);
        $result = $left . '.' . $right;

        return $sign . rtrim(rtrim($result, '0'), '.');
    }
}
