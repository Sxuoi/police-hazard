<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AssignmentRepository implements AssignmentRepositoryInterface
{
    public function findById(string $id): ?Assignment
    {
        return Assignment::find($id);
    }

    public function find(string $id): ?Assignment
    {
        return Assignment::find($id);
    }

    public function findOrFail(string $id): Assignment
    {
        return Assignment::findOrFail($id);
    }

    public function findByOfficerAndDate(string $officerId, string $date): Collection
    {
        return Assignment::with(['location', 'shift', 'operation'])
            ->where('officer_id', $officerId)
            ->where('assignment_date', $date)
            ->whereIn('status', ['active', 'pending'])
            ->get();
    }

    public function create(array $data): Assignment
    {
        return Assignment::create($data);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Assignment::with([
            'officer:id,name,nrp,saker_id',
            'location:id,name',
            'shift:id,name,shift_start,shift_end',
            'operation:id,name,operation_type',
        ]);

        if (!empty($filters['operation_id'])) {
            $query->where('operation_id', $filters['operation_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['officer_id'])) {
            $query->where('officer_id', $filters['officer_id']);
        }

        if (!empty($filters['date'])) {
            $query->where('assignment_date', $filters['date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('assignment_date')->paginate($perPage)->withQueryString();
    }
}
