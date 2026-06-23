<?php

namespace App\Repositories\Contracts;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UnitRepositoryInterface
{
    public function getAll(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findById(string $id): ?Unit;
    public function create(array $data): Unit;
    public function update(Unit $unit, array $data): Unit;
    public function delete(Unit $unit): bool;
}
