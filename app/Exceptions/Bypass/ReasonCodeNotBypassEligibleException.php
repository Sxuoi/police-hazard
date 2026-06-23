<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when the reason code on the bypass request is not in the eligible set.
 * HTTP 422.
 */
final class ReasonCodeNotBypassEligibleException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'REASON_CODE_NOT_BYPASS_ELIGIBLE',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
