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

        static $isResolvingAuth = false;

        if ($isResolvingAuth) {
            return;
        }

        $isResolvingAuth = true;
        try {
            $user = auth()->user() ?? auth('sanctum')->user();
            $sakerId = request()->attributes->get('saker_id')
                ?? $user?->saker_id
                ?? session('saker_id')
                ?? null;
        } finally {
            $isResolvingAuth = false;
        }

        // During migrations, seeding, or CLI contexts where no auth exists,
        // skip the scope to avoid runtime errors
        if ($sakerId === null) {
            if (app()->runningInConsole()) {
                return;
            }
            throw new \RuntimeException('Saker context not set — cannot query tenant-scoped data without authentication.');
        }

        // Hierarchical visibility for Saker Admins (and any non-officer
        // role that reaches this scope): own saker + every descendant.
        // Officers stay strictly own-saker. If we cannot resolve the user
        // (e.g. background job), fall back to strict equality.
        $sakerIds = $user && method_exists($user, 'accessibleSakerIds') && ! $user->isOfficer()
            ? $user->accessibleSakerIds()
            : [$sakerId];

        if (count($sakerIds) === 1) {
            $builder->where($model->getTable().'.saker_id', $sakerIds[0]);
        } else {
            $builder->whereIn($model->getTable().'.saker_id', $sakerIds);
        }
    }
}
