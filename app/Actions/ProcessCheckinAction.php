<?php

namespace App\Actions;

use App\Exceptions\Checkin\AssignmentNotFoundException;
use App\Exceptions\Checkin\DuplicateCheckinException;
use App\Exceptions\Checkin\MockLocationException;
use App\Exceptions\Checkin\OutsideGeofenceException;
use App\Exceptions\Checkin\OutsideShiftWindowException;
use App\Exceptions\Checkin\PhotoInvalidException;
use App\Exceptions\Checkin\PhotoTooLargeException;
use App\Exceptions\Checkin\SpoofingRejectedException;
use App\Jobs\ProcessCheckinPhoto;
use App\Models\Attendance;
use App\Models\Concerns\SakerScope;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AuditService;
use App\Services\DashboardCacheInvalidator;
use App\Services\GeofenceService;
use App\Services\LocationTimezoneResolver;
use App\Services\SpoofingDetectionService;
use App\Support\Dtos\CheckinDto;
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Ramsey\Uuid\Uuid;

/**
 * ProcessCheckinAction — Design §3.
 * The 12-step check-in pipeline. The one Action class authorized to insert an Attendance.
 *
 * Invokable: receives a CheckinDto (already validated by the controller/request),
 * runs all validation steps, persists atomically, and dispatches post-commit side effects.
 *
 * (R3, R9, R11.7, R12.3, P1, P2, P6, P9)
 */
