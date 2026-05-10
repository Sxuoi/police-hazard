<?php

namespace App\Repositories;

use App\Models\Operation;
use App\Repositories\Contracts\OperationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OperationRepository implements OperationRepositoryInterface
{
    public function findById(string $id): ?Operation
    {
        return Operation::find($id);
    }

    public function find(string $id): ?Operation
    {
        return Operation::find($id);
    }

    public function findOrFail(string $id): Operation
    {
        return Operation::findOrFail($id);
    }

    public function findBySaker(string $sakerId): Collection
    {
        return Operation::where('saker_id', $sakerId)->orderByDesc('created_at')->get();
    }

    public function getActive(): Collection
    {
        return Operation::where('status', 'active')->orderBy('name')->get();
    }

    public function allActive(): Collection
    {
        return Operation::whereIn('status', ['active', 'draft'])->orderBy('name')->get();
    }

    public function all(): Collection
    {
        return Operation::orderBy('name')->get();
    }

    public function create(array $data): Operation
    {
        return Operation::create($data);
    }

    public function update(Operation $operation, array $data): Operation
    {
        $operation->update($data);
        return $operation->fresh();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Operation::with('saker:id,code,type')
            ->withCount(['zones', 'assignments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('operation_type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }
}
