<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 3 — Replace global no_update_attendances with a BEFORE UPDATE trigger
     * that allows ONLY photo_path + photo_status to change (pending → processed/failed),
     * and silently rejects all other updates by returning NULL from the trigger.
     *
     * Using a trigger instead of multiple INSTEAD rules avoids PostgreSQL rule recursion.
     *
     * _(R3.14, P1)_
     */
    public function up(): void
    {
        DB::statement('DROP RULE IF EXISTS no_update_attendances ON attendances');

        // Create the trigger function that enforces narrow photo update
        DB::statement("
            CREATE OR REPLACE FUNCTION fn_narrow_photo_update_attendances()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Allow photo_path + photo_status transition from pending → processed/failed
                -- only if all other columns remain identical
                IF OLD.photo_status = 'pending'
                   AND NEW.photo_status IN ('processed', 'failed')
                   AND NEW.id = OLD.id
                   AND NEW.assignment_id = OLD.assignment_id
                   AND NEW.officer_id = OLD.officer_id
                   AND NEW.location_id = OLD.location_id
                   AND NEW.saker_id = OLD.saker_id
                   AND NEW.distance_from_point = OLD.distance_from_point
                   AND NEW.is_within_geofence = OLD.is_within_geofence
                   AND NEW.checked_in_at = OLD.checked_in_at
                   AND NEW.shift_window_start = OLD.shift_window_start
                   AND NEW.shift_window_end = OLD.shift_window_end
                   AND NEW.is_within_shift = OLD.is_within_shift
                   AND NEW.is_manual_bypass = OLD.is_manual_bypass
                   AND (NEW.bypass_approval_id IS NOT DISTINCT FROM OLD.bypass_approval_id)
                   AND NEW.status = OLD.status
                   AND NEW.spoofing_score = OLD.spoofing_score
                   AND NEW.checksum = OLD.checksum
                   AND NEW.checkin_coordinates = OLD.checkin_coordinates
                THEN
                    -- Allow only photo_path and photo_status to change
                    NEW.id := OLD.id;
                    NEW.assignment_id := OLD.assignment_id;
                    NEW.officer_id := OLD.officer_id;
                    NEW.location_id := OLD.location_id;
                    NEW.saker_id := OLD.saker_id;
                    NEW.distance_from_point := OLD.distance_from_point;
                    NEW.is_within_geofence := OLD.is_within_geofence;
                    NEW.checked_in_at := OLD.checked_in_at;
                    NEW.shift_window_start := OLD.shift_window_start;
                    NEW.shift_window_end := OLD.shift_window_end;
                    NEW.is_within_shift := OLD.is_within_shift;
                    NEW.is_manual_bypass := OLD.is_manual_bypass;
                    NEW.bypass_approval_id := OLD.bypass_approval_id;
                    NEW.status := OLD.status;
                    NEW.spoofing_score := OLD.spoofing_score;
                    NEW.checksum := OLD.checksum;
                    NEW.checkin_coordinates := OLD.checkin_coordinates;
                    RETURN NEW;
                END IF;

                -- Reject all other updates silently (return NULL cancels the UPDATE)
                RETURN NULL;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        // Create the BEFORE UPDATE trigger
        DB::statement('
            CREATE TRIGGER trg_narrow_photo_update_attendances
            BEFORE UPDATE ON attendances
            FOR EACH ROW
            EXECUTE FUNCTION fn_narrow_photo_update_attendances()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_narrow_photo_update_attendances ON attendances');
        DB::statement('DROP FUNCTION IF EXISTS fn_narrow_photo_update_attendances()');

        // Restore original global no-update rule
        DB::statement('CREATE RULE no_update_attendances AS ON UPDATE TO attendances DO INSTEAD NOTHING');
    }
};
