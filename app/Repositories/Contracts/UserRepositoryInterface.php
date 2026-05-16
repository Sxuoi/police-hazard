<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findOrFail(string $id): User;

    public function find(string $id): ?User;

    public function findByNrp(string $nrp): ?User;

    public function getOfficersBySaker(string $sakerId): Collection;

    public function searchOfficers(string $query, ?string $sakerId = null): Collection;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function paginateOfficers(int $perPage, array $filters, string $date): LengthAwarePaginator;
}
