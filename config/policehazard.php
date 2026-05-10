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
        'min_radius_meters'     => 10,
        'max_radius_meters'     => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass Token TTL
    |--------------------------------------------------------------------------
    | PRD §5.4 — Manual bypass token expiry times.
    */
    'bypass' => [
        'ph_ttl_minutes'     => 15,
        'patrol_ttl_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Spoofing Detection Thresholds
    |--------------------------------------------------------------------------
    | PRD §13.2 — Multi-signal scoring thresholds.
    */
    'spoofing' => [
        'auto_reject_score'          => 2,     // Score >= 2 → auto-reject
        'flag_score'                 => 1,     // Score == 1 → flag for review
        'suspicious_accuracy_meters' => 3.0,   // < 3.0m = suspiciously precise
        'timestamp_drift_seconds'    => 60,    // Device vs server delta
        'speed_limit_kmh'            => 200,   // Implied speed plausibility
        'network_high_accuracy'      => 5.0,   // Network provider with < 5m
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Upload
    |--------------------------------------------------------------------------
    | PRD §13.3 — File upload security.
    */
    'photo' => [
        'max_size_mb'     => 8,
        'allowed_mimes'   => ['image/jpeg', 'image/png'],
        'watermark_retry' => 3, // Max retries before marking as failed
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication & Security
    |--------------------------------------------------------------------------
    | PRD §13.1 — Brute force protection.
    */
    'auth' => [
        'max_login_attempts'   => 5,
        'lockout_minutes'      => 15,
        'token_expiry_hours'   => 24,
        'checkin_rate_limit'   => 10,  // Attempts per officer per minute
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
        'recap_ttl_seconds'     => 600,
        'locations_ttl_seconds' => 300,
        'logo_ttl_seconds'      => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Escalation Timers
    |--------------------------------------------------------------------------
    | PRD §11.3 — Bypass request escalation.
    */
    'escalation' => [
        'god_admin_after_minutes' => 5,
        'email_after_minutes'     => 10,
    ],
];
