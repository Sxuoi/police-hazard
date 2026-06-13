<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog;

    public function findByEntity(string $entityType, string $entityId): LengthAwarePaginator;

    public function findByActor(string $actorId): LengthAwarePaginator;

    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;
}
