<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attendance Amendments table — PRD §6.1, §19.1.
     * Correction log for attendance records. IMMUTABLE — append-only.
     * Used when an admin needs to annotate or correct attendance metadata.
     */
    public function up(): void
    {
        Schema::create('attendance_amendments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attendance_id');
            $table->uuid('amended_by');
            $table->text('reason');
            $table->string('field_changed', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('attendance_id')->references('id')->on('attendances');
            $table->foreign('amended_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index('attendance_id');
        });

        // Append-only rules
        DB::statement("CREATE RULE no_update_amendments AS ON UPDATE TO attendance_amendments DO INSTEAD NOTHING");
        DB::statement("CREATE RULE no_delete_amendments AS ON DELETE TO attendance_amendments DO INSTEAD NOTHING");
    }

    public function down(): void
    {
        DB::statement("DROP RULE IF EXISTS no_update_amendments ON attendance_amendments");
        DB::statement("DROP RULE IF EXISTS no_delete_amendments ON attendance_amendments");
        Schema::dropIfExists('attendance_amendments');
    }
};