class ProcessCheckinAction
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignmentRepo,
        private readonly AttendanceRepositoryInterface $attendanceRepo,
        private readonly GeofenceService $geofenceService,
        private readonly SpoofingDetectionService $spoofingDetectionService,
        private readonly LocationTimezoneResolver $locationTimezoneResolver,
        private readonly DashboardCacheInvalidator $dashboardCacheInvalidator,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Execute the 12-step check-in pipeline.
     *
     * @throws AssignmentNotFoundException
     * @throws OutsideShiftWindowException
     * @throws MockLocationException
     * @throws OutsideGeofenceException
     * @throws SpoofingRejectedException
     * @throws DuplicateCheckinException
     * @throws PhotoInvalidException
     * @throws PhotoTooLargeException
     */
    public function __invoke(CheckinDto $dto): Attendance
    {
        // ── Unconditional audit: CHECKIN_ATTEMPT (R3.17) ─────────────
        $this->auditService->log('CHECKIN_ATTEMPT', null, [
            'officer_id' => $dto->officerId,
            'assignment_id' => $dto->assignmentId,
            'location_id' => $dto->locationId,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
        ]);

        // ── Step 1: Token Auth — handled by middleware ───────────────

        // ── Step 2: Assignment Lookup ────────────────────────────────
        $assignment = $this->assignmentRepo->findForOfficerToday(
            $dto->officerId,
            $dto->sakerId,
        );

        if (! $assignment) {
            throw new AssignmentNotFoundException;
        }

        $assignment->loadMissing([
            'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            'operation' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            'officer',
        ]);

        $location = $assignment->location;
        $operation = $assignment->operation;

        // ── Step 3: Shift Window Check ───────────────────────────────
        $timezone = $location->timezone ?? config('policehazard.default_timezone', 'Asia/Jakarta');

        // Check both today and yesterday's windows to support midnight-spanning shifts
        [$shiftStartToday, $shiftEndToday] = $this->locationTimezoneResolver->shiftWindow(
            $operation,
            Carbon::today($timezone),
            $timezone,
        );

        [$shiftStartYesterday, $shiftEndYesterday] = $this->locationTimezoneResolver->shiftWindow(
            $operation,
            Carbon::yesterday($timezone),
            $timezone,
        );

        $isWithinShift = false;
        $shiftStart = $shiftStartToday;
        $shiftEnd = $shiftEndToday;

        if (now()->between($shiftStartToday, $shiftEndToday)) {
            $isWithinShift = true;
            $shiftStart = $shiftStartToday;
            $shiftEnd = $shiftEndToday;
        } elseif (now()->between($shiftStartYesterday, $shiftEndYesterday)) {
            $isWithinShift = true;
            $shiftStart = $shiftStartYesterday;
            $shiftEnd = $shiftEndYesterday;
        }

        if (! $isWithinShift) {
            $this->auditService->log('CHECKIN_REJECTED', null, [
                'officer_id' => $dto->officerId,
                'reason_code' => 'OUTSIDE_SHIFT_WINDOW',
            ]);
            throw new OutsideShiftWindowException([
                'shift_start' => $shiftStart->toIso8601String(),
                'shift_end' => $shiftEnd->toIso8601String(),
            ]);
        }

        // ── Step 4: Mock Location Detection ──────────────────────────
        if ($dto->mockLocation === true) {
            $this->auditService->log('CHECKIN_REJECTED', null, [
                'officer_id' => $dto->officerId,
                'reason_code' => 'MOCK_LOCATION_DETECTED',
            ]);
            throw new MockLocationException;
        }

        // ── Step 5: Geofence Validation ──────────────────────────────
        $distance = $this->geofenceService->distanceFromLocation($location, $dto->latitude, $dto->longitude);
        $isWithinGeofence = $this->geofenceService->isWithinGeofence($location, $dto->latitude, $dto->longitude);

        if (! $isWithinGeofence) {
            $this->auditService->log('CHECKIN_REJECTED', null, [
                'officer_id' => $dto->officerId,
                'reason_code' => 'OUTSIDE_GEOFENCE',
                'distance_meters' => $distance,
            ]);
            throw new OutsideGeofenceException($distance);
        }

        // ── Step 6: GPS Accuracy Flag ────────────────────────────────
        $spoofingSignals = [];
        $spoofingScore = 0;
        $status = 'verified';

        if ($dto->gpsAccuracy > 50) {
            $spoofingSignals[] = ['signal' => 'LOW_GPS_ACCURACY', 'value' => $dto->gpsAccuracy];
            $status = 'flagged';
        }

        // ── Step 7: Timestamp Drift Flag ─────────────────────────────
        $driftThreshold = (int) config('policehazard.spoofing.timestamp_drift_seconds', 60);
        $drift = abs(now()->diffInSeconds($dto->timestampDevice));

        if ($drift > $driftThreshold) {
            $spoofingSignals[] = ['signal' => 'TIMESTAMP_DRIFT', 'value' => $drift];
            $spoofingScore++;
        }

        // ── Step 8: Spoofing Multi-Signal ────────────────────────────
        $spoofingResult = $this->spoofingDetectionService->score($dto, $assignment->officer);

        // Merge service signals with our local signals
        $spoofingScore += $spoofingResult->score;
        $spoofingSignals = array_merge($spoofingSignals, $spoofingResult->signals);

        $autoRejectScore = (int) config('policehazard.spoofing.auto_reject_score', 2);

        if ($spoofingScore >= $autoRejectScore) {
            $this->auditService->log('CHECKIN_REJECTED', null, [
                'officer_id' => $dto->officerId,
                'reason_code' => 'SPOOFING_REJECTED',
                'spoofing_score' => $spoofingScore,
                'signals' => $spoofingSignals,
            ]);
            throw new SpoofingRejectedException($spoofingSignals);
        }

        // If score == flag threshold, mark as flagged
        $flagScore = (int) config('policehazard.spoofing.flag_score', 1);
        if ($spoofingScore >= $flagScore) {
            $status = 'flagged';
        }

        // ── Step 9: Duplicate Guard (PH only) ────────────────────────
        if ($operation->operation_type === 'PH') {
            if ($this->attendanceRepo->verifiedExistsFor($assignment->id)) {
                $this->auditService->log('CHECKIN_REJECTED', null, [
                    'officer_id' => $dto->officerId,
                    'reason_code' => 'CHECKIN_ALREADY_COMPLETED',
                ]);
                throw new DuplicateCheckinException;
            }
        }

        // ── Step 10: Photo Validation ────────────────────────────────
        $this->validatePhoto($dto);

        // ── Step 11: Atomic Write ────────────────────────────────────
        $attendanceId = Uuid::uuid7()->toString();

        $attendance = DB::transaction(function () use (
            $dto, $assignment, $location, $attendanceId,
            $isWithinGeofence, $isWithinShift, $distance,
            $shiftStart, $shiftEnd, $spoofingScore, $spoofingSignals, $status,
        ) {
            // Strip EXIF from photo via Intervention Image v4 re-encoding
            $tempPath = sys_get_temp_dir().'/'.$attendanceId.'_stripped.jpg';
            $manager = new ImageManager(new Driver);
            $img = $manager->decodePath($dto->photo->getPathname());
            $img->save($tempPath);

            // Persist to private disk
            $disk = config('policehazard.photo.private_disk', 'local');
            $path = config('policehazard.photo.private_path', 'checkin-photos');
            $filename = $attendanceId.'.jpg';

            Storage::disk($disk)->putFileAs($path, new File($tempPath), $filename);

            // Clean up temp file
            @unlink($tempPath);

            $photoRawPath = $path.'/'.$filename;

            // Compute WKB hex for checksum (PostGIS point representation)
            $wkbHex = $this->computeWkbHex($dto->latitude, $dto->longitude);

            // Compute server-internal checksum (R11.7)
            $checksum = $this->computeChecksum(
                $attendanceId,
                $assignment->id,
                $dto->officerId,
                $location->id,
                $wkbHex,
                $dto->checkedInAt,
                $isWithinGeofence,
                $isWithinShift,
                $spoofingScore,
            );

            // Insert via repository (includes lockForUpdate for PH duplicate guard)
            $data = [
                'id' => $attendanceId,
                'assignment_id' => $assignment->id,
                'officer_id' => $dto->officerId,
                'location_id' => $location->id,
                'saker_id' => $dto->sakerId,
                'gps_accuracy_meters' => $dto->gpsAccuracy,
                'distance_from_point' => $distance,
                'is_within_geofence' => $isWithinGeofence,
                'checked_in_at' => $dto->checkedInAt,
                'shift_window_start' => $shiftStart,
                'shift_window_end' => $shiftEnd,
                'is_within_shift' => $isWithinShift,
                'is_manual_bypass' => false,
                'bypass_approval_id' => null,
                'status' => $status,
                'spoofing_score' => $spoofingScore,
                'spoofing_signals' => $spoofingSignals,
                'device_metadata' => $dto->deviceMetadata,
                'photo_path' => null,
                'photo_raw_path' => $photoRawPath,
                'photo_status' => 'pending',
                'created_at' => now(),
            ];

            return $this->attendanceRepo->insertVerified($data, $checksum);
        });

        // ── Step 12: Post-Commit Side Effects ────────────────────────
        DB::afterCommit(function () use ($attendance) {
            // Dispatch photo processing job
            ProcessCheckinPhoto::dispatch($attendance->id);

            // Invalidate dashboard cache
            $this->dashboardCacheInvalidator->invalidateFor($attendance);

            // Audit CHECKIN_VERIFIED (R3.18)
            $this->auditService->log('CHECKIN_VERIFIED', $attendance, [
                'distance_from_point' => $attendance->distance_from_point,
                'spoofing_score' => $attendance->spoofing_score,
                'status' => $attendance->status,
            ]);
        });

        return $attendance;
    }

    /**
     * Validate photo magic bytes and file size.
     *
     * @throws PhotoInvalidException
     * @throws PhotoTooLargeException
     */
    private function validatePhoto(CheckinDto $dto): void
    {
        $photo = $dto->photo;

        // Check file size against config max
        $maxSizeMb = (float) config('policehazard.photo.max_size_mb', 8);
        $maxSizeBytes = $maxSizeMb * 1024 * 1024;

        if ($photo->getSize() > $maxSizeBytes) {
            throw new PhotoTooLargeException([
                'max_size_mb' => $maxSizeMb,
                'actual_size_mb' => round($photo->getSize() / 1024 / 1024, 2),
            ]);
        }

        // Check magic bytes: JPEG (FF D8 FF) or PNG (89 50 4E 47)
        $handle = fopen($photo->getPathname(), 'rb');
        if (! $handle) {
            throw new PhotoInvalidException(['reason' => 'unreadable']);
        }

        $header = fread($handle, 4);
        fclose($handle);

        if ($header === false || strlen($header) < 3) {
            throw new PhotoInvalidException(['reason' => 'empty_or_corrupt']);
        }

        $isJpeg = ord($header[0]) === 0xFF && ord($header[1]) === 0xD8 && ord($header[2]) === 0xFF;
        $isPng = ord($header[0]) === 0x89 && ord($header[1]) === 0x50 && ord($header[2]) === 0x4E && ord($header[3]) === 0x47;

        if (! $isJpeg && ! $isPng) {
            throw new PhotoInvalidException(['reason' => 'invalid_magic_bytes']);
        }
    }

    /**
     * Compute server-internal checksum (R11.7, P9).
     * Deterministic on the row's own fields — any tamper is detectable by recomputing.
     */
    private function computeChecksum(
        string $attendanceId,
        string $assignmentId,
        string $officerId,
        string $locationId,
        string $wkbHex,
        Carbon $checkedInAt,
        bool $isWithinGeofence,
        bool $isWithinShift,
        int $spoofingScore,
    ): string {
        return hash('sha256', implode('|', [
            $attendanceId,
            $assignmentId,
            $officerId,
            $locationId,
            $wkbHex,
            $checkedInAt->toIso8601String(),
            $isWithinGeofence ? '1' : '0',
            $isWithinShift ? '1' : '0',
            (string) $spoofingScore,
        ]));
    }

    /**
     * Compute a WKB hex representation of the GPS point for checksum purposes.
     * Uses WKB format for POINT geometry (SRID 4326).
     */
    private function computeWkbHex(float $latitude, float $longitude): string
    {
        // WKB format: byte order (1 byte) + type (4 bytes) + x (8 bytes) + y (8 bytes)
        // Little-endian, type 1 = Point
        $wkb = pack('C', 1);           // byte order: little-endian
        $wkb .= pack('V', 1);          // geometry type: Point (1)
        $wkb .= pack('d', $longitude); // X coordinate (longitude)
        $wkb .= pack('d', $latitude);  // Y coordinate (latitude)

        return bin2hex($wkb);
    }
}
