<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Operation — PRD §5.1, §6.2.
 * Deployment operations owned by a Saker.
 * Types: PH (Police Hazard) or PATROL — immutable after first zone.
 * Status lifecycle: draft → active → suspended → completed → archived.
 */
#[ScopedBy([SakerScope::class])]
class Operation extends Model
{
    use HasUuidV7, HasAuditTrail;

    protected $fillable = [
        'saker_id',
        'name',
        'description',
        'operation_type',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
