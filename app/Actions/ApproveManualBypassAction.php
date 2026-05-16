<?php

namespace App\Actions;

use App\Exceptions\Bypass\BypassExpiredException;
use App\Exceptions\Bypass\MockLocationNeverBypassableException;
use App\Jobs\ProcessCheckinPhoto;
use App\Models\Attendance;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\DashboardCacheInvalidator;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * ApproveManualBypassAction — Design §5.1.
 *
 * Approves a pending bypass request, creates attendance from the stored officer bundle,
 * dispatches photo processing, invalidates cache, and notifies the officer.
 *
 * (R5.4, R5.5, R5.7–R5.15, P7)
 */
final class ApproveManualBypassAction
{
    public function __construct(
        private readonly ManualBypassApprovalRepositoryInterface $bypassRepo,
        private readonly AttendanceRepositoryInterface $attendanceRepo,
        private readonly AuditService $auditService,
        private readonly DashboardCacheInvalidator $cacheInvalidator,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @throws MockLocationNeverBypassableException
     * @throws BypassExpiredException
     * @throws AccessDeniedHttpException
     */
    public function __invoke(string $bypassId, string $reviewerNote, User $reviewer): Attendance
    {
        return DB::transaction(function () use ($bypassId, $reviewerNote, $reviewer) {
            // Find pending bypass with lock for safe state transition
            $bypass = $this->bypassRepo->findPendingForUpdate($bypassId);

            $bypass->loadMissing(['assignment.location', 'assignment.shift']);

            // Defense-in-depth: mock_location is never bypassable (R5.15)
            if ($bypass->bypass_reason === 'MOCK_LOCATION_DETECTED') {
                throw new MockLocationNeverBypassableException;
            }

            // Assert not expired (R5.8)
            if ($bypass->expires_at && $bypass->expires_at->isPast()) {
                throw new BypassExpiredException;
            }

            // Assert same tenant or god admin (R5.10)
            if ($bypass->saker_id !== $reviewer->saker_id && ! $reviewer->isGodAdmin()) {
                throw new AccessDeniedHttpException('Cross-tenant bypass approval not permitted.');
            }

            // Mark approved
            $this->bypassRepo->markApproved($bypass, $reviewer, $reviewerNote);

            // Create attendance from bypass bundle
            $attendance = $this->attendanceRepo->insertFromBypass($bypass);

            // Post-commit side effects
            DB::afterCommit(function () use ($bypass, $attendance, $reviewer, $reviewerNote) {
                // Dispatch photo processing job
                ProcessCheckinPhoto::dispatch($attendance->id);

                // Invalidate dashboard cache
                $this->cacheInvalidator->invalidateFor($attendance);

                // Audit MANUAL_BYPASS_APPROVED
                $this->auditService->log('MANUAL_BYPASS_APPROVED', $bypass, [
                    'reviewed_by' => $reviewer->id,
                    'reviewer_note' => $reviewerNote,
                    'attendance_id' => $attendance->id,
                ]);

                // Notify officer
                $this->notificationService->notifyUser(
                    $bypass->officer_id,
                    $bypass->saker_id,
                    'bypass_approved',
                    'Bypass Disetujui',
                    'Permintaan bypass Anda telah disetujui oleh supervisor.',
                    null,
                    ['bypass_id' => $bypass->id, 'attendance_id' => $attendance->id],
                );
            });

            return $attendance;
        });
    }
}
