<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when mock_location is detected on the device.
 * HTTP 403, NOT bypass-eligible (PRD §5.4 — never bypassable).
 */
final class MockLocationException extends CheckinException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'MOCK_LOCATION_DETECTED',
            httpStatus: 403,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
