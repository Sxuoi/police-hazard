<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;

/**
 * PRD §20.2 — Attendance repository.
 * INSERT only — table has DB-level rule preventing UPDATE/DELETE.
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
}
