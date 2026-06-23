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
            $table->string('nama_pelapor', 150)->nullable()->after('tempat_kejadian');
            $table->string('no_hp_pelapor', 20)->nullable()->after('nama_pelapor');
            $table->string('jenis_no_hp_pelapor', 20)->nullable()->after('no_hp_pelapor'); // WhatsApp or Telepon Biasa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports_110', function (Blueprint $table) {
            $table->dropColumn(['nama_pelapor', 'no_hp_pelapor', 'jenis_no_hp_pelapor']);
        });
    }
};
