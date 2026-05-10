<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operations table — PRD §6.2.
     * Deployment operations owned by a Saker.
     * operation_type: PH (Police Hazard) or PATROL — immutable after first zone.
     */
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('saker_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('operation_type', 20);
            $table->string('status', 20)->default('draft');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->foreign('saker_id')->references('id')->on('sakers');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->index('saker_id');
        });

        DB::statement("ALTER TABLE operations ADD CONSTRAINT chk_operation_type CHECK (operation_type IN ('PH','PATROL'))");
        DB::statement("ALTER TABLE operations ADD CONSTRAINT chk_operation_status CHECK (status IN ('draft','active','suspended','completed','archived'))");
        DB::statement("ALTER TABLE operations ADD CONSTRAINT chk_date_order CHECK (end_date IS NULL OR end_date >= start_date)");
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
