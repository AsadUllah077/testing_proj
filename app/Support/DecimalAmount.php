<?php

namespace App\Support;

use InvalidArgumentException;

final class DecimalAmount
{
    public static function normalize(mixed $value, int $scale = 4): string
    {
        $value = trim((string) ($value ?? '0'));
        if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Accounting amounts must be non-negative decimal values.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        if (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '') {
            throw new InvalidArgumentException("Accounting amounts support at most {$scale} decimal places.");
        }
        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole) . '.' . str_pad(substr($fraction, 0, $scale), $scale, '0');
    }

    public static function isZero(mixed $value, int $scale = 4): bool
    {
        return self::normalize($value, $scale) === '0.' . str_repeat('0', $scale);
    }

    public static function equals(mixed $left, mixed $right, int $scale = 4): bool
    {
        return self::normalize($left, $scale) === self::normalize($right, $scale);
    }

    public static function isSignedZero(mixed $value, int $scale = 4): bool
    {
        return self::isZero(ltrim(trim((string) $value), '+-'), $scale);
    }

    public static function sum(iterable $values, int $scale = 4): string
    {
        $total = '0';
        foreach ($values as $value) {
            $digits = str_replace('.', '', self::normalize($value, $scale));
            $carry = 0;
            $result = '';
            $length = max(strlen($total), strlen($digits));
            $left = str_pad($total, $length, '0', STR_PAD_LEFT);
            $right = str_pad($digits, $length, '0', STR_PAD_LEFT);
            for ($i = $length - 1; $i >= 0; $i--) {
                $sum = (int) $left[$i] + (int) $right[$i] + $carry;
                $result = ($sum % 10) . $result;
                $carry = intdiv($sum, 10);
            }
            $total = ($carry ? (string) $carry : '') . $result;
        }
        $total = str_pad(ltrim($total, '0'), $scale + 1, '0', STR_PAD_LEFT);

        return substr($total, 0, -$scale) . '.' . substr($total, -$scale);
    }
}
