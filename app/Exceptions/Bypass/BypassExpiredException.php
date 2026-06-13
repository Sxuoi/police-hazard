<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when a bypass request has expired (past its TTL).
 * HTTP 410.
 */
final class BypassExpiredException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'BYPASS_EXPIRED',
            httpStatus: 410,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
