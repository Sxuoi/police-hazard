<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface AssignmentRepositoryInterface
{
    public function findById(string $id): ?Assignment;

    public function findOrFail(string $id): Assignment;

    public function find(string $id): ?Assignment;

    public function findByOfficerAndDate(string $officerId, string $date): Collection;

    public function create(array $data): Assignment;

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find an active/pending assignment for the officer on the given date (defaults to today).
     * Uses the location's timezone for "today" determination.
     */
    public function findForOfficerToday(string $officerId, string $sakerId, ?Carbon $date = null): ?Assignment;

    /**
     * List assignments for the officer within the date range, excluding cancelled, sorted by shift_start ascending.
     */
    public function listForOfficer(string $officerId, string $sakerId, Carbon $from, Carbon $to): Collection;
}
