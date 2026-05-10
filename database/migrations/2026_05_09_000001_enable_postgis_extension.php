<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Enable PostGIS extension for geospatial capabilities.
     * PRD §3.1 — PostgreSQL + PostGIS required for ST_DWithin geofencing.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS postgis CASCADE;');
    }
};
