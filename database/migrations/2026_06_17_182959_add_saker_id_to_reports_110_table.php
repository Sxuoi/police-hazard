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
            $table->foreignUuid('saker_id')->nullable()->constrained('sakers')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports_110', function (Blueprint $table) {
            $table->dropForeign(['saker_id']);
            $table->dropColumn('saker_id');
        });
    }
};
