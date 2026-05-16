<?php

namespace App\Repositories\Contracts;

use App\Models\ManualBypassApproval;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * ManualBypassApproval repository interface.
 * Bypass requests are append-only with narrow status transitions enforced at DB level.
 */
interface ManualBypassApprovalRepositoryInterface
{
    /**
     * Create a new pending bypass request.
     */
    public function createPending(array $attrs): ManualBypassApproval;

    /**
     * Find a pending bypass request with lockForUpdate for safe state transition.
     */
    public function findPendingForUpdate(string $id): ManualBypassApproval;

    /**
     * Transition bypass to approved status.
     */
    public function markApproved(ManualBypassApproval $bypass, User $reviewer, string $note): void;

    /**
     * Transition bypass to denied status.
     */
    public function markDenied(ManualBypassApproval $bypass, User $reviewer, string $note): void;

    /**
     * Transition bypass to expired status.
     */
    public function markExpired(ManualBypassApproval $bypass): void;

    /**
     * Advance escalation level (0→1→2).
     */
    public function advanceEscalation(ManualBypassApproval $bypass, int $level): void;

    /**
     * Paginated list of bypass requests for supervisor review.
     * Uses SakerScope unless sakerId is null (God Admin sees all).
     */
    public function listForSupervisor(?string $sakerId, array $filters, int $page): LengthAwarePaginator;

    /**
     * List pending bypass requests at a given escalation level that have been pending
     * longer than the specified minutes (for escalation scheduler).
     */
    public function listPendingAtEscalationLevel(int $level, int $afterMinutes): Collection;

    /**
     * List pending bypass requests that have passed their expires_at (for expiration scheduler).
     */
    public function listExpirable(): Collection;
}
