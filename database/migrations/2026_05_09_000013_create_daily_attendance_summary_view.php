<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Materialized view for reporting — PRD §6.2.
     * Aggregates daily attendance per location. Refreshed nightly via pg_cron.
     */
    public function up(): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW daily_attendance_summary AS
            SELECT
                l.id AS location_id,
                l.saker_id,
                l.zone_id,
                a.assignment_date AS summary_date,
                COUNT(DISTINCT att.id) AS total_checkins,
                l.minimum_officer,
                CASE
                    WHEN COUNT(DISTINCT att.id) >= l.minimum_officer THEN 'attended'
                    WHEN COUNT(DISTINCT att.id) > 0 THEN 'partial'
                    ELSE 'not_attended'
                END AS day_status
            FROM assignments a
            JOIN locations l ON l.id = a.location_id
            LEFT JOIN attendances att
                ON att.assignment_id = a.id
                AND att.status = 'verified'
            GROUP BY l.id, l.saker_id, l.zone_id, a.assignment_date, l.minimum_officer
        ");

        DB::statement('CREATE UNIQUE INDEX ON daily_attendance_summary(location_id, summary_date)');
    }

    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS daily_attendance_summary');
    }
};
