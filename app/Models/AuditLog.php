<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — PRD §6.2, §14.1. IMMUTABLE.
 * Global event log. saker_id is nullable (some events are cross-tenant).
 * entity_id is a soft reference (no FK) for flexibility.
 * NOT tenant-scoped — God Admin and Saker Admin both read this table.
 */
class AuditLog extends Model
{
    use HasUuidV7;

    public $timestamps = false; // Append-only — only created_at

    protected $fillable = [
        'actor_id',
        'actor_ip',
        'actor_user_agent',
        'saker_id',
        'event_type',
        'entity_type',
        'entity_id',
        'payload_before',
        'payload_after',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_before' => 'array',
            'payload_after' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }
}
