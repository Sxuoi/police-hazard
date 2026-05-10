<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AssignmentRepositoryInterface
{
    public function findById(string $id): ?Assignment;
    public function findOrFail(string $id): Assignment;
    public function find(string $id): ?Assignment;
    public function findByOfficerAndDate(string $officerId, string $date): Collection;
    public function create(array $data): Assignment;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
