<?php

namespace Tests\Unit\Services;

use App\Models\Operation;
use App\Services\LocationTimezoneResolver;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationTimezoneResolverTest extends TestCase
{
    private LocationTimezoneResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new LocationTimezoneResolver;
    }

    #[Test]
    public function test_shift_window_wib_timezone(): void
    {
        $operation = new Operation;
        $operation->start_time = '06:00';
        $operation->end_time = '14:00';

        $date = Carbon::parse('2025-01-15', 'Asia/Jakarta');

        [$start, $end] = $this->resolver->shiftWindow($operation, $date, 'Asia/Jakarta');

        // WIB is UTC+7, so 06:00 WIB = 23:00 UTC (previous day)
        $this->assertEquals('2025-01-14 23:00:00', $start->format('Y-m-d H:i:s'));
        // 14:00 WIB = 07:00 UTC
        $this->assertEquals('2025-01-15 07:00:00', $end->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $start->timezoneName);
        $this->assertEquals('UTC', $end->timezoneName);
    }

    #[Test]
    public function test_shift_window_wita_timezone(): void
    {
        $operation = new Operation;
        $operation->start_time = '06:00';
        $operation->end_time = '14:00';

        $date = Carbon::parse('2025-01-15', 'Asia/Makassar');

        [$start, $end] = $this->resolver->shiftWindow($operation, $date, 'Asia/Makassar');

        // WITA is UTC+8, so 06:00 WITA = 22:00 UTC (previous day)
        $this->assertEquals('2025-01-14 22:00:00', $start->format('Y-m-d H:i:s'));
        // 14:00 WITA = 06:00 UTC
        $this->assertEquals('2025-01-15 06:00:00', $end->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $start->timezoneName);
        $this->assertEquals('UTC', $end->timezoneName);
    }

    #[Test]
    public function test_shift_window_wit_timezone(): void
    {
        $operation = new Operation;
        $operation->start_time = '06:00';
        $operation->end_time = '14:00';

        $date = Carbon::parse('2025-01-15', 'Asia/Jayapura');

        [$start, $end] = $this->resolver->shiftWindow($operation, $date, 'Asia/Jayapura');

        // WIT is UTC+9, so 06:00 WIT = 21:00 UTC (previous day)
        $this->assertEquals('2025-01-14 21:00:00', $start->format('Y-m-d H:i:s'));
        // 14:00 WIT = 05:00 UTC
        $this->assertEquals('2025-01-15 05:00:00', $end->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $start->timezoneName);
        $this->assertEquals('UTC', $end->timezoneName);
    }

    #[Test]
    public function test_midnight_spanning_shift(): void
    {
        $operation = new Operation;
        $operation->start_time = '22:00';
        $operation->end_time = '06:00';

        $date = Carbon::parse('2025-01-15', 'Asia/Jakarta');

        [$start, $end] = $this->resolver->shiftWindow($operation, $date, 'Asia/Jakarta');

        // 22:00 WIB on Jan 15 = 15:00 UTC on Jan 15
        $this->assertEquals('2025-01-15 15:00:00', $start->format('Y-m-d H:i:s'));
        // 06:00 WIB on Jan 16 (next day because midnight-spanning) = 23:00 UTC on Jan 15
        $this->assertEquals('2025-01-15 23:00:00', $end->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $start->timezoneName);
        $this->assertEquals('UTC', $end->timezoneName);
    }

    #[Test]
    public function test_tz_abbreviation_returns_correct_values(): void
    {
        $this->assertEquals('WIB', $this->resolver->tzAbbreviation('Asia/Jakarta'));
        $this->assertEquals('WITA', $this->resolver->tzAbbreviation('Asia/Makassar'));
        $this->assertEquals('WIT', $this->resolver->tzAbbreviation('Asia/Jayapura'));
        $this->assertEquals('', $this->resolver->tzAbbreviation('America/New_York'));
        $this->assertEquals('', $this->resolver->tzAbbreviation('Europe/London'));
    }
}
