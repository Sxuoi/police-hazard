<?php

namespace App\Repositories;

use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UnitRepository implements UnitRepositoryInterface
{
    public function getAll(): Collection
    {
        return Unit::orderBy('nama_unit')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Unit::orderBy('nama_unit')->paginate($perPage);
    }

    public function findById(string $id): ?Unit
    {
        return Unit::find($id);
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);
        return $unit;
    }

    public function delete(Unit $unit): bool
    {
        return $unit->delete();
    }
}
