<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function find(string $id): ?User
    {
        return User::find($id);
    }

    public function findOrFail(string $id): User
    {
        return User::findOrFail($id);
    }

    public function findByNrp(string $nrp): ?User
    {
        return User::where('nrp', $nrp)->first();
    }

    public function getOfficersBySaker(string $sakerId): Collection
    {
        return User::where('saker_id', $sakerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function searchOfficers(string $query, ?string $sakerId = null): Collection
    {
        return User::withoutGlobalScopes()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('nrp', 'ilike', "%{$query}%");
            })
            ->when($sakerId, fn ($q) => $q->where('saker_id', $sakerId))
            ->with('saker:id,code,type')
            ->limit(20)
            ->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::query();

        // role is removed, all users are officers

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * PRD §7.6 — Officers list with tardiness sort.
     * Tardiness = checked_in_at - shift_start, null if not attended.
     * Default sort: longest tardiness first.
     */
    public function paginateOfficers(int $perPage, array $filters, string $date): LengthAwarePaginator
    {
        $query = User::where('is_active', true)
            ->with(['saker:id,code,type'])
            ->withExists([
                'assignments as has_today_assignment' => fn ($q) => $q->where('start_date', '<=', $date)
                    ->where(fn ($sq) => $sq->whereNull('end_date')->orWhere('end_date', '>=', $date)),
                'attendances as has_attended_today' => fn ($q) => $q->whereDate('checked_in_at', $date),
            ])
            ->when(isset($filters['search']), fn ($q) => $q->where(function ($sq) use ($filters) {
                $sq->where('name', 'ilike', "%{$filters['search']}%")
                    ->orWhere('nrp', 'ilike', "%{$filters['search']}%");
            }))
            ->when(isset($filters['saker_id']), fn ($q) => $q->where('saker_id', $filters['saker_id']))
            ->when(isset($filters['status']) && $filters['status'] === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        return $query->paginate($perPage)->withQueryString();
    }
}
