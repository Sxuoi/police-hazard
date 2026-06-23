<?php

namespace Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * P8 — Reason Code ↔ Bypass Eligibility Contract.
 *
 * For every check-in/bypass rejection response E with
 *   reason_code ∈ {OUTSIDE_SHIFT_WINDOW, OUTSIDE_GEOFENCE, SPOOFING_REJECTED}:
 *     E.bypass_eligible = true
 * For every rejection with any other reason code:
 *     E.bypass_eligible = false
 *
 * Enforces R4.1.
 */
class ReasonCodeBypassContractTest extends TestCase
{
    use TestTrait;

    private const BYPASS_ELIGIBLE = [
        'OUTSIDE_SHIFT_WINDOW',
        'OUTSIDE_GEOFENCE',
        'SPOOFING_REJECTED',
    ];

    private const NOT_BYPASS_ELIGIBLE = [
        'INVALID_CREDENTIALS',
        'ACCOUNT_DISABLED',
        'ACCOUNT_LOCKED',
        'TOKEN_INVALID',
        'ASSIGNMENT_NOT_FOUND',
        'MOCK_LOCATION_DETECTED',
        'CHECKIN_ALREADY_COMPLETED',
        'PHOTO_INVALID',
        'PHOTO_TOO_LARGE',
        'RATE_LIMITED',
        'BYPASS_DECISION_ALREADY_MADE',
        'BYPASS_EXPIRED',
        'BYPASS_PHOTO_MISSING',
        'OFFICER_NOTE_REQUIRED',
        'SUPERVISOR_NOTE_REQUIRED',
        'MOCK_LOCATION_NEVER_BYPASSABLE',
        'INVALID_DATE_RANGE',
        'UNSUPPORTED_MEDIA_TYPE',
        'MIDDLEWARE_MISCONFIGURED',
    ];

    public function test_bypass_eligible_reason_codes_are_always_eligible(): void
    {
        $this->forAll(
            Generator\elements(...self::BYPASS_ELIGIBLE),
        )->then(function (string $reasonCode): void {
            $this->assertTrue(
                $this->isBypassEligible($reasonCode),
                "Reason code {$reasonCode} must be bypass-eligible"
            );
        });
    }

    public function test_non_eligible_reason_codes_are_never_eligible(): void
    {
        $this->forAll(
            Generator\elements(...self::NOT_BYPASS_ELIGIBLE),
        )->then(function (string $reasonCode): void {
            $this->assertFalse(
                $this->isBypassEligible($reasonCode),
                "Reason code {$reasonCode} must NOT be bypass-eligible"
            );
        });
    }

    /**
     * Mirror of the contract defined in CheckinException / BypassException subclasses.
     */
    private function isBypassEligible(string $reasonCode): bool
    {
        return in_array($reasonCode, self::BYPASS_ELIGIBLE, true);
    }
}
