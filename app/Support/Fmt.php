<?php

namespace App\Support;

/** Number and money formatting for storefront pages: Bangla digits and lakh grouping when the store is in Bangla. */
class Fmt
{
    private const BN = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    private static function bangla(): bool
    {
        $store = app()->bound('currentStore') ? app('currentStore') : null;

        return app()->getLocale() === 'bn' && ($store?->look()['digits'] ?? true);
    }

    public static function digits(string|int|float $v): string
    {
        $v = (string) $v;

        return self::bangla() ? strtr($v, self::BN) : $v;
    }

    public static function number(float|int|string $n): string
    {
        $n = (int) round((float) $n);
        $s = (string) abs($n);
        if (app()->getLocale() === 'bn' && strlen($s) > 3) { // 1,50,000
            $s = self::lakh(substr($s, 0, -3), substr($s, -3));
        } else {
            $s = number_format(abs($n));
        }

        return self::digits(($n < 0 ? '-' : '').$s);
    }

    private static function lakh(string $rest, string $last): string
    {
        $parts = [];
        while (strlen($rest) > 2) {
            array_unshift($parts, substr($rest, -2));
            $rest = substr($rest, 0, -2);
        }
        if ($rest !== '') {
            array_unshift($parts, $rest);
        }

        return implode(',', $parts).','.$last;
    }

    public static function money(float|int|string $n): string
    {
        $store = app()->bound('currentStore') ? app('currentStore') : null;
        $cur = $store?->settings['currency'] ?? 'BDT';

        return ($cur === 'BDT' ? '৳' : $cur.' ').self::number($n);
    }

    /** Bangladesh mobile -> wa.me number (8801XXXXXXXXX), or null. */
    public static function whatsapp(?string $phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if (preg_match('/^(?:88)?(01[3-9]\d{8})$/', $d, $m)) {
            return '88'.$m[1];
        }

        return null;
    }
}
