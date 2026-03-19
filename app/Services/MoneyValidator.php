<?php

namespace App\Services;

/**
 * Monetary Amount Validator
 * Uses bcmath for precise decimal comparisons
 * CRITICAL: All financial comparisons MUST use this service
 */
class MoneyValidator
{
    /**
     * Compare two monetary amounts with proper precision
     * Uses bcmath for exact decimal comparison (prevents floating point errors)
     *
     * @param float|string|int $amount1 First amount
     * @param float|string|int $amount2 Second amount
     * @param int $precision Decimal places (default 2 for GEL)
     * @return bool True if amounts are exactly equal
     */
    public static function amountsEqual($amount1, $amount2, int $precision = 2): bool
    {
        return bccomp(
            (string)$amount1,
            (string)$amount2,
            $precision
        ) === 0;
    }

    /**
     * Compare monetary amounts
     * @return int -1 if $amount1 < $amount2, 0 if equal, 1 if $amount1 > $amount2
     */
    public static function compare($amount1, $amount2, int $precision = 2): int
    {
        return bccomp(
            (string)$amount1,
            (string)$amount2,
            $precision
        );
    }

    /**
     * Add two monetary amounts
     * @return string Result as string (use bcadd for precision)
     */
    public static function add($amount1, $amount2, int $precision = 2): string
    {
        return bcadd((string)$amount1, (string)$amount2, $precision);
    }

    /**
     * Subtract two monetary amounts
     * @return string Result as string (use bcsub for precision)
     */
    public static function subtract($amount1, $amount2, int $precision = 2): string
    {
        return bcsub((string)$amount1, (string)$amount2, $precision);
    }

    /**
     * Validate amount is positive and within acceptable range
     * @return bool True if amount is valid
     */
    public static function isValid($amount, int $minAmount = 0, int $maxAmount = 999999): bool
    {
        if (bccomp((string)$amount, (string)$minAmount, 2) < 0) {
            return false;
        }

        if (bccomp((string)$amount, (string)$maxAmount, 2) > 0) {
            return false;
        }

        return true;
    }
}
