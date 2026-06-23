<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PRD §7 — Table reports_110 for emergency reports.
     * Includes PostGIS geometry columns coordinates_tiba and coordinates_selesai.
     */
    public function up(): void
    {
        Schema::create('reports_110', function (Blueprint $table) {
            // Kolom Administrasi
            $table->uuid('id')->primary();
            $table->string('no_tiketing', 50)->unique();
            $table->uuid('unit_id');
            $table->string('token', 64)->unique();
            $table->string('status', 30)->default('Butuh penanganan');
            
            // Kolom Alamat Spasial
            $table->text('alamat_aktual_tiba')->nullable();
            $table->text('alamat_aktual_selesai')->nullable();

            // Kolom Laporan Segera
            $table->string('jenis_gangguan', 150);
            $table->timestampTz('waktu_kejadian');
            $table->timestampTz('waktu_dilaporkan');
            $table->timestampTz('waktu_mendatangi_tkp')->nullable();
            $table->string('tempat_kejadian', 250); // Alamat / TKP awal dari pelapor

            // Teks Laporan
            $table->string('nama_pamapta', 150)->nullable();
            $table->string('nrp_pamapta', 50)->nullable();
            $table->text('modus_operandi')->nullable();
            $table->text('korban')->nullable();
            $table->text('uraian_kejadian')->nullable();
            $table->text('pelaku')->nullable();
            $table->text('sanksi_sanksi')->nullable();
            $table->text('motif')->nullable();
            $table->text('alat_yang_digunakan')->nullable();
            $table->text('kerugian')->nullable();
            $table->text('bukti_yang_dapat_disita')->nullable();
            $table->text('tindakan_kepolisian')->nullable();
            $table->text('keterangan_lain')->nullable();

            // Dokumentasi
            $table->string('bukti_foto_path', 500)->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // Foreign Key
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');

            // Indexes
            $table->index('unit_id', 'idx_reports_110_unit');
            $table->index('status', 'idx_reports_110_status');
            $table->index('token', 'idx_reports_110_token');
        });

        // Add PostGIS geometry columns — SRID 4326 (WGS84)
        DB::statement("ALTER TABLE reports_110 ADD COLUMN koordinat_tiba GEOMETRY(POINT, 4326) NULL");
        DB::statement("ALTER TABLE reports_110 ADD COLUMN koordinat_selesai GEOMETRY(POINT, 4326) NULL");

        // Mandatory GIST index for fast spatial queries
        DB::statement("CREATE INDEX idx_reports_110_koordinat_tiba ON reports_110 USING GIST(koordinat_tiba)");
        DB::statement("CREATE INDEX idx_reports_110_koordinat_selesai ON reports_110 USING GIST(koordinat_selesai)");

        // CHECK constraint for status
        DB::statement("ALTER TABLE reports_110 ADD CONSTRAINT chk_reports_110_status CHECK (status IN ('Butuh penanganan', 'Sedang penanganan', 'Sudah penanganan'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports_110');
    }
};
