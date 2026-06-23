<?php

namespace App\Support\Dtos;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * CheckinDto — Immutable value object holding all check-in request data.
 * Passed from CheckinController → ProcessCheckinAction.
 */
final readonly class CheckinDto
{
    public function __construct(
        public string $assignmentId,
        public string $officerId,
        public string $locationId,
        public string $sakerId,
        public float $latitude,
        public float $longitude,
        public float $gpsAccuracy,
        public ?float $gpsAltitude,
        public ?float $gpsSpeed,
        public string $gpsProvider,
        public Carbon $timestampDevice,
        public bool $mockLocation,
        public UploadedFile $photo,
        public Carbon $checkedInAt,
        public bool $isWithinGeofence,
        public bool $isWithinShift,
        public float $distanceFromPoint,
        public int $spoofingScore,
        public array $spoofingSignals,
        public ?array $deviceMetadata,
        public Carbon $shiftWindowStart,
        public Carbon $shiftWindowEnd,
        public string $status,
    ) {}
}
