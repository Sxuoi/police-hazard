<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Strict Global query scope for Saker isolation.
 * Unlike SakerScope which permits descending hierarchical access,
 * this strictly limits access to records created by the exact Saker account itself.
 */
class StrictSakerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
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

        if ($user && method_exists($user, 'isGodAdmin') && $user->isGodAdmin()) {
            return;
        }

        if ($sakerId === null) {
            if (app()->runningInConsole()) {
                return;
            }
            throw new \RuntimeException('Saker context not set — cannot query tenant-scoped data without authentication.');
        }

        // Strict visibility: own saker ONLY.
        $builder->where($model->getTable().'.saker_id', $sakerId);
    }
}
