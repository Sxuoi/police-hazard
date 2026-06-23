<?php

namespace App\Repositories;

use App\Models\JenisGangguan;
use App\Repositories\Contracts\JenisGangguanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class JenisGangguanRepository implements JenisGangguanRepositoryInterface
{
    public function getAll(): Collection
    {
        return JenisGangguan::orderBy('nama')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return JenisGangguan::orderBy('nama')->paginate($perPage);
    }

    public function findById(string $id): ?JenisGangguan
    {
        return JenisGangguan::find($id);
    }

    public function create(array $data): JenisGangguan
    {
        return JenisGangguan::create($data);
    }

    public function update(JenisGangguan $jenisGangguan, array $data): JenisGangguan
    {
        $jenisGangguan->update($data);
        return $jenisGangguan;
    }

    public function delete(JenisGangguan $jenisGangguan): bool
    {
        return $jenisGangguan->delete();
    }
}
