<?php

namespace App\Repositories;

use App\Models\Shift;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ShiftRepository implements ShiftRepositoryInterface
{
    public function findById(string $id): ?Shift
    {
        return Shift::find($id);
    }

    public function findOrFail(string $id): Shift
    {
        return Shift::findOrFail($id);
    }

    public function byLocation(string $locationId): Collection
    {
        return Shift::where('location_id', $locationId)
            ->where('is_active', true)
            ->orderBy('shift_start')
            ->get();
    }

    public function create(array $data): Shift
    {
        return Shift::create($data);
    }

    public function update(Shift $shift, array $data): Shift
    {
        $shift->update($data);
        return $shift->fresh();
    }

    public function delete(Shift $shift): void
    {
        $shift->update(['is_active' => false]);
    }
}
