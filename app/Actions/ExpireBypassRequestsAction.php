<?php

namespace App\Actions;

use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * ExpireBypassRequestsAction — Design §4.3.
 *
 * Transitions pending bypass requests past their expires_at to expired status.
 * Audits each expiration and notifies the officer.
 *
 * (R4.13, R4.16)
 */
final class ExpireBypassRequestsAction
{
    public function __construct(
        private readonly ManualBypassApprovalRepositoryInterface $bypassRepo,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    public function __invoke(): void
    {
        $expirableRequests = $this->bypassRepo->listExpirable();

        foreach ($expirableRequests as $bypass) {
            $this->bypassRepo->markExpired($bypass);

            $this->auditService->log('MANUAL_BYPASS_EXPIRED', $bypass, [
                'officer_id' => $bypass->officer_id,
                'bypass_reason' => $bypass->bypass_reason,
            ]);

            $this->notificationService->notifyUser(
                $bypass->officer_id,
                $bypass->saker_id,
                'bypass_expired',
                'Bypass Kedaluwarsa',
                'Permintaan bypass Anda telah kedaluwarsa tanpa keputusan supervisor.',
                null,
                ['bypass_id' => $bypass->id],
            );
        }
    }
}
