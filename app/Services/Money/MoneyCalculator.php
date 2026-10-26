<?php

namespace App\Services\Money;

class MoneyCalculator {
    public static function format(string|int|float $amount): string {
        return self::round((string) $amount, 2);
    }

    public static function add(string $left, string $right, int $scale = 2): string {
        return bcadd($left, $right, $scale);
    }

    public static function sub(string $left, string $right, int $scale = 2): string {
        return bcsub($left, $right, $scale);
    }

    public static function mul(string $left, string $right, int $scale = 4): string {
        return bcmul($left, $right, $scale);
    }

    public static function div(string $left, string $right, int $scale = 4): string {
        return bcdiv($left, $right, $scale);
    }

    public static function compare(string $left, string $right, int $scale = 2): int {
        return bccomp($left, $right, $scale);
    }

    public static function isGreaterThan(string $left, string $right, int $scale = 2): bool {
        return bccomp($left, $right, $scale) === 1;
    }

    public static function isLessThan(string $left, string $right, int $scale = 2): bool {
        return bccomp($left, $right, $scale) === -1;
    }

    public static function isEqual(string $left, string $right, int $scale = 2): bool {
        return bccomp($left, $right, $scale) === 0;
    }

    public static function isZero(string $value, int $scale = 2): bool {
        return bccomp($value, '0.00', $scale) === 0;
    }

    public static function isPositive(string $value, int $scale = 2): bool {
        return bccomp($value, '0.00', $scale) === 1;
    }

    public static function round(string $value, int $decimals = 2): string {
        $value = trim($value);

        if ($value === '' || $value === '.') {
            return sprintf('0.%0'.$decimals.'d', 0);
        }

        $isNegative = str_starts_with($value, '-');
        if ($isNegative) {
            $value = substr($value, 1);
        }

        $parts = explode('.', $value);
        $integerPart = $parts[0] === '' ? '0' : $parts[0];
        $fractionPart = $parts[1] ?? '';

        if (strlen($fractionPart) < $decimals) {
            $fractionPart = str_pad($fractionPart, $decimals, '0', STR_PAD_RIGHT);
            $result = $integerPart.'.'.$fractionPart;

            return $isNegative ? '-'.$result : $result;
        }

        $truncatedFraction = substr($fractionPart, 0, $decimals);
        $nextDigit = $fractionPart[$decimals] ?? '0';

        $base = $integerPart.($decimals > 0 ? '.'.$truncatedFraction : '');

        if ((int) $nextDigit >= 5) {
            $increment = '0.'.str_repeat('0', max(0, $decimals - 1)).'1';
            if ($decimals === 0) {
                $increment = '1';
            }
            $base = bcadd($base, $increment, $decimals);
        }

        return $isNegative ? '-'.$base : $base;
    }
}
