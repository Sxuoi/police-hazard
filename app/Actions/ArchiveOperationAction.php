<?php

namespace App\Actions;

use App\Models\Operation;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * PRD §7.3 — Archives an Operation.
 * Blocked if any assignment has status 'pending' or 'active'.
 * Returns HTTP 422 with list of blocking assignments.
 */
class ArchiveOperationAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function execute(Operation $operation, User $actor): Operation
    {
        $blockingAssignments = $operation->assignments()
            ->whereIn('status', ['pending', 'active'])
            ->count();

        if ($blockingAssignments > 0) {
            throw new HttpResponseException(
                response()->json([
                    'message' => "Operasi tidak dapat diarsipkan karena masih terdapat {$blockingAssignments} penugasan aktif. Batalkan semua penugasan terlebih dahulu.",
                    'blocking_count' => $blockingAssignments,
                ], 422)
            );
        }

        $operation->update([
            'status' => 'archived',
            'updated_by' => $actor->id,
        ]);

        $this->auditService->log('OPERATION_ARCHIVED', $operation, [
            'archived_by' => $actor->name,
        ]);

        return $operation->fresh();
    }
}
