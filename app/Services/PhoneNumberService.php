<?php

namespace App\Services;

class PhoneNumberService
{
    /**
     * Normalize any Nigerian phone number format into E.164 format (+234...).
     * 
     * Examples:
     * - '09031704109' -> '+2349031704109'
     * - '9031704109'  -> '+2349031704109'
     * - '2349031704109' -> '+2349031704109'
     * - '+234 903 170 4109' -> '+2349031704109'
     */
    public static function normalize(?string $phone): ?string
    {
        if (! $phone) {
            return $phone;
        }

        // Remove all non-numeric characters
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (empty($digits)) {
            return $phone;
        }

        // 11 digits starting with 0 (e.g. 09031704109)
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+234' . substr($digits, 1);
        }

        // 10 digits without leading 0 (e.g. 9031704109)
        if (strlen($digits) === 10) {
            return '+234' . $digits;
        }

        // 13 digits starting with 234 (e.g. 2349031704109)
        if (strlen($digits) === 13 && str_starts_with($digits, '234')) {
            return '+' . $digits;
        }

        // Already formatted or non-standard numeric sequence
        return '+' . ltrim($digits, '+');
    }

    /**
     * Format phone number as international numeric string for WhatsApp wa.me links (e.g. 2349031704109).
     */
    public static function formatForWhatsApp(?string $phone): string
    {
        $normalized = static::normalize($phone);

        if (! $normalized) {
            return '';
        }

        return ltrim(preg_replace('/[^0-9]/', '', $normalized), '+');
    }

    /**
     * Format phone number in local Nigerian format (e.g. 09031704109).
     */
    public static function formatLocal(?string $phone): ?string
    {
        $normalized = static::normalize($phone);

        if (! $normalized) {
            return $phone;
        }

        $digits = preg_replace('/[^0-9]/', '', $normalized);

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '0' . substr($digits, 3);
        }

        return $phone;
    }
}
