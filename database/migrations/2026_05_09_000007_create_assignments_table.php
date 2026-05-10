<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assignments table — PRD §6.2.
     * Binds Officer ↔ Location ↔ Shift ↔ Operation.
     * Partial unique index prevents PH overlap at database level (PRD §5.1).
     * saker_id = officer's home Saker; assigned_saker_id = borrowing Saker (PRD §2.3).
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('officer_id');
            $table->uuid('location_id');
            $table->uuid('shift_id');
            $table->uuid('operation_id');
            $table->uuid('saker_id');
            $table->uuid('assigned_saker_id');
            $table->date('assignment_date');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->uuid('assigned_by');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('officer_id')->references('id')->on('users');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->foreign('operation_id')->references('id')->on('operations');
            $table->foreign('saker_id')->references('id')->on('sakers');
            $table->foreign('assigned_saker_id')->references('id')->on('sakers');
            $table->foreign('assigned_by')->references('id')->on('users');

            $table->index('officer_id', 'idx_assignments_officer');
            $table->index('location_id', 'idx_assignments_location');
            $table->index('assignment_date', 'idx_assignments_date');
            $table->index('operation_id', 'idx_assignments_operation');
        });

        // CHECK constraint for status
        DB::statement("ALTER TABLE assignments ADD CONSTRAINT chk_assignment_status CHECK (status IN ('pending','active','completed','cancelled'))");

        // PH one-to-one enforcement at database level (PRD §5.1)
        // Partial unique index prevents same officer from being assigned to same date+shift
        // unless the assignment is cancelled
        DB::statement("
            CREATE UNIQUE INDEX idx_assignments_ph_no_overlap
            ON assignments(officer_id, assignment_date, shift_id)
            WHERE status != 'cancelled'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
