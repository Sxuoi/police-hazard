<?php

namespace App\Repositories\Contracts;

use App\Models\Attendance;
use App\Models\ManualBypassApproval;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * PRD §20.2 — Attendance repository interface.
 * All attendance writes are INSERT-only (table has DB-level UPDATE/DELETE prevention).
 * Narrow photo_path/photo_status transitions are allowed via DB rule.
 */
interface AttendanceRepositoryInterface
{
    public function createFromCheckin(array $data): Attendance;

    public function findByAssignment(string $assignmentId): ?Attendance;

    public function getCheckinCountForLocation(string $locationId, string $date): int;

    public function hasDuplicateCheckin(string $assignmentId): bool;

    /**
     * Check if a verified/flagged attendance already exists for the assignment.
     */
    public function verifiedExistsFor(string $assignmentId, ?string $date = null): bool;

    /**
     * Insert a verified attendance with lockForUpdate on the assignment row (PH duplicate guard).
     */
    public function insertVerified(array $data, string $checksum): Attendance;

    /**
     * Create attendance from a bypass approval's stored officer bundle.
     */
    public function insertFromBypass(ManualBypassApproval $bypass): Attendance;

    /**
     * Update photo_path and photo_status to 'processed' (narrow DB rule allows this).
     */
    public function markPhotoProcessed(string $id, string $s3Key): void;

    /**
     * Update photo_status to 'failed' (narrow DB rule allows this).
     */
    public function markPhotoFailed(string $id): void;

    /**
     * Paginated attendance history for an officer within a date range.
     */
    public function listForOfficerHistory(string $officerId, Carbon $from, Carbon $to, int $page): LengthAwarePaginator;

    /**
     * Find a single attendance owned by the officer.
     */
    public function findForOfficer(string $id, string $officerId): ?Attendance;

    /**
     * Return a presigned S3 URL for the attendance photo with TTL from config.
     */
    public function presignPhotoUrl(string $id): string;

    /**
     * Find attendance or throw ModelNotFoundException.
     */
    public function findOrFail(string $id): Attendance;
}
