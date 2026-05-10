<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function findByEntity(string $entityType, string $entityId): LengthAwarePaginator
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function findByActor(string $actorId): LengthAwarePaginator
    {
        return AuditLog::where('actor_id', $actorId)
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
        if (isset($filters['saker_id'])) {
            $query->where('saker_id', $filters['saker_id']);
        }
        if (isset($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
