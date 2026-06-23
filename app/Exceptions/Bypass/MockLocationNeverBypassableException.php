<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when a bypass is attempted for a mock-location rejection.
 * Mock location is permanently non-bypassable per PRD §5.4.
 * HTTP 403.
 */
final class MockLocationNeverBypassableException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'MOCK_LOCATION_NEVER_BYPASSABLE',
            httpStatus: 403,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
