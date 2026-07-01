<?php

namespace App\Repositories;

use App\Models\Zone;
use App\Repositories\Contracts\ZoneRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ZoneRepository implements ZoneRepositoryInterface
{
    public function findById(string $id): ?Zone
    {
        return Zone::find($id);
    }

    public function find(string $id): ?Zone
    {
        return Zone::find($id);
    }

    public function findOrFail(string $id): Zone
    {
        return Zone::findOrFail($id);
    }

    public function findBySaker(string $sakerId): Collection
    {
        return Zone::where('saker_id', $sakerId)->orderBy('name')->get();
    }

    public function byOperation(string $operationId): Collection
    {
        return Zone::where('operation_id', $operationId)
            ->where('is_active', true)
            ->withCount('locations')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Zone
    {
        return Zone::create($data);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Zone::with(['operation:id,name,operation_type,status', 'saker:id,code,type'])
            ->withCount('locations');

        if (! empty($filters['operation_id'])) {
            $query->where('operation_id', $filters['operation_id']);
        } else {
            // Hide zones from archived operations by default
            $query->whereHas('operation', fn ($q) => $q->where('status', '!=', 'archived'));
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }
}
