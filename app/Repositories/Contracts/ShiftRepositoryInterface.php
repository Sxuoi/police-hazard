<?php

namespace App\Repositories\Contracts;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;

interface ShiftRepositoryInterface
{
    public function findById(string $id): ?Shift;

    public function findOrFail(string $id): Shift;

    public function byLocation(string $locationId): Collection;

    public function create(array $data): Shift;

    public function update(Shift $shift, array $data): Shift;

    public function delete(Shift $shift): void;
}
