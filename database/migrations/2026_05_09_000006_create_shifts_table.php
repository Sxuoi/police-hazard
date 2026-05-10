<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shifts table — PRD §6.2.
     * Time windows for attendance at a location.
     * active_days uses PostgreSQL SMALLINT[] array (ISO weekdays: 1=Mon..7=Sun).
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('location_id');
            $table->string('name', 100);
            $table->time('shift_start');
            $table->time('shift_end');
            // active_days added via raw SQL (PostgreSQL array type)
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('location_id')->references('id')->on('locations');
            $table->index('location_id', 'idx_shifts_location');
        });

        // PostgreSQL SMALLINT[] array for active days
        DB::statement("ALTER TABLE shifts ADD COLUMN active_days SMALLINT[] NOT NULL DEFAULT '{}'");

        // CHECK constraint: shift_end must be after shift_start
        DB::statement("ALTER TABLE shifts ADD CONSTRAINT chk_shift_time CHECK (shift_end > shift_start)");
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
