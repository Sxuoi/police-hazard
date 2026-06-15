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
 * Location — PRD §6.2, §7.5, §20.1.
 * Geospatial patrol point with PostGIS coordinates.
 * Coordinates become LOCKED after the first attendance record (§5.6).
 * operating_hours is DISPLAY ONLY.
 */
#[ScopedBy([SakerScope::class])]
class Location extends Model
{
    use HasAuditTrail, HasUuidV7;

    protected $fillable = [
        'zone_id',
        'saker_id',
        'name',
        'description',
        'address',
        'radius_meters',
        'minimum_officer',
        'padal_id',
        'operating_hours',
        'coords_locked',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'coords_locked' => 'boolean',
            'is_active' => 'boolean',
            'radius_meters' => 'integer',
            'minimum_officer' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }

    public function padal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'padal_id');
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
