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
            $table->timestamp('waktu_diselesaikan')->nullable()->after('waktu_mendatangi_tkp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports_110', function (Blueprint $table) {
            $table->dropColumn('waktu_diselesaikan');
        });
    }
};
