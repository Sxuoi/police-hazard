<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttendanceAmendment — PRD §6.1, §19.1. IMMUTABLE.
 * Correction log for attendance records.
 * Used when admins need to annotate or correct attendance metadata.
 */
class AttendanceAmendment extends Model
{
    use HasUuidV7;

    public $timestamps = false; // Append-only — only created_at

    protected $fillable = [
        'attendance_id',
        'amended_by',
        'reason',
        'field_changed',
        'old_value',
        'new_value',
        'approved_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amended_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
