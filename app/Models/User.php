<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model — PRD §2.1, §6.2.
 * Represents Officers/Anggota Lapangan only.
 * NRP is the unique officer identifier for Indonesian police.
 * Tenant-scoped via SakerScope.
 */
#[ScopedBy([SakerScope::class])]
class User extends Authenticatable
{
    use HasApiTokens, HasAuditTrail, HasUuidV7, Notifiable;

    protected $fillable = [
        'saker_id',
        'name',
        'nrp',
        'phone',
        'safung',
        'avatar_path',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // ── Scope & Role Compatibility ───────────────────────────────────

    public function isOfficer(): bool
    {
        return true;
    }

    public function isGodAdmin(): bool
    {
        return false;
    }

    public function accessibleSakerIds(): array
    {
        return [$this->saker_id];
    }

    public function canAccessSaker(?string $sakerId): bool
    {
        return $sakerId === $this->saker_id;
    }

    // ── Relationships ────────────────────────────────────────────────

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class, 'saker_id');
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
