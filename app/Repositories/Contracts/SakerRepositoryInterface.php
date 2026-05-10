<?php

namespace App\Repositories\Contracts;

use App\Models\Saker;
use Illuminate\Database\Eloquent\Collection;

interface SakerRepositoryInterface
{
    public function findById(string $id): ?Saker;
    public function findByCode(string $code): ?Saker;
    public function getAll(): Collection;
    public function getChildren(string $parentId): Collection;
    public function create(array $data): Saker;
    public function update(Saker $saker, array $data): Saker;
}
