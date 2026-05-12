<?php

namespace App\Actions;

use App\Models\Operation;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Validation\ValidationException;

/**
 * PRD §7.3 — Updates an Operation.
 * Operation type is IMMUTABLE after the first Zone is created.
 */
class UpdateOperationAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function execute(Operation $operation, array $data, User $actor): Operation
    {
        // Guard: type is immutable after first zone
        if (
            isset($data['operation_type'])
            && $data['operation_type'] !== $operation->operation_type
            && $operation->zones()->exists()
        ) {
            throw ValidationException::withMessages([
                'operation_type' => ['Tipe operasi tidak dapat diubah setelah zona pertama dibuat.'],
            ]);
        }

        $operation->update([
            'name'           => $data['name'],
            'description'    => $data['description'] ?? $operation->description,
            'operation_type' => $data['operation_type'] ?? $operation->operation_type,
            'status'         => $data['status'] ?? $operation->status,
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'] ?? null,
            'updated_by'     => $actor->id,
        ]);

        $this->auditService->log('OPERATION_UPDATED', $operation, [
            'changes' => $operation->getChanges(),
        ]);

        return $operation->fresh();
    }
}
