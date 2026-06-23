<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zones table — PRD §7.4, §19.1.
     * Organizational zones within an operation.
     * Not explicitly defined in §6.2 SQL but referenced in ERD and hierarchy.
     */
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('operation_id');
            $table->uuid('saker_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();

            $table->foreign('operation_id')->references('id')->on('operations');
            $table->foreign('saker_id')->references('id')->on('sakers');

            $table->index('operation_id');
            $table->index('saker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
