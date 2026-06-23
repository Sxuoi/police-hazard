<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3 — Add timezone column to locations.
     * Backfills existing rows using longitude-based rule (R12.1, R12.2):
     *   longitude < 115°E  → Asia/Jakarta  (WIB, UTC+7)
     *   115°E ≤ lon < 135°E → Asia/Makassar (WITA, UTC+8)
     *   longitude ≥ 135°E  → Asia/Jayapura (WIT, UTC+9)
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('timezone', 64)->default('Asia/Jakarta')->after('operating_hours');
        });

        DB::statement("
            ALTER TABLE locations ADD CONSTRAINT chk_location_timezone
            CHECK (timezone IN ('Asia/Jakarta','Asia/Makassar','Asia/Jayapura'))
        ");

        // Backfill based on longitude extracted from PostGIS geometry
        DB::statement("
            UPDATE locations SET timezone = CASE
                WHEN ST_X(coordinates::geometry) < 115 THEN 'Asia/Jakarta'
                WHEN ST_X(coordinates::geometry) < 135 THEN 'Asia/Makassar'
                ELSE 'Asia/Jayapura'
            END
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE locations DROP CONSTRAINT IF EXISTS chk_location_timezone');

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
