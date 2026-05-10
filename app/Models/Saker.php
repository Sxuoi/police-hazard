<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saker (Satuan Kerja) — Organizational Unit.
 * PRD §4.1 — Root tenant entity. NOT tenant-scoped (no SakerScope).
 * Self-referential hierarchy: POLDA → POLRESTABES → POLSEK.
 */
class Saker extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Saker::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Saker::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }
}
