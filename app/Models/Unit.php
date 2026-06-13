<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unit Model — PRD §7.
 * Represents rapid response field units responsible for responding to emergency calls.
 */
class Unit extends Model
{
    use HasUuidV7;

    protected $fillable = [
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
}
