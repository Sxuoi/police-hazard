<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when the supervisor's note is missing or too short on a bypass decision.
 * HTTP 422.
 */
final class SupervisorNoteRequiredException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'SUPERVISOR_NOTE_REQUIRED',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
