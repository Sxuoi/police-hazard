<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Assignment — PRD §6.2, §7.7.
 * Binds Officer ↔ Location ↔ Shift ↔ Operation.
 * saker_id = officer's home Saker.
 * assigned_saker_id = borrowing Saker (may differ for cross-tenant borrowing).
 * Status lifecycle: pending → active → completed | cancelled.
 */
#[ScopedBy([SakerScope::class])]
class Assignment extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'officer_id',
        'location_id',
        'shift_id',
        'operation_id',
        'saker_id',
        'assigned_saker_id',
        'start_date',
        'end_date',
        'status',
        'notes',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Check if the assignment is active on a given date.
     */
    public function isActiveOn(mixed $date): bool
    {
        $parsedDate = \Illuminate\Support\Carbon::parse($date);
        return $this->start_date->startOfDay()->lte($parsedDate)
            && (is_null($this->end_date) || $this->end_date->endOfDay()->gte($parsedDate));
    }

    // ── Relationships ────────────────────────────────────────────────

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }

    public function assignedSaker(): BelongsTo
    {
        return $this->belongsTo(Saker::class, 'assigned_saker_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function latestAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)->latestOfMany('checked_in_at');
    }
}
