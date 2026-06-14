<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assignments table — PRD §6.2.
     * Binds Officer ↔ Location ↔ Operation.
     * Partial unique index prevents PH overlap at database level (PRD §5.1).
     * saker_id = officer's home Saker; assigned_saker_id = borrowing Saker (PRD §2.3).
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('officer_id');
            $table->uuid('location_id');
            $table->uuid('operation_id');
            $table->uuid('saker_id');
            $table->uuid('assigned_saker_id');
            $table->date('start_date');
            $table->date('end_date')->nullable(); // NULL = indefinite
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->uuid('assigned_by');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('officer_id')->references('id')->on('users');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('operation_id')->references('id')->on('operations');
            $table->foreign('saker_id')->references('id')->on('sakers');
            $table->foreign('assigned_saker_id')->references('id')->on('sakers');
            $table->foreign('assigned_by')->references('id')->on('users');

            $table->index('officer_id', 'idx_assignments_officer');
            $table->index('location_id', 'idx_assignments_location');
            $table->index('start_date', 'idx_assignments_start_date');
            $table->index('operation_id', 'idx_assignments_operation');
        });

        // CHECK constraint for status
        DB::statement("ALTER TABLE assignments ADD CONSTRAINT chk_assignment_status CHECK (status IN ('pending','active','completed','cancelled'))");

        // Active range index for efficient overlap lookups
        DB::statement("
            CREATE INDEX idx_assignments_active_range
            ON assignments(officer_id, start_date, end_date)
            WHERE status != 'cancelled'
        ");

        // PH one-to-one enforcement at database level (PRD §5.1)
        // Prevents same officer from being assigned to same location+date unless cancelled
        DB::statement("
            CREATE UNIQUE INDEX idx_assignments_no_overlap
            ON assignments(officer_id, location_id, start_date)
            WHERE status != 'cancelled'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};

