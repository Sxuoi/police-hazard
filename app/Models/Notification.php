<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification — PRD §6.2, §11.2.
 * Custom notification system (NOT Laravel's default).
 * read_at is the only mutable field.
 */
#[ScopedBy([SakerScope::class])]
class Notification extends Model
{
    use HasUuidV7;

    public $timestamps = false; // Only created_at

    protected $fillable = [
        'recipient_id',
        'saker_id',
        'type',
        'title',
        'body',
        'action_url',
        'payload',
        'read_at',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }
}
