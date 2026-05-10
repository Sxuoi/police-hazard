<?php

namespace App\Repositories;

use App\Models\Location;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LocationRepository implements LocationRepositoryInterface
{
    public function findById(string $id): ?Location
    {
        return Location::find($id);
    }

    public function find(string $id): ?Location
    {
        return Location::find($id);
    }

    public function findOrFail(string $id): Location
    {
        return Location::findOrFail($id);
    }

    public function findBySaker(string $sakerId): Collection
    {
        return Location::where('saker_id', $sakerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function byZone(string $zoneId): Collection
    {
        return Location::where('zone_id', $zoneId)
            ->where('is_active', true)
            ->select(['id', 'name', 'address', 'radius_meters', 'minimum_officer'])
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Location
    {
        return Location::create($data);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Location::with(['zone:id,name', 'zone.operation:id,name,operation_type'])
            ->where('is_active', true);

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        if (!empty($filters['operation_id'])) {
            $query->whereHas('zone', fn ($q) => $q->where('operation_id', $filters['operation_id']));
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }
}
