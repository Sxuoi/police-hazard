<?php

namespace Tests\Property;

use App\Models\Operation;
use App\Services\LocationTimezoneResolver;
use Carbon\Carbon;
use Eris\Generator;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * P6 — Midnight-Spanning Shift Attribution.
 *
 * For every operation with end_time < start_time: the computed shift window's
 * end datetime is on the next calendar day, and the start stays on the
 * assignment_date.
 *
 * Enforces R12.3, R12.4.
 *
 * Unit-style property test — does not require DB access.
 */
class MidnightShiftAttributionTest extends TestCase
{
    use TestTrait;

    public function test_midnight_spanning_shift_end_is_on_next_day(): void
    {
        // Generate pairs where end_time < start_time (midnight-spanning)
        $this->forAll(
            Generator\choose(18, 23),  // start hour (evening)
            Generator\choose(0, 5),    // end hour (morning)
            Generator\elements('Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'),
        )->then(function (int $startHour, int $endHour, string $timezone): void {
            $operation = new Operation;
            $operation->start_time = sprintf('%02d:00', $startHour);
            $operation->end_time = sprintf('%02d:00', $endHour);

            $date = Carbon::parse('2025-06-15', $timezone);
            $resolver = new LocationTimezoneResolver;

            [$start, $end] = $resolver->shiftWindow($operation, $date, $timezone);

            // Convert back to the target timezone for date-day comparison
            $startLocal = $start->copy()->setTimezone($timezone);
            $endLocal = $end->copy()->setTimezone($timezone);

            $this->assertSame('2025-06-15', $startLocal->toDateString(), 'Start must stay on assignment_date');
            $this->assertSame('2025-06-16', $endLocal->toDateString(), 'End must roll over to next day');
        });
    }

    public function test_non_midnight_spanning_shift_stays_on_same_day(): void
    {
        // Generate pairs where end_time > start_time (normal shift)
        $this->forAll(
            Generator\choose(6, 12),
            Generator\choose(13, 22),
            Generator\elements('Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'),
        )->then(function (int $startHour, int $endHour, string $timezone): void {
            $operation = new Operation;
            $operation->start_time = sprintf('%02d:00', $startHour);
            $operation->end_time = sprintf('%02d:00', $endHour);

            $date = Carbon::parse('2025-06-15', $timezone);
            $resolver = new LocationTimezoneResolver;

            [$start, $end] = $resolver->shiftWindow($operation, $date, $timezone);

            $startLocal = $start->copy()->setTimezone($timezone);
            $endLocal = $end->copy()->setTimezone($timezone);

            $this->assertSame('2025-06-15', $startLocal->toDateString());
            $this->assertSame('2025-06-15', $endLocal->toDateString());
        });
    }
}
