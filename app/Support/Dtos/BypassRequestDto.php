<?php

namespace App\Support\Dtos;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * BypassRequestDto — Immutable value object holding bypass request data.
 * Passed from BypassRequestController → CreateBypassRequestAction.
 */
final readonly class BypassRequestDto
{
    public function __construct(
        public string $assignmentId,
        public string $reasonCode,
        public float $latitude,
        public float $longitude,
        public float $gpsAccuracy,
        public ?float $gpsAltitude,
        public ?float $gpsSpeed,
        public string $gpsProvider,
        public Carbon $timestampDevice,
        public bool $mockLocation,
        public UploadedFile $photo,
        public string $officerNote,
        public ?array $deviceMetadata,
    ) {}
}
