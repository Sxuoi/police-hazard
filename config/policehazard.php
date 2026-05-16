<?php

/**
 * Police Hazard — Application-specific configuration.
 * PRD v2.1 constants and defaults.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Application Timezone (Display)
    |--------------------------------------------------------------------------
    | PRD §17 — All timestamps stored as TIMESTAMPTZ (UTC internally).
    | Display timezone: WIB (Asia/Jakarta, UTC+7).
    */
    'timezone' => 'Asia/Jakarta',

    /*
    |--------------------------------------------------------------------------
    | Geofence Defaults
    |--------------------------------------------------------------------------
    | PRD §6.2 — Location geofence configuration.
    */
    'geofence' => [
        'default_radius_meters' => 50,
        'min_radius_meters' => 10,
        'max_radius_meters' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass Token TTL
    |--------------------------------------------------------------------------
    | PRD §5.4 — Manual bypass token expiry times.
    */
    'bypass' => [
        'ph_ttl_minutes' => 15,
        'patrol_ttl_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Spoofing Detection Thresholds
    |--------------------------------------------------------------------------
    | PRD §13.2 — Multi-signal scoring thresholds.
    */
    'spoofing' => [
        'auto_reject_score' => 2,     // Score >= 2 → auto-reject
        'flag_score' => 1,     // Score == 1 → flag for review
        'suspicious_accuracy_meters' => 3.0,   // < 3.0m = suspiciously precise
        'timestamp_drift_seconds' => 60,    // Device vs server delta
        'speed_limit_kmh' => 200,   // Implied speed plausibility
        'network_high_accuracy' => 5.0,   // Network provider with < 5m
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Upload
    |--------------------------------------------------------------------------
    | PRD §13.3 — File upload security.
    | Phase 3 additions: S3 disk, presigned URL TTL, private disk + path.
    */
    'photo' => [
        'max_size_mb' => env('PH_PHOTO_MAX_SIZE_MB', 8),
        'allowed_mimes' => ['image/jpeg', 'image/png'],
        'watermark_retry' => env('PH_PHOTO_WATERMARK_RETRY', 3), // Max retries before marking as failed
        's3_disk' => env('PH_PHOTO_DISK', 's3'),
        'presigned_ttl_min' => env('PH_PHOTO_PRESIGNED_TTL_MIN', 15),
        'private_disk' => env('PH_PHOTO_PRIVATE_DISK', 'local'),
        'private_path' => env('PH_PHOTO_PRIVATE_PATH', 'checkin-photos'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication & Security
    |--------------------------------------------------------------------------
    | PRD §13.1 — Brute force protection.
    | Phase 3 additions: bypass_rate_limit, env()-backed values.
    */
    'auth' => [
        'max_login_attempts' => env('PH_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_minutes' => env('PH_LOCKOUT_MINUTES', 15),
        'token_expiry_hours' => env('PH_TOKEN_EXPIRY_HOURS', 12),
        'checkin_rate_limit' => env('PH_CHECKIN_RATE_LIMIT', 10),  // Attempts per officer per minute
        'bypass_rate_limit' => env('PH_BYPASS_RATE_LIMIT', 5),    // Bypass requests per officer per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard & Cache
    |--------------------------------------------------------------------------
    | PRD §16.2 — Caching strategy.
    */
    'cache' => [
        'dashboard_ttl_seconds' => 30,
        'map_points_ttl_seconds' => 30,
        'recap_ttl_seconds' => 600,
        'locations_ttl_seconds' => 300,
        'logo_ttl_seconds' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Escalation Timers
    |--------------------------------------------------------------------------
    | PRD §11.3 — Bypass request escalation.
    */
    'escalation' => [
        'god_admin_after_minutes' => 5,
        'email_after_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    | Phase 3 — Fallback timezone when no primary Location is found for a Saker.
    | Used by GET /api/v1/officer/assignments when resolving "today" (R2.2).
    */
    'default_timezone' => env('PH_DEFAULT_TIMEZONE', 'Asia/Jakarta'),
];
