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

    // ── Hierarchy ───────────────────────────────────────────────────

    /**
     * IDs of this Saker plus every descendant (children, grandchildren, …).
     *
     * Used for hierarchical access:
     *   POLDA Saker Admin → POLDA + every POLRESTABES + every POLSEK under them
     *   POLRESTABES Saker Admin → POLRESTABES + every POLSEK under it
     *   POLSEK Saker Admin → POLSEK only
     *
     * Cached for the request lifetime so repeated calls inside the same
     * request (every model query through SakerScope) hit memory, not the DB.
     *
     * @return array<int, string>
     */
    public function descendantIds(): array
    {
        static $cache = [];

        if (isset($cache[$this->id])) {
            return $cache[$this->id];
        }

        $ids = [$this->id];
        $frontier = [$this->id];

        // Iteratively walk the tree breadth-first. The hierarchy is shallow
        // (POLDA → POLRESTABES → POLSEK) so a small loop is fine.
        while (! empty($frontier)) {
            $children = static::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            if (empty($children)) {
                break;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $cache[$this->id] = array_values(array_unique($ids));
    }
}
