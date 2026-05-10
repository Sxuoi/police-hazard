<?php

namespace App\Repositories\Contracts;

use App\Models\Operation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OperationRepositoryInterface
{
    public function findById(string $id): ?Operation;
    public function findOrFail(string $id): Operation;
    public function find(string $id): ?Operation;
    public function findBySaker(string $sakerId): Collection;
    public function getActive(): Collection;
    public function allActive(): Collection;
    public function all(): Collection;
    public function create(array $data): Operation;
    public function update(Operation $operation, array $data): Operation;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
