<?php

if (!function_exists('formatNaira')) {
    /**
     * Format an amount into Nigerian Naira format.
     *
     * @param  float  $amount
     * @return string
     */
    function formatNaira($amount)
    {
        $amount = floatval($amount);
        // Check if the amount has a fractional part that's not .00
        $fractionalPart = $amount - floor($amount);
        if ($fractionalPart > 0) {
            return '₦' . number_format($amount, 2, '.', ',');
        }

        // If it doesn't have a fractional part or is .00, just show the whole number
        return '₦' . number_format($amount);
    }
}
