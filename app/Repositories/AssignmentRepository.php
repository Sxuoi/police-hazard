<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Models\Concerns\SakerScope;
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
        return Assignment::with([
                'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
                'operation' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            ])
            ->where('officer_id', $officerId)
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
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
            'operation:id,name,operation_type',
        ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('officer', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nrp', 'ilike', "%{$search}%");
            });
        }

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
            $date = $filters['date'];
            $query->where('start_date', '<=', $date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date));
        }

        if (! empty($filters['start_date'])) {
            $query->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $filters['start_date']));
        }

        if (! empty($filters['end_date'])) {
            $query->where('start_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', '!=', 'cancelled');
        }

        return $query->orderByDesc('start_date')->paginate($perPage)->withQueryString();
    }

    public function findForOfficerToday(string $officerId, string $sakerId, ?Carbon $date = null): ?Assignment
    {
        // Resolve "today" using the location's timezone.
        // If no date is provided, we use today in the default timezone as a starting point,
        // then filter by assignments whose location timezone matches "today".
        $today = $date ?? Carbon::today(config('policehazard.default_timezone', 'Asia/Jakarta'));
        $todayStr = $today->toDateString();

        $query = Assignment::with([
                'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
                'operation' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            ])
            ->where('officer_id', $officerId)
            ->where('start_date', '<=', $todayStr)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $todayStr))
            ->whereIn('status', ['active', 'pending']);

        if (request() && request()->input('assignment_id')) {
            $query->where('id', request()->input('assignment_id'));
        }

        return $query->first();
    }

    public function listForOfficer(string $officerId, string $sakerId, Carbon $from, Carbon $to): Collection
    {
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        return Assignment::with([
                'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
                'operation' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            ])
            ->where('assignments.officer_id', $officerId)
            ->where('assignments.start_date', '<=', $toStr)
            ->where(fn ($q) => $q->whereNull('assignments.end_date')->orWhere('assignments.end_date', '>=', $fromStr))
            ->where('assignments.status', '!=', 'cancelled')
            ->join('operations', 'assignments.operation_id', '=', 'operations.id')
            ->orderBy('operations.start_time', 'asc')
            ->select('assignments.*')
            ->get();
    }
}
