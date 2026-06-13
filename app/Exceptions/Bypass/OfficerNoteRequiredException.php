<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when the officer's note is missing or too short on a bypass request.
 * HTTP 422.
 */
final class OfficerNoteRequiredException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'OFFICER_NOTE_REQUIRED',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
