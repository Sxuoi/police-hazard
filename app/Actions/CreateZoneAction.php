<?php

namespace App\Actions;

use App\Models\Operation;
use App\Models\User;
use App\Models\Zone;
use App\Services\AuditService;

/**
 * PRD §7.4 — Creates a Zone.
 * Also locks the parent Operation's type (immutable after first Zone).
 */
class CreateZoneAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function execute(array $data, User $actor): Zone
    {
        $operation = Operation::findOrFail($data['operation_id']);

        $zone = Zone::create([
            'operation_id' => $operation->id,
            'saker_id'     => $actor->isGodAdmin() ? ($data['saker_id'] ?? $actor->saker_id) : $actor->saker_id,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'is_active'    => true,
            'created_by'   => $actor->id,
            'updated_by'   => $actor->id,
        ]);

        $this->auditService->log('ZONE_CREATED', $zone, [
            'operation_id' => $operation->id,
            'operation_type' => $operation->operation_type,
        ]);

        return $zone;
    }
}
