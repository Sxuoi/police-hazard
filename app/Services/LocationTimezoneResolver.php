<?php

namespace App\Services;

use App\Models\Operation;
use Carbon\Carbon;

/**
 * Timezone-aware shift window calculation and abbreviation mapping.
 *
 * Design §12 — interprets shift start/end times in the location's
 * IANA timezone, converts to UTC, and handles midnight-spanning shifts.
 */
final class LocationTimezoneResolver
{
    /**
     * Compute the UTC shift window for a given operation on a specific date.
     *
     * Shift times are interpreted in the provided timezone, then converted to UTC.
     * If end_time < start_time (midnight-spanning), the end is placed on the next day.
     *
     * @return array{0: Carbon, 1: Carbon} [start, end] in UTC
     */
    public function shiftWindow(Operation $operation, Carbon $assignmentDate, string $timezone): array
    {
        [$startHour, $startMinute] = explode(':', $operation->start_time);
        $endTime = $operation->end_time ?? '23:59:00';
        [$endHour, $endMinute] = explode(':', $endTime);

        $start = $assignmentDate->copy()
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTime((int) $startHour, (int) $startMinute, 0);

        $end = $assignmentDate->copy()
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTime((int) $endHour, (int) $endMinute, 0);

        // Midnight-spanning: if end time is before or equal to start time, end is next day
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start->utc(), $end->utc()];
    }

    /**
     * Return the Indonesian timezone abbreviation for a given IANA timezone.
     *
     * WIB = Asia/Jakarta (UTC+7), WITA = Asia/Makassar (UTC+8), WIT = Asia/Jayapura (UTC+9).
     * Returns empty string for unknown timezones.
     */
    public function tzAbbreviation(string $timezone): string
    {
        return match ($timezone) {
            'Asia/Jakarta' => 'WIB',
            'Asia/Makassar' => 'WITA',
            'Asia/Jayapura' => 'WIT',
            default => '',
        };
    }
}
