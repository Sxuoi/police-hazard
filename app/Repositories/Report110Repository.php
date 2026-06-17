<?php

namespace App\Repositories;

use App\Models\Report110;
use App\Repositories\Contracts\Report110RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class Report110Repository implements Report110RepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Report110::selectRaw('reports_110.*, ST_Y(koordinat_110::geometry) as lat, ST_X(koordinat_110::geometry) as lng')
            ->with('unit')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getActiveReports(): Collection
    {
        return Report110::selectRaw('reports_110.*, ST_Y(koordinat_110::geometry) as lat, ST_X(koordinat_110::geometry) as lng')
            ->with('unit')
            ->whereNotNull('koordinat_110')
            ->whereIn('status', ['Sedang penanganan', 'Sudah penanganan'])
            ->get();
    }

    public function findById(string $id): ?Report110
    {
        return Report110::selectRaw('reports_110.*, ST_Y(koordinat_110::geometry) as lat, ST_X(koordinat_110::geometry) as lng')
            ->with('unit')
            ->where('id', $id)
            ->first();
    }

    public function findByToken(string $token): ?Report110
    {
        return Report110::withoutGlobalScope(\App\Models\Concerns\SakerScope::class)
            ->selectRaw('reports_110.*, ST_Y(koordinat_110::geometry) as lat, ST_X(koordinat_110::geometry) as lng')
            ->with('unit')
            ->where('token', $token)
            ->first();
    }

    public function create(array $data): Report110
    {
        return Report110::create($data);
    }

    public function update(Report110 $report, array $data): Report110
    {
        $report->update($data);
        return $report;
    }

    public function updateSpatial(Report110 $report, float $latitude, float $longitude, array $additionalData = []): bool
    {
        $updateData = array_merge($additionalData, [
            'koordinat_110' => DB::raw("ST_GeomFromText('POINT({$longitude} {$latitude})', 4326)")
        ]);

        return $report->update($updateData);
    }

    public function delete(Report110 $report): bool
    {
        return $report->delete();
    }
}
