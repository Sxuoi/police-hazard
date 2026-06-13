<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ManualBypassApproval — PRD §5.4, §19.1. IMMUTABLE.
 * Records bypass requests for OUTSIDE_GEOFENCE or OUTSIDE_SHIFT_WINDOW.
 * MOCK_LOCATION_DETECTED is never eligible for bypass.
 */
#[ScopedBy([SakerScope::class])]
class ManualBypassApproval extends Model
{
    use HasUuidV7;

    public $timestamps = false; // Append-only — only created_at

    protected $fillable = [
        'assignment_id',
        'officer_id',
        'saker_id',
        'bypass_reason',
        'officer_note',
        'status',
        'reviewed_by',
        'reviewer_note',
        'signature_hmac',
        'expires_at',
        'reviewed_at',
        'created_at',
        'escalation_level',
        // Phase 3 — officer-submitted GPS/photo/metadata columns
        'officer_latitude',
        'officer_longitude',
        'officer_gps_accuracy',
        'officer_gps_altitude',
        'officer_gps_speed',
        'officer_gps_provider',
        'officer_photo_path',
        'officer_device_metadata',
        'officer_timestamp_device',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'officer_timestamp_device' => 'datetime',
            'officer_device_metadata' => 'array',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class);
    }
}
