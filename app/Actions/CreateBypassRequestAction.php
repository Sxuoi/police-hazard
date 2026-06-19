<?php

namespace App\Actions;

use App\Exceptions\Bypass\MockLocationNeverBypassableException;
use App\Exceptions\Bypass\OfficerNoteRequiredException;
use App\Exceptions\Bypass\ReasonCodeNotBypassEligibleException;
use App\Exceptions\Checkin\AssignmentNotFoundException;
use App\Exceptions\Checkin\DuplicateCheckinException;
use App\Exceptions\Checkin\PhotoInvalidException;
use App\Models\ManualBypassApproval;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Support\Dtos\BypassRequestDto;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Ramsey\Uuid\Uuid;

/**
 * CreateBypassRequestAction — Design §4.1.
 *
 * Validates the officer's bypass request, persists the GPS/photo bundle
 * into manual_bypass_approvals, and notifies supervisors.
 *
 * (R4.2–R4.11, P3, P8)
 */
final class CreateBypassRequestAction
{
    private const BYPASS_ELIGIBLE_REASONS = [
        'OUTSIDE_SHIFT_WINDOW',
        'OUTSIDE_GEOFENCE',
        'SPOOFING_REJECTED',
    ];

    public function __construct(
        private readonly AssignmentRepositoryInterface $assignmentRepo,
        private readonly AttendanceRepositoryInterface $attendanceRepo,
        private readonly ManualBypassApprovalRepositoryInterface $bypassRepo,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @throws MockLocationNeverBypassableException
     * @throws ReasonCodeNotBypassEligibleException
     * @throws OfficerNoteRequiredException
     * @throws PhotoInvalidException
     * @throws AssignmentNotFoundException
     * @throws DuplicateCheckinException
     */
    public function __invoke(BypassRequestDto $dto, User $officer): ManualBypassApproval
    {
        // ── Validate: mock_location must be false ────────────────────
        if ($dto->mockLocation) {
            throw new MockLocationNeverBypassableException;
        }

        // ── Validate: reason_code must be bypass-eligible ────────────
        if (! in_array($dto->reasonCode, self::BYPASS_ELIGIBLE_REASONS, true)) {
            throw new ReasonCodeNotBypassEligibleException;
        }

        // ── Validate: officer_note >= 20 chars ───────────────────────
        if (mb_strlen($dto->officerNote) < 20) {
            throw new OfficerNoteRequiredException([
                'min_length' => 20,
                'actual_length' => mb_strlen($dto->officerNote),
            ]);
        }

        // ── Validate: photo magic bytes ──────────────────────────────
        $this->assertValidPhotoMagicBytes($dto);

        // ── Resolve assignment (same as ProcessCheckinAction step 2) ─
        $assignment = $this->assignmentRepo->findForOfficerToday(
            $officer->id,
            $officer->saker_id,
        );

        if (! $assignment) {
            throw new AssignmentNotFoundException;
        }

        $assignment->loadMissing(['location', 'operation']);

        // ── PH duplicate guard (same as ProcessCheckinAction step 9) ─
        if ($assignment->operation->operation_type === 'PH') {
            if ($this->attendanceRepo->verifiedExistsFor($assignment->id)) {
                throw new DuplicateCheckinException;
            }
        }

        // ── Persist photo to private disk (EXIF-stripped, UUID-named) ─
        $rawPath = $this->persistPhoto($dto);

        // ── Determine TTL from config ────────────────────────────────
        $ttl = $assignment->operation->operation_type === 'PH'
            ? (int) config('policehazard.bypass.ph_ttl_minutes', 15)
            : (int) config('policehazard.bypass.patrol_ttl_minutes', 30);

        // ── Create pending bypass inside transaction ─────────────────
        return DB::transaction(function () use ($dto, $officer, $assignment, $rawPath, $ttl) {
            $bypass = $this->bypassRepo->createPending([
                'assignment_id' => $assignment->id,
                'officer_id' => $officer->id,
                'saker_id' => $officer->saker_id,
                'bypass_reason' => $dto->reasonCode,
                'officer_note' => $dto->officerNote,
                'status' => 'pending',
                'officer_latitude' => $dto->latitude,
                'officer_longitude' => $dto->longitude,
                'officer_gps_accuracy' => $dto->gpsAccuracy,
                'officer_gps_altitude' => $dto->gpsAltitude,
                'officer_gps_speed' => $dto->gpsSpeed,
                'officer_gps_provider' => $dto->gpsProvider,
                'officer_photo_path' => $rawPath,
                'officer_device_metadata' => $dto->deviceMetadata,
                'officer_timestamp_device' => $dto->timestampDevice,
                'expires_at' => now()->addMinutes($ttl),
                'escalation_level' => 0,
                'created_at' => now(),
            ]);

            DB::afterCommit(function () use ($bypass) {
                $this->auditService->log('MANUAL_BYPASS_REQUESTED', $bypass, [
                    'reason_code' => $bypass->bypass_reason,
                    'officer_id' => $bypass->officer_id,
                ]);

                $this->notificationService->notifySakerAdmins(
                    $bypass->saker_id,
                    'bypass_requested',
                    'Permintaan Bypass Baru',
                    "Officer meminta bypass: {$bypass->bypass_reason}",
                    null,
                    ['bypass_id' => $bypass->id],
                );
            });

            return $bypass;
        });
    }

    /**
     * Validate photo magic bytes: JPEG (FF D8 FF) or PNG (89 50 4E 47).
     *
     * @throws PhotoInvalidException
     */
    private function assertValidPhotoMagicBytes(BypassRequestDto $dto): void
    {
        $photo = $dto->photo;

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
     * Strip EXIF and persist photo to private disk with UUID filename.
     */
    private function persistPhoto(BypassRequestDto $dto): string
    {
        $uuid = Uuid::uuid7()->toString();
        $tempPath = sys_get_temp_dir().'/'.$uuid.'_stripped.jpg';

        $manager = new ImageManager(new Driver);
        $img = $manager->decodePath($dto->photo->getPathname());
        $img->save($tempPath);

        $disk = config('policehazard.photo.private_disk', 'local');
        $path = config('policehazard.photo.private_path', 'checkin-photos');
        $filename = $uuid.'.jpg';

        Storage::disk($disk)->putFileAs($path, new File($tempPath), $filename);

        @unlink($tempPath);

        return $path.'/'.$filename;
    }
}
