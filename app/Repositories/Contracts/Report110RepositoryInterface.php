<?php

namespace App\Repositories\Contracts;

use App\Models\Report110;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface Report110RepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function getActiveReports(): Collection;
    public function findById(string $id): ?Report110;
    public function findByToken(string $token): ?Report110;
    public function create(array $data): Report110;
    public function update(Report110 $report, array $data): Report110;
    public function updateSpatial(Report110 $report, float $latitude, float $longitude, array $additionalData = []): bool;
    public function delete(Report110 $report): bool;
}
