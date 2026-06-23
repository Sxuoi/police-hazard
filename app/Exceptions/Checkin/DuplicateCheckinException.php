<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when a PH assignment already has a verified attendance.
 * HTTP 409, NOT bypass-eligible.
 */
final class DuplicateCheckinException extends CheckinException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'CHECKIN_ALREADY_COMPLETED',
            httpStatus: 409,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
