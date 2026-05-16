<?php

namespace App\Exceptions\Bypass;

/**
 * Thrown when the bypass request is missing the required photo.
 * HTTP 422.
 */
final class BypassPhotoMissingException extends BypassException
{
    public function __construct(array $extra = [])
    {
        parent::__construct(
            reasonCode: 'BYPASS_PHOTO_MISSING',
            httpStatus: 422,
            bypassEligible: false,
            extra: $extra,
        );
    }
}
