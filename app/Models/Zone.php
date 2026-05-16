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
 * Zone — PRD §7.4, §19.1.
 * Operational zones within an operation.
 * Acts as a grouping layer between operations and locations.
 */
#[ScopedBy([SakerScope::class])]
class Zone extends Model
{
    use HasAuditTrail, HasUuidV7;

    protected $fillable = [
        'operation_id',
        'saker_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
