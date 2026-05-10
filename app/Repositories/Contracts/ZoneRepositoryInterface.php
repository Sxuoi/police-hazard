<?php

namespace App\Repositories\Contracts;

use App\Models\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ZoneRepositoryInterface
{
    public function findById(string $id): ?Zone;
    public function findOrFail(string $id): Zone;
    public function find(string $id): ?Zone;
    public function findBySaker(string $sakerId): Collection;
    public function byOperation(string $operationId): Collection;
    public function create(array $data): Zone;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
