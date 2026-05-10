<?php

namespace App\Repositories\Contracts;

use App\Models\Attendance;

/**
 * PRD §20.2 — Attendance repository interface.
 * All attendance writes are INSERT-only (table has DB-level UPDATE/DELETE prevention).
 */
interface AttendanceRepositoryInterface
{
    public function createFromCheckin(array $data): Attendance;
    public function findByAssignment(string $assignmentId): ?Attendance;
    public function getCheckinCountForLocation(string $locationId, string $date): int;
    public function hasDuplicateCheckin(string $assignmentId): bool;
}
