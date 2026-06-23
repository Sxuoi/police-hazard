<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when a supervisor attempts to approve/deny a bypass that has already been decided.
 * HTTP 409.
 */
final class BypassDecisionAlreadyMadeException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'BYPASS_DECISION_ALREADY_MADE',
            httpStatus: 409,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
