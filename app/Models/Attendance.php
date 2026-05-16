<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance — PRD §6.2. IMMUTABLE — append-only.
 * Check-in records with GPS, photo, spoofing analysis.
 * No updated_at — INSERT only. DB rules prevent UPDATE/DELETE.
 */
#[ScopedBy([SakerScope::class])]
class Attendance extends Model
{
    use HasUuidV7;

    public $timestamps = false; // No updated_at — append-only

    protected $fillable = [
        'assignment_id',
        'officer_id',
        'location_id',
        'saker_id',
        'gps_accuracy_meters',
        'distance_from_point',
        'is_within_geofence',
        'checked_in_at',
        'shift_window_start',
        'shift_window_end',
        'is_within_shift',
        'is_manual_bypass',
        'bypass_approval_id',
        'status',
        'spoofing_score',
        'spoofing_signals',
        'device_metadata',
        'photo_path',
        'photo_raw_path',
        'photo_status',
        'checksum',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_within_geofence' => 'boolean',
            'is_within_shift' => 'boolean',
            'is_manual_bypass' => 'boolean',
            'spoofing_score' => 'integer',
            'spoofing_signals' => 'array',
            'device_metadata' => 'array',
            'checked_in_at' => 'datetime',
            'shift_window_start' => 'datetime',
            'shift_window_end' => 'datetime',
            'created_at' => 'datetime',
            'gps_accuracy_meters' => 'float',
            'distance_from_point' => 'float',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }

    public function bypassApproval(): BelongsTo
    {
        return $this->belongsTo(ManualBypassApproval::class, 'bypass_approval_id');
    }
}
