<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Helper to normalize French/English date strings into Carbon instances.
 *
 * Handles:
 * - French long dates: "15 août 2026", "1er janvier 2026"
 * - Relative French: "demain", "aujourd'hui", "hier"
 * - English dates: standard ISO, "tomorrow", "yesterday"
 * - Partial dates: "15/08/2026", "2026-08-15", "15 août"
 * - Generic datetime strings parsable by Carbon/strtotime
 */
class DateNormalizer
{
    /**
     * French month names mapping.
     */
    protected static array $frenchMonths = [
        'janvier' => 'January',
        'février' => 'February', 'fevrier' => 'February',
        'mars' => 'March',
        'avril' => 'April',
        'mai' => 'May',
        'juin' => 'June',
        'juillet' => 'July',
        'août' => 'August', 'aout' => 'August',
        'septembre' => 'September',
        'octobre' => 'October',
        'novembre' => 'November',
        'décembre' => 'December', 'decembre' => 'December',
    ];

    /**
     * Normalize a date string to a Carbon instance.
     *
     * @param  string|null  $value  Raw date string
     * @param  string|null  $defaultTimezone  Timezone (default: config('app.timezone'))
     * @return \Carbon\Carbon|null
     */
    public static function normalize(?string $value, ?string $defaultTimezone = null): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        $timezone = $defaultTimezone ?? (function_exists('config') ? config('app.timezone', 'UTC') : 'UTC');

        // 1. Try direct Carbon parsing first (ISO, "tomorrow", etc.)
        try {
            $carbon = new Carbon($value, $timezone);
            // If it parsed as a valid date (not "1970" from garbage), return it
            if ($carbon->year > 1970) {
                return $carbon;
            }
        } catch (\Exception) {
            // Fall through to French parsing
        }

        // 2. French relative keywords
        $relativeMap = [
            'aujourd\'hui' => 'today',
            "aujourd'hui" => 'today',
            'demain' => 'tomorrow',
            'après-demain' => '+2 days',
            'après demain' => '+2 days',
            'hier' => 'yesterday',
            'ce soir' => 'today 20:00',
            'cet après-midi' => 'today 14:00',
            "cet après-midi" => 'today 14:00',
        ];

        foreach ($relativeMap as $fr => $en) {
            if (mb_strtolower($value) === $fr) {
                return new Carbon($en, $timezone);
            }
        }

        // 3. Replace French month names with English equivalents
        $englishDate = self::replaceFrenchMonths($value);

        // 4. Handle "1er janvier 2026" → "1 January 2026"
        $englishDate = preg_replace('/\b(\d+)(er|ère)\b/i', '$1', $englishDate);

        try {
            return new Carbon($englishDate, $timezone);
        } catch (\Exception) {
            // Fall through
        }

        // 5. Try strtotime as last resort
        $timestamp = @strtotime($englishDate);
        if ($timestamp !== false && $timestamp > 86400) {
            return Carbon::createFromTimestamp($timestamp, $timezone);
        }

        // 6. Try partial formats (e.g., "15/08/2026")
        $formats = [
            'd/m/Y', 'd/m/y', 'd-m-Y', 'd.m.Y',
            'd/m/Y H:i', 'd/m/Y H:i:s',
            'Y/m/d', 'Y-m-d',
            'd M Y', 'd F Y', 'j M Y', 'j F Y',
        ];

        foreach ($formats as $format) {
            try {
                $carbon = Carbon::createFromFormat($format, $value, $timezone);
                if ($carbon && $carbon->year > 1970) {
                    return $carbon;
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * Parse a time string like "14h30", "14:30", "2:30 PM" into a Carbon time.
     *
     * @param  string|null  $value  Time string
     * @return array{hour: int, minute: int}|null
     */
    public static function parseTime(?string $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);

        // French format "14h30" or "14h"
        if (preg_match('/^(\d{1,2})h(\d{2})?$/', $value, $m)) {
            return [
                'hour' => (int) $m[1],
                'minute' => isset($m[2]) ? (int) $m[2] : 0,
            ];
        }

        // Standard formats
        try {
            $carbon = new Carbon($value);
            return [
                'hour' => (int) $carbon->format('H'),
                'minute' => (int) $carbon->format('i'),
            ];
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Merge a date and a time into a single Carbon instance.
     */
    public static function mergeDateAndTime(Carbon $date, array $time): Carbon
    {
        return $date->copy()->setHour($time['hour'])->setMinute($time['minute'])->setSecond(0);
    }

    /**
     * Replace French month names in a string with English equivalents.
     */
    protected static function replaceFrenchMonths(string $value): string
    {
        $search = array_keys(self::$frenchMonths);
        $replace = array_values(self::$frenchMonths);

        return str_ireplace($search, $replace, $value);
    }
}
