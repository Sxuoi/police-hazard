<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Locations table — PRD §6.2.
     * Geospatial patrol points with PostGIS GEOMETRY(POINT, 4326).
     * Coordinates become LOCKED (read-only) after first attendance record.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('zone_id');
            $table->uuid('saker_id');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            // coordinates added via raw SQL below (PostGIS type)
            $table->smallInteger('radius_meters')->default(50);
            $table->smallInteger('minimum_officer')->default(1);
            $table->uuid('padal_id')->nullable();
            $table->jsonb('operating_hours')->nullable();
            $table->boolean('coords_locked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->foreign('zone_id')->references('id')->on('zones');
            $table->foreign('saker_id')->references('id')->on('sakers');
            $table->foreign('padal_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->index('zone_id', 'idx_locations_zone');
            $table->index('saker_id', 'idx_locations_saker');
        });

        // Add PostGIS geometry column — SRID 4326 (WGS84)
        DB::statement("ALTER TABLE locations ADD COLUMN coordinates GEOMETRY(POINT, 4326) NOT NULL");

        // Mandatory GIST index for ST_DWithin performance (PRD §16.3)
        DB::statement("CREATE INDEX idx_locations_coordinates ON locations USING GIST(coordinates)");

        // CHECK constraints per PRD
        DB::statement("ALTER TABLE locations ADD CONSTRAINT chk_radius_meters CHECK (radius_meters BETWEEN 10 AND 500)");
        DB::statement("ALTER TABLE locations ADD CONSTRAINT chk_minimum_officer CHECK (minimum_officer >= 1)");
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
