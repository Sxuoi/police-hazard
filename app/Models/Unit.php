<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\StrictSakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unit Model — PRD §7.
 * Represents rapid response field units responsible for responding to emergency calls.
 */
#[ScopedBy([StrictSakerScope::class])]
class Unit extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'saker_id',
        'nama_unit',
        'no_wa',
    ];

    /**
     * Get all reports assigned to this unit.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report110::class, 'unit_id');
    }

    /**
     * Get the Saker this unit belongs to.
     */
    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class, 'saker_id');
    }
}
