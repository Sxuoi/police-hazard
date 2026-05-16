<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3 — Extend manual_bypass_approvals for officer-submitted GPS/photo bundle.
     *
     * Changes:
     * 1. Add 10 officer-submitted columns (GPS, photo, device metadata).
     * 2. Add escalation_level column.
     * 3. Widen chk_bypass_reason to include SPOOFING_REJECTED.
     * 4. Replace global no_update_manual_bypass with a BEFORE UPDATE trigger that
     *    allows only narrow pending→decided transitions and escalation advancement.
     *
     * Using a trigger instead of multiple INSTEAD rules avoids PostgreSQL rule recursion.
     *
     * _(R4.9, R4.14–R4.16, R5.4, R5.6)_
     */
    public function up(): void
    {
        Schema::table('manual_bypass_approvals', function (Blueprint $table) {
            $table->decimal('officer_latitude', 10, 7)->nullable()->after('officer_note');
            $table->decimal('officer_longitude', 10, 7)->nullable()->after('officer_latitude');
            $table->decimal('officer_gps_accuracy', 6, 2)->nullable()->after('officer_longitude');
            $table->decimal('officer_gps_altitude', 7, 2)->nullable()->after('officer_gps_accuracy');
            $table->decimal('officer_gps_speed', 6, 2)->nullable()->after('officer_gps_altitude');
            $table->string('officer_gps_provider', 16)->nullable()->after('officer_gps_speed');
            $table->string('officer_photo_path', 500)->nullable()->after('officer_gps_provider');
            $table->jsonb('officer_device_metadata')->nullable()->after('officer_photo_path');
            $table->timestampTz('officer_timestamp_device')->nullable()->after('officer_device_metadata');
            $table->smallInteger('escalation_level')->default(0)->after('officer_timestamp_device');
        });

        DB::statement('ALTER TABLE manual_bypass_approvals ADD CONSTRAINT chk_escalation_level CHECK (escalation_level BETWEEN 0 AND 2)');

        // Widen bypass_reason CHECK to include SPOOFING_REJECTED
        DB::statement('ALTER TABLE manual_bypass_approvals DROP CONSTRAINT chk_bypass_reason');
        DB::statement("
            ALTER TABLE manual_bypass_approvals ADD CONSTRAINT chk_bypass_reason
            CHECK (bypass_reason IN ('OUTSIDE_GEOFENCE','OUTSIDE_SHIFT_WINDOW','SPOOFING_REJECTED'))
        ");

        // Replace global no-update rule with a BEFORE UPDATE trigger
        DB::statement('DROP RULE IF EXISTS no_update_manual_bypass ON manual_bypass_approvals');

        // Create the trigger function that enforces narrow transitions
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_narrow_transition_manual_bypass()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Case 1: Allow pending → approved/denied/expired transition
                -- Only mutable fields (status, reviewed_by, reviewer_note, reviewed_at, escalation_level) may change
                IF OLD.status = 'pending'
                   AND NEW.status IN ('approved', 'denied', 'expired')
                   AND NEW.id = OLD.id
                   AND NEW.assignment_id = OLD.assignment_id
                   AND NEW.officer_id = OLD.officer_id
                   AND NEW.saker_id = OLD.saker_id
                   AND NEW.bypass_reason = OLD.bypass_reason
                   AND NEW.officer_note = OLD.officer_note
                   AND NEW.expires_at = OLD.expires_at
                   AND NEW.created_at = OLD.created_at
                THEN
                    -- Enforce immutability of all other columns
                    NEW.id := OLD.id;
                    NEW.assignment_id := OLD.assignment_id;
                    NEW.officer_id := OLD.officer_id;
                    NEW.saker_id := OLD.saker_id;
                    NEW.bypass_reason := OLD.bypass_reason;
                    NEW.officer_note := OLD.officer_note;
                    NEW.expires_at := OLD.expires_at;
                    NEW.created_at := OLD.created_at;
                    -- Allow: status, reviewed_by, reviewer_note, reviewed_at, escalation_level
                    RETURN NEW;
                END IF;

                -- Case 2: Allow escalation_level advancement on pending rows
                IF OLD.status = 'pending'
                   AND NEW.status = 'pending'
                   AND NEW.escalation_level > OLD.escalation_level
                   AND NEW.id = OLD.id
                   AND NEW.assignment_id = OLD.assignment_id
                   AND NEW.officer_id = OLD.officer_id
                   AND NEW.saker_id = OLD.saker_id
                   AND NEW.bypass_reason = OLD.bypass_reason
                   AND NEW.officer_note = OLD.officer_note
                   AND NEW.expires_at = OLD.expires_at
                   AND NEW.created_at = OLD.created_at
                THEN
                    -- Only allow escalation_level to change
                    NEW.id := OLD.id;
                    NEW.assignment_id := OLD.assignment_id;
                    NEW.officer_id := OLD.officer_id;
                    NEW.saker_id := OLD.saker_id;
                    NEW.bypass_reason := OLD.bypass_reason;
                    NEW.officer_note := OLD.officer_note;
                    NEW.status := OLD.status;
                    NEW.reviewed_by := OLD.reviewed_by;
                    NEW.reviewer_note := OLD.reviewer_note;
                    NEW.reviewed_at := OLD.reviewed_at;
                    NEW.expires_at := OLD.expires_at;
                    NEW.created_at := OLD.created_at;
                    RETURN NEW;
                END IF;

                -- Reject all other updates silently (return NULL cancels the UPDATE)
                RETURN NULL;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        // Create the BEFORE UPDATE trigger
        DB::statement('
            CREATE TRIGGER trg_narrow_transition_manual_bypass
            BEFORE UPDATE ON manual_bypass_approvals
            FOR EACH ROW
            EXECUTE FUNCTION fn_narrow_transition_manual_bypass()
        ');
    }

    public function down(): void
    {
        // Drop trigger and function
        DB::statement('DROP TRIGGER IF EXISTS trg_narrow_transition_manual_bypass ON manual_bypass_approvals');
        DB::statement('DROP FUNCTION IF EXISTS fn_narrow_transition_manual_bypass()');

        // Also drop any leftover rules from previous versions
        DB::statement('DROP RULE IF EXISTS narrow_transition_manual_bypass ON manual_bypass_approvals');
        DB::statement('DROP RULE IF EXISTS escalate_manual_bypass ON manual_bypass_approvals');
        DB::statement('DROP RULE IF EXISTS reject_other_update_manual_bypass ON manual_bypass_approvals');

        // Restore original global no-update rule
        DB::statement('CREATE RULE no_update_manual_bypass AS ON UPDATE TO manual_bypass_approvals DO INSTEAD NOTHING');

        // Restore original bypass_reason CHECK
        DB::statement('ALTER TABLE manual_bypass_approvals DROP CONSTRAINT IF EXISTS chk_bypass_reason');
        DB::statement("
            ALTER TABLE manual_bypass_approvals ADD CONSTRAINT chk_bypass_reason
            CHECK (bypass_reason IN ('OUTSIDE_GEOFENCE','OUTSIDE_SHIFT_WINDOW'))
        ");

        DB::statement('ALTER TABLE manual_bypass_approvals DROP CONSTRAINT IF EXISTS chk_escalation_level');

        Schema::table('manual_bypass_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'officer_latitude',
                'officer_longitude',
                'officer_gps_accuracy',
                'officer_gps_altitude',
                'officer_gps_speed',
                'officer_gps_provider',
                'officer_photo_path',
                'officer_device_metadata',
                'officer_timestamp_device',
                'escalation_level',
            ]);
        });
    }
};
