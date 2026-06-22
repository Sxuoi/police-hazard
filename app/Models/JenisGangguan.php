<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\StrictSakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([StrictSakerScope::class])]
class JenisGangguan extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'saker_id',
        'nama',
    ];

    /**
     * Get the Saker this belongs to.
     */
    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class, 'saker_id');
    }
}
