<?php

namespace App\Models\Concerns;

use Ramsey\Uuid\Uuid;

/**
 * Auto-generates UUID v7 primary keys on model creation.
 * PRD §3.3 — UUID v7 (time-ordered) is mandatory. UUID v4 is prohibited.
 */
trait HasUuidV7
{
    public function initializeHasUuidV7(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    protected static function bootHasUuidV7(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Uuid::uuid7()->toString();
            }
        });
    }
}
