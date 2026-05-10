<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Automatically sets created_by and updated_by fields
 * from the authenticated user on model events.
 */
trait HasAuditTrail
{
    protected static function bootHasAuditTrail(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
