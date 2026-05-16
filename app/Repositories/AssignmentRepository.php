<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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

        if (! empty($filters['operation_id'])) {
            $query->where('operation_id', $filters['operation_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['officer_id'])) {
            $query->where('officer_id', $filters['officer_id']);
        }

        if (! empty($filters['date'])) {
            $query->where('assignment_date', $filters['date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('assignment_date')->paginate($perPage)->withQueryString();
    }

    public function findForOfficerToday(string $officerId, string $sakerId, ?Carbon $date = null): ?Assignment
    {
        // Resolve "today" using the location's timezone.
        // If no date is provided, we use today in the default timezone as a starting point,
        // then filter by assignments whose location timezone matches "today".
        $today = $date ?? Carbon::today(config('policehazard.default_timezone', 'Asia/Jakarta'));

        return Assignment::with(['location', 'shift', 'operation'])
            ->where('officer_id', $officerId)
            ->where('saker_id', $sakerId)
            ->where('assignment_date', $today->toDateString())
            ->whereIn('status', ['active', 'pending'])
            ->first();
    }

    public function listForOfficer(string $officerId, string $sakerId, Carbon $from, Carbon $to): Collection
    {
        return Assignment::with(['location', 'shift', 'operation'])
            ->where('officer_id', $officerId)
            ->where('saker_id', $sakerId)
            ->whereBetween('assignment_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->join('shifts', 'assignments.shift_id', '=', 'shifts.id')
            ->orderBy('shifts.shift_start', 'asc')
            ->select('assignments.*')
            ->get();
    }
}
