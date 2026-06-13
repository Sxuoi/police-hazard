<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual Bypass Approvals table — PRD §5.4, §19.1.
     * Records manual bypass requests and their approval/denial.
     * IMMUTABLE — append-only. Created BEFORE the attendance record it may generate.
     */
    public function up(): void
    {
        Schema::create('manual_bypass_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id');
            $table->uuid('officer_id');
            $table->uuid('saker_id');
            $table->string('bypass_reason', 50);   // OUTSIDE_GEOFENCE, OUTSIDE_SHIFT_WINDOW
            $table->text('officer_note');
            $table->string('status', 20)->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->text('reviewer_note')->nullable();
            $table->string('signature_hmac', 128)->nullable(); // HMAC-SHA256 on approval
            $table->timestampTz('expires_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('officer_id')->references('id')->on('users');
            $table->foreign('saker_id')->references('id')->on('sakers');
            $table->foreign('reviewed_by')->references('id')->on('users');

            $table->index('officer_id');
            $table->index('status');
        });

        // CHECK constraints
        DB::statement("ALTER TABLE manual_bypass_approvals ADD CONSTRAINT chk_bypass_reason CHECK (bypass_reason IN ('OUTSIDE_GEOFENCE','OUTSIDE_SHIFT_WINDOW'))");
        DB::statement("ALTER TABLE manual_bypass_approvals ADD CONSTRAINT chk_bypass_status CHECK (status IN ('pending','approved','denied','expired'))");

        // Append-only rules — PRD §6.1
        DB::statement('CREATE RULE no_update_manual_bypass AS ON UPDATE TO manual_bypass_approvals DO INSTEAD NOTHING');
        DB::statement('CREATE RULE no_delete_manual_bypass AS ON DELETE TO manual_bypass_approvals DO INSTEAD NOTHING');
    }

    public function down(): void
    {
        DB::statement('DROP RULE IF EXISTS no_update_manual_bypass ON manual_bypass_approvals');
        DB::statement('DROP RULE IF EXISTS no_delete_manual_bypass ON manual_bypass_approvals');
        Schema::dropIfExists('manual_bypass_approvals');
    }
};
