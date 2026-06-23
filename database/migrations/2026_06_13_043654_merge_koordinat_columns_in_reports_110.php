<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports_110', function (Blueprint $table) {
            $table->renameColumn('alamat_aktual_tiba', 'alamat_aktual_110');
            $table->dropColumn('alamat_aktual_selesai');
        });

        // Rename the PostGIS geometry column and recreate index
        DB::statement('DROP INDEX IF EXISTS idx_reports_110_koordinat_tiba');
        DB::statement('DROP INDEX IF EXISTS idx_reports_110_koordinat_selesai');
        
        DB::statement('ALTER TABLE reports_110 RENAME COLUMN koordinat_tiba TO koordinat_110');
        DB::statement('ALTER TABLE reports_110 DROP COLUMN koordinat_selesai');
        
        DB::statement('CREATE INDEX idx_reports_110_koordinat_110 ON reports_110 USING GIST(koordinat_110)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_reports_110_koordinat_110');
        DB::statement('ALTER TABLE reports_110 ADD COLUMN koordinat_selesai GEOMETRY(POINT, 4326) NULL');
        DB::statement('ALTER TABLE reports_110 RENAME COLUMN koordinat_110 TO koordinat_tiba');
        
        DB::statement('CREATE INDEX idx_reports_110_koordinat_tiba ON reports_110 USING GIST(koordinat_tiba)');
        DB::statement('CREATE INDEX idx_reports_110_koordinat_selesai ON reports_110 USING GIST(koordinat_selesai)');

        Schema::table('reports_110', function (Blueprint $table) {
            $table->renameColumn('alamat_aktual_110', 'alamat_aktual_tiba');
            $table->text('alamat_aktual_selesai')->nullable();
        });
    }
};
