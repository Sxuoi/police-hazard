<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attendances table — PRD §6.2. IMMUTABLE — append-only.
     * Check-in records with GPS, photo, spoofing data.
     * PostgreSQL rules prevent UPDATE and DELETE at database level.
     * No updated_at column — INSERT only.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id');
            $table->uuid('officer_id');
            $table->uuid('location_id');
            $table->uuid('saker_id');
            // GPS — checkin_coordinates added via raw SQL (PostGIS)
            $table->decimal('gps_accuracy_meters', 6, 2)->nullable();
            $table->decimal('distance_from_point', 8, 2);
            $table->boolean('is_within_geofence');
            // Timing
            $table->timestampTz('checked_in_at')->useCurrent();
            $table->timestampTz('shift_window_start');
            $table->timestampTz('shift_window_end');
            $table->boolean('is_within_shift');
            // Bypass
            $table->boolean('is_manual_bypass')->default(false);
            $table->uuid('bypass_approval_id')->nullable();
            // Status & Flags
            $table->string('status', 20)->default('verified');
            $table->smallInteger('spoofing_score')->default(0);
            $table->jsonb('spoofing_signals')->nullable();
            // Device
            $table->jsonb('device_metadata');
            $table->string('photo_path', 500)->nullable();
            $table->string('photo_raw_path', 500)->nullable();
            $table->string('photo_status', 20)->default('pending')->nullable();
            // Integrity
            $table->string('checksum', 64);
            $table->timestampTz('created_at')->useCurrent();
            // No updated_at — INSERT only

            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('officer_id')->references('id')->on('users');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('saker_id')->references('id')->on('sakers');
            $table->foreign('bypass_approval_id')->references('id')->on('manual_bypass_approvals');

            $table->index('assignment_id', 'idx_attendances_assignment');
            $table->index('officer_id', 'idx_attendances_officer');
            $table->index('checked_in_at', 'idx_attendances_checkedin');
            $table->index('saker_id', 'idx_attendances_saker');
        });

        // PostGIS geometry column for check-in coordinates
        DB::statement('ALTER TABLE attendances ADD COLUMN checkin_coordinates GEOMETRY(POINT, 4326) NOT NULL');
        DB::statement('CREATE INDEX idx_attendances_coordinates ON attendances USING GIST(checkin_coordinates)');

        // CHECK constraints
        DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_attendance_status CHECK (status IN ('verified','flagged','rejected'))");
        DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_photo_status CHECK (photo_status IN ('pending','processed','failed'))");

        // IMMUTABLE — Enforce append-only at database level (PRD §6.2)
        DB::statement('CREATE RULE no_update_attendances AS ON UPDATE TO attendances DO INSTEAD NOTHING');
        DB::statement('CREATE RULE no_delete_attendances AS ON DELETE TO attendances DO INSTEAD NOTHING');
    }

    public function down(): void
    {
        DB::statement('DROP RULE IF EXISTS no_update_attendances ON attendances');
        DB::statement('DROP RULE IF EXISTS no_delete_attendances ON attendances');
        Schema::dropIfExists('attendances');
    }
};
