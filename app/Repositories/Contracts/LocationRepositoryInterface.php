<?php

namespace App\Repositories\Contracts;

use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LocationRepositoryInterface
{
    public function findById(string $id): ?Location;

    public function findOrFail(string $id): Location;

    public function find(string $id): ?Location;

    public function findBySaker(string $sakerId): Collection;

    public function byZone(string $zoneId): Collection;

    public function create(array $data): Location;

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
