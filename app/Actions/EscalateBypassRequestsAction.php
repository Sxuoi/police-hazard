<?php

namespace App\Actions;

use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\NotificationService;

/**
 * EscalateBypassRequestsAction — Design §4.
 *
 * Advances escalation_level for pending bypass requests:
 * - Level 0→1: pending rows older than god_admin_after_minutes → notify God Admins
 * - Level 1→2: pending rows older than email_after_minutes → send email to Saker Admins
 *
 * (R4.14, R4.15, R4.16)
 */
final class EscalateBypassRequestsAction
{
    public function __construct(
        private readonly ManualBypassApprovalRepositoryInterface $bypassRepo,
        private readonly NotificationService $notificationService,
    ) {}

    public function __invoke(): void
    {
        $this->escalateToLevel1();
        $this->escalateToLevel2();
    }

    /**
     * Level 0→1: pending rows older than god_admin_after_minutes with escalation_level=0.
     * Advance to level 1 and notify God Admins.
     */
    private function escalateToLevel1(): void
    {
        $afterMinutes = (int) config('policehazard.escalation.god_admin_after_minutes', 5);

        $requests = $this->bypassRepo->listPendingAtEscalationLevel(0, $afterMinutes);

        foreach ($requests as $bypass) {
            $this->bypassRepo->advanceEscalation($bypass, 1);

            // Notify God Admins (sakerId=null targets all god admins)
            $this->notificationService->notifySakerAdmins(
                $bypass->saker_id,
                'bypass_escalated',
                'Bypass Eskalasi ke God Admin',
                "Permintaan bypass belum ditangani selama {$afterMinutes} menit.",
                null,
                ['bypass_id' => $bypass->id, 'escalation_level' => 1],
            );
        }
    }

    /**
     * Level 1→2: pending rows older than email_after_minutes with escalation_level=1.
     * Advance to level 2 and send email to Saker Admins.
     */
    private function escalateToLevel2(): void
    {
        $afterMinutes = (int) config('policehazard.escalation.email_after_minutes', 10);

        $requests = $this->bypassRepo->listPendingAtEscalationLevel(1, $afterMinutes);

        foreach ($requests as $bypass) {
            $this->bypassRepo->advanceEscalation($bypass, 2);

            // Send email notification to Saker Admins
            $this->notificationService->notifySakerAdmins(
                $bypass->saker_id,
                'bypass_escalated_email',
                'Bypass Eskalasi - Email',
                "Permintaan bypass belum ditangani selama {$afterMinutes} menit. Diperlukan tindakan segera.",
                null,
                ['bypass_id' => $bypass->id, 'escalation_level' => 2],
            );
        }
    }
}
