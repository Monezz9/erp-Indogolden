<?php

namespace App\Support;

class IndoNumber
{
    public static function decimal(mixed $value, int $maxDecimals = 4): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $normalized = number_format((float) $value, $maxDecimals, ',', '.');

        return rtrim(rtrim($normalized, '0'), ',');
    }

    public static function rupiah(mixed $value, int $maxDecimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return 'Rp '.number_format((float) $value, $maxDecimals, ',', '.');
    }

    public static function percent(mixed $value, int $maxDecimals = 2): string
    {
        return self::decimal($value, $maxDecimals).'%';
    }
}
