<?php

namespace App\Repositories\Contracts;

use App\Models\JenisGangguan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface JenisGangguanRepositoryInterface
{
    public function getAll(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findById(string $id): ?JenisGangguan;
    public function create(array $data): JenisGangguan;
    public function update(JenisGangguan $jenisGangguan, array $data): JenisGangguan;
    public function delete(JenisGangguan $jenisGangguan): bool;
}
