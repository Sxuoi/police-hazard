<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global query scope for multi-tenant Saker isolation.
 * PRD §4.3, §20.1 — Layer 2 of three-layer tenant isolation.
 *
 * All tenant-scoped models apply this scope automatically.
 * God Admin bypasses via the 'saker.bypass' container flag
 * (set by SetGodAdminContext middleware).
 */
class SakerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // God Admin bypass flag set by SetGodAdminContext middleware
        if (app()->bound('saker.bypass') && app('saker.bypass')) {
            return;
        }

        $sakerId = auth()->user()?->saker_id
            ?? session('saker_id')
            ?? null;

        // During migrations, seeding, or CLI contexts where no auth exists,
        // skip the scope to avoid runtime errors
        if ($sakerId === null) {
            if (app()->runningInConsole()) {
                return;
            }
            throw new \RuntimeException('Saker context not set — cannot query tenant-scoped data without authentication.');
        }

        $builder->where($model->getTable() . '.saker_id', $sakerId);
    }
}
