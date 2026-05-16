<?php

namespace App\Exceptions\Checkin;

/**
 * Thrown when the spoofing detection score exceeds the auto-reject threshold.
 * HTTP 422, bypass-eligible. Extra carries signals array.
 */
final class SpoofingRejectedException extends CheckinException
{
    public function __construct(array $signals, array $extra = [])
    {
        parent::__construct(
            reasonCode: 'SPOOFING_REJECTED',
            httpStatus: 422,
            bypassEligible: true,
            extra: array_merge(['signals' => $signals], $extra),
        );
    }
}
