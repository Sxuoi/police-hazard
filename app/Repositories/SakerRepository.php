<?php

namespace App\Repositories;

use App\Models\Saker;
use App\Repositories\Contracts\SakerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SakerRepository implements SakerRepositoryInterface
{
    public function findById(string $id): ?Saker
    {
        return Saker::find($id);
    }

    public function findByCode(string $code): ?Saker
    {
        return Saker::where('code', $code)->first();
    }

    public function getAll(): Collection
    {
        return Saker::where('is_active', true)->orderBy('name')->get();
    }

    public function getChildren(string $parentId): Collection
    {
        return Saker::where('parent_id', $parentId)->where('is_active', true)->get();
    }

    public function create(array $data): Saker
    {
        return Saker::create($data);
    }

    public function update(Saker $saker, array $data): Saker
    {
        $saker->update($data);

        return $saker->fresh();
    }
}
