<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the materialized view that depends on assignment_date
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS daily_attendance_summary');

        // 2. Add new columns
        Schema::table('assignments', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable(); // NULL = indefinite
        });

        // 3. Migrate existing data
        DB::statement('UPDATE assignments SET start_date = assignment_date, end_date = assignment_date');

        // 4. Set start_date to NOT NULL
        Schema::table('assignments', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
        });

        // 5. Drop old index and column
        DB::statement('DROP INDEX IF EXISTS idx_assignments_ph_no_overlap');
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_date');
            $table->dropColumn('assignment_date');
        });

        // 6. Create new indexes
        Schema::table('assignments', function (Blueprint $table) {
            $table->index('start_date', 'idx_assignments_start_date');
        });

        DB::statement("
            CREATE INDEX idx_assignments_active_range
            ON assignments(officer_id, start_date, end_date)
            WHERE status != 'cancelled'
        ");

        DB::statement("
            CREATE UNIQUE INDEX idx_assignments_no_overlap
            ON assignments(officer_id, shift_id, start_date)
            WHERE status != 'cancelled'
        ");

        // 7. Recreate materialized view with daily range expansion
        DB::statement("
            CREATE MATERIALIZED VIEW daily_attendance_summary AS
            SELECT
                l.id AS location_id,
                l.saker_id,
                l.zone_id,
                gs.summary_date::date AS summary_date,
                COUNT(DISTINCT att.id) AS total_checkins,
                l.minimum_officer,
                CASE
                    WHEN COUNT(DISTINCT att.id) >= l.minimum_officer THEN 'attended'
                    WHEN COUNT(DISTINCT att.id) > 0 THEN 'partial'
                    ELSE 'not_attended'
                END AS day_status
            FROM assignments a
            CROSS JOIN LATERAL generate_series(
                a.start_date::timestamp,
                COALESCE(a.end_date, CURRENT_DATE)::timestamp,
                '1 day'::interval
            ) AS gs(summary_date)
            JOIN locations l ON l.id = a.location_id
            LEFT JOIN attendances att
                ON att.assignment_id = a.id
                AND att.status = 'verified'
                AND att.checked_in_at::date = gs.summary_date::date
            GROUP BY l.id, l.saker_id, l.zone_id, gs.summary_date::date, l.minimum_officer
        ");

        DB::statement('CREATE UNIQUE INDEX ON daily_attendance_summary(location_id, summary_date)');
    }

    public function down(): void
    {
        // 1. Drop the materialized view
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS daily_attendance_summary');

        // 2. Drop new indexes
        DB::statement('DROP INDEX IF EXISTS idx_assignments_no_overlap');
        DB::statement('DROP INDEX IF EXISTS idx_assignments_active_range');
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_start_date');
        });

        // 3. Re-add assignment_date column
        Schema::table('assignments', function (Blueprint $table) {
            $table->date('assignment_date')->nullable();
        });

        // 4. Restore data from start_date
        DB::statement('UPDATE assignments SET assignment_date = start_date');

        // 5. Make assignment_date NOT NULL
        Schema::table('assignments', function (Blueprint $table) {
            $table->date('assignment_date')->nullable(false)->change();
            $table->index('assignment_date', 'idx_assignments_date');
            $table->dropColumn(['start_date', 'end_date']);
        });

        // 6. Restore old unique index
        DB::statement("
            CREATE UNIQUE INDEX idx_assignments_ph_no_overlap
            ON assignments(officer_id, assignment_date, shift_id)
            WHERE status != 'cancelled'
        ");

        // 7. Restore old materialized view
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
};
