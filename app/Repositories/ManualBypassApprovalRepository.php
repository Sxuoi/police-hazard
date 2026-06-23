<?php

namespace App\Repositories;

use App\Models\ManualBypassApproval;
use App\Models\User;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * ManualBypassApproval repository.
 * Bypass requests are append-only with narrow status transitions enforced at DB level.
 * The DB rule only allows: pending → approved|denied|expired.
 */
class ManualBypassApprovalRepository implements ManualBypassApprovalRepositoryInterface
{
    public function createPending(array $attrs): ManualBypassApproval
    {
        $attrs['status'] = 'pending';

        return ManualBypassApproval::create($attrs);
    }

    public function findPendingForUpdate(string $id): ManualBypassApproval
    {
        return ManualBypassApproval::where('id', $id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function markApproved(ManualBypassApproval $bypass, User $reviewer, string $note): void
    {
        $bypass->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewer_note' => $note,
            'reviewed_at' => now(),
        ]);
    }

    public function markDenied(ManualBypassApproval $bypass, User $reviewer, string $note): void
    {
        $bypass->update([
            'status' => 'denied',
            'reviewed_by' => $reviewer->id,
            'reviewer_note' => $note,
            'reviewed_at' => now(),
        ]);
    }

    public function markExpired(ManualBypassApproval $bypass): void
    {
        $bypass->update([
            'status' => 'expired',
            'reviewed_at' => now(),
        ]);
    }

    public function advanceEscalation(ManualBypassApproval $bypass, int $level): void
    {
        $bypass->update([
            'escalation_level' => $level,
        ]);
    }

    public function listForSupervisor(string|array|null $sakerId, array $filters, int $page): LengthAwarePaginator
    {
        $query = ManualBypassApproval::withoutGlobalScopes()
            ->with(['officer:id,name,nrp', 'assignment.location:id,name', 'reviewer:id,name']);

        // null  → no filter (God Admin sees all).
        // array → restrict to the listed saker_ids (Saker Admin hierarchy).
        // string → legacy single-saker filter.
        if (is_array($sakerId) && ! empty($sakerId)) {
            $query->whereIn('saker_id', $sakerId);
        } elseif (is_string($sakerId) && $sakerId !== '') {
            $query->where('saker_id', $sakerId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['bypass_reason'])) {
            $query->where('bypass_reason', $filters['bypass_reason']);
        }

        if (! empty($filters['officer_id'])) {
            $query->where('officer_id', $filters['officer_id']);
        }

        return $query->orderByDesc('created_at')
            ->paginate(15, ['*'], 'page', $page);
    }

    public function listPendingAtEscalationLevel(int $level, int $afterMinutes): Collection
    {
        return ManualBypassApproval::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('escalation_level', $level)
            ->where('created_at', '<=', now()->subMinutes($afterMinutes))
            ->get();
    }

    public function listExpirable(): Collection
    {
        return ManualBypassApproval::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();
    }
}
