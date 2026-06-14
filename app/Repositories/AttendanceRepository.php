<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ManualBypassApproval;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

/**
 * PRD §20.2 — Attendance repository.
 * INSERT only — table has DB-level rule preventing UPDATE/DELETE.
 * Narrow photo_path/photo_status transitions are allowed via DB rule.
 */
class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function createFromCheckin(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function findByAssignment(string $assignmentId): ?Attendance
    {
        return Attendance::where('assignment_id', $assignmentId)
            ->whereIn('status', ['verified', 'flagged'])
            ->first();
    }

    public function hasDuplicateCheckin(string $assignmentId): bool
    {
        return Attendance::query()
            ->where('assignment_id', $assignmentId)
            ->whereIn('status', ['verified', 'flagged'])
            ->exists();
    }

    public function getCheckinCountForLocation(string $locationId, string $date): int
    {
        return Attendance::query()
            ->where('location_id', $locationId)
            ->whereDate('checked_in_at', $date)
            ->whereIn('status', ['verified', 'flagged'])
            ->count();
    }

    public function verifiedExistsFor(string $assignmentId): bool
    {
        return Attendance::query()
            ->where('assignment_id', $assignmentId)
            ->whereIn('status', ['verified', 'flagged'])
            ->exists();
    }

    public function insertVerified(array $data, string $checksum): Attendance
    {
        return DB::transaction(function () use ($data, $checksum) {
            // Lock the assignment row to serialize concurrent PH attempts
            Assignment::where('id', $data['assignment_id'])->lockForUpdate()->firstOrFail();

            $data['checksum'] = $checksum;

            return Attendance::create($data);
        });
    }

    public function insertFromBypass(ManualBypassApproval $bypass): Attendance
    {
        $checkedInAt = $bypass->officer_timestamp_device ?? $bypass->created_at;
        $lat = $bypass->officer_latitude ?? 0;
        $lng = $bypass->officer_longitude ?? 0;
        $attendanceId = Uuid::uuid7()->toString();

        // Build shift window timestamps from check-in date + shift times
        $checkedInCarbon = ($checkedInAt instanceof \Carbon\Carbon) ? $checkedInAt : Carbon::parse($checkedInAt);
        $dateStr = $checkedInCarbon->format('Y-m-d');
        $shiftStart = $bypass->assignment->shift?->shift_start;
        $shiftEnd = $bypass->assignment->shift?->shift_end;
        $shiftWindowStart = $shiftStart ? Carbon::parse("{$dateStr} {$shiftStart}") : now();
        $shiftWindowEnd = $shiftEnd ? Carbon::parse("{$dateStr} {$shiftEnd}") : now();

        // Compute server-internal checksum
        $checksumParts = [
            $attendanceId,
            $bypass->assignment_id,
            $bypass->officer_id,
            $bypass->assignment->location_id,
            "{$lng},{$lat}",
            ($checkedInAt instanceof \Carbon\Carbon ? $checkedInAt : Carbon::parse($checkedInAt))->toIso8601String(),
            '0', // is_within_geofence = false
            '0', // is_within_shift = false
            '0', // spoofing_score
        ];
        $checksum = hash('sha256', implode('|', $checksumParts));

        DB::statement("
            INSERT INTO attendances (
                id, assignment_id, officer_id, location_id, saker_id,
                gps_accuracy_meters, distance_from_point, is_within_geofence,
                checked_in_at, shift_window_start, shift_window_end, is_within_shift,
                is_manual_bypass, bypass_approval_id, status, spoofing_score,
                spoofing_signals, device_metadata, photo_path, photo_raw_path,
                photo_status, checksum, created_at, checkin_coordinates
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, 0, false,
                ?, ?, ?, false,
                true, ?, 'verified', 0,
                ?::jsonb, ?::jsonb, NULL, ?,
                ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)
            )
        ", [
            $attendanceId,
            $bypass->assignment_id,
            $bypass->officer_id,
            $bypass->assignment->location_id,
            $bypass->saker_id,
            $bypass->officer_gps_accuracy,
            ($checkedInAt instanceof \Carbon\Carbon ? $checkedInAt : Carbon::parse($checkedInAt))->toDateTimeString(),
            $shiftWindowStart->toDateTimeString(),
            $shiftWindowEnd->toDateTimeString(),
            $bypass->id,
            json_encode([]),
            json_encode($bypass->officer_device_metadata ?? []),
            $bypass->officer_photo_path,
            $bypass->officer_photo_path ? 'pending' : null,
            $checksum,
            now()->toDateTimeString(),
            $lng,
            $lat,
        ]);

        return Attendance::withoutGlobalScopes()->findOrFail($attendanceId);
    }

    public function markPhotoProcessed(string $id, string $s3Key): void
    {
        Attendance::where('id', $id)->update([
            'photo_path' => $s3Key,
            'photo_status' => 'processed',
        ]);
    }

    public function markPhotoFailed(string $id): void
    {
        Attendance::where('id', $id)->update([
            'photo_status' => 'failed',
        ]);
    }

    public function listForOfficerHistory(string $officerId, Carbon $from, Carbon $to, int $page): LengthAwarePaginator
    {
        return Attendance::with(['location', 'assignment.shift', 'assignment.operation'])
            ->where('officer_id', $officerId)
            ->whereBetween('checked_in_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderByDesc('checked_in_at')
            ->paginate(15, ['*'], 'page', $page);
    }

    public function findForOfficer(string $id, string $officerId): ?Attendance
    {
        return Attendance::with(['location', 'assignment.shift', 'assignment.operation'])
            ->where('id', $id)
            ->where('officer_id', $officerId)
            ->first();
    }

    public function presignPhotoUrl(string $id): string
    {
        $attendance = Attendance::findOrFail($id);

        $disk = config('policehazard.photo.s3_disk', 's3');
        $ttl = (int) config('policehazard.photo.presigned_ttl_min', 15);

        return Storage::disk($disk)->temporaryUrl(
            $attendance->photo_path,
            now()->addMinutes($ttl)
        );
    }

    public function findOrFail(string $id): Attendance
    {
        return Attendance::findOrFail($id);
    }
}
