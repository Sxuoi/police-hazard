<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model — PRD §2.1, §6.2.
 * Roles: god_admin, saker_admin, officer.
 * NRP is the unique officer identifier for Indonesian police.
 * Tenant-scoped via SakerScope.
 */
#[ScopedBy([SakerScope::class])]
class User extends Authenticatable
{
    use HasApiTokens, HasUuidV7, HasAuditTrail;

    protected $fillable = [
        'saker_id',
        'name',
        'nrp',
        'email',
        'phone',
        'role',
        'safung',
        'avatar_path',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // ── Role Helpers ─────────────────────────────────────────────────

    public function isGodAdmin(): bool
    {
        return $this->role === 'god_admin';
    }

    public function isSakerAdmin(): bool
    {
        return $this->role === 'saker_admin';
    }

    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'officer_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'officer_id');
    }
}
