<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when the check-in attempt is outside the shift time window.
 * HTTP 403, bypass-eligible.
 */
final class OutsideShiftWindowException extends CheckinException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'OUTSIDE_SHIFT_WINDOW',
            httpStatus: 403,
            bypassEligible: true,
            extra: $extra,
        );
    }
}
