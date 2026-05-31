<?php

namespace App\Actions;

use App\Exceptions\Bypass\BypassExpiredException;
use App\Models\User;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * DenyManualBypassAction — Design §5.
 *
 * Denies a pending bypass request. Does NOT create attendance.
 * Audits the denial and notifies the officer.
 *
 * (R5.6–R5.13)
 */
final class DenyManualBypassAction
{
    public function __construct(
        private readonly ManualBypassApprovalRepositoryInterface $bypassRepo,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @throws BypassExpiredException
     * @throws AccessDeniedHttpException
     */
    public function __invoke(string $bypassId, string $reviewerNote, User $reviewer): void
    {
        DB::transaction(function () use ($bypassId, $reviewerNote, $reviewer) {
            // Find pending bypass with lock for safe state transition
            $bypass = $this->bypassRepo->findPendingForUpdate($bypassId);

            // Assert not expired (R5.8)
            if ($bypass->expires_at && $bypass->expires_at->isPast()) {
                throw new BypassExpiredException;
            }

            // Assert reviewer can access this bypass — God Admin always,
            // Saker Admin if the bypass belongs to their saker or any
            // descendant in the POLDA → POLRESTABES → POLSEK hierarchy (R5.10)
            if (! $reviewer->canAccessSaker($bypass->saker_id)) {
                throw new AccessDeniedHttpException('Cross-tenant bypass denial not permitted.');
            }

            // Mark denied
            $this->bypassRepo->markDenied($bypass, $reviewer, $reviewerNote);

            // Post-commit side effects
            DB::afterCommit(function () use ($bypass, $reviewer, $reviewerNote) {
                // Audit MANUAL_BYPASS_DENIED
                $this->auditService->log('MANUAL_BYPASS_DENIED', $bypass, [
                    'reviewed_by' => $reviewer->id,
                    'reviewer_note' => $reviewerNote,
                ]);

                // Notify officer
                $this->notificationService->notifyUser(
                    $bypass->officer_id,
                    $bypass->saker_id,
                    'bypass_denied',
                    'Bypass Ditolak',
                    'Permintaan bypass Anda telah ditolak oleh supervisor.',
                    null,
                    ['bypass_id' => $bypass->id],
                );
            });
        });
    }
}
