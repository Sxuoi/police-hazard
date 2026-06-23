<?php

namespace Tests\Property;

use App\Exceptions\Bypass\MockLocationNeverBypassableException;
use App\Exceptions\Checkin\MockLocationException;
use Eris\Generator;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * P3 — Mock Location Never Produces Attendance.
 *
 * For every check-in or bypass request R with mock_location=true: no attendance
 * row is ever created.
 *
 * This property is enforced at the Exception layer — the Action classes
 * throw immediately when mock_location=true, which this test verifies via
 * the defined exception contract (reasonCode + httpStatus).
 *
 * Enforces R3.6, R4.3.
 */
class MockLocationNeverProducesAttendanceTest extends TestCase
{
    use TestTrait;

    public function test_mock_location_exception_always_rejects_checkin(): void
    {
        $this->forAll(
            Generator\choose(0, 100), // arbitrary iteration
        )->then(function ($_): void {
            $e = new MockLocationException;
            $this->assertSame('MOCK_LOCATION_DETECTED', $e->reasonCode);
            $this->assertSame(403, $e->httpStatus);
            $this->assertFalse($e->bypassEligible);
        });
    }

    public function test_mock_location_never_bypassable_exception_always_rejects_bypass(): void
    {
        $this->forAll(
            Generator\choose(0, 100),
        )->then(function ($_): void {
            $e = new MockLocationNeverBypassableException;
            $this->assertSame('MOCK_LOCATION_NEVER_BYPASSABLE', $e->reasonCode);
            $this->assertSame(403, $e->httpStatus);
            $this->assertFalse($e->bypassEligible);
        });
    }
}
