<?php

namespace App\Models;

use App\Casts\PostgresArray;
use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shift — PRD §6.2.
 * Time windows for attendance at a location.
 * active_days uses PostgreSQL SMALLINT[] (ISO weekdays: 1=Mon..7=Sun).
 * Not directly tenant-scoped — scoped via location → saker relationship.
 */
class Shift extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'location_id',
        'name',
        'shift_start',
        'shift_end',
        'active_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'active_days' => PostgresArray::class,
            'is_active' => 'boolean',
            'shift_start' => 'string',
            'shift_end' => 'string',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
