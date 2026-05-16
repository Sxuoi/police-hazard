<?php

namespace App\Actions;

use App\Models\Operation;
use App\Models\User;
use App\Services\AuditService;

/**
 * PRD §7.3, §3.2 — Creates a new Operation.
 * Operation type is settable at creation; becomes immutable after first Zone is created.
 */
class CreateOperationAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function execute(array $data, User $actor): Operation
    {
        $operation = Operation::create([
            'saker_id' => $actor->isGodAdmin() ? $data['saker_id'] : $actor->saker_id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'operation_type' => $data['operation_type'],
            'status' => 'draft',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->auditService->log('OPERATION_CREATED', $operation, [
            'name' => $operation->name,
            'type' => $operation->operation_type,
        ]);

        return $operation;
    }
}
