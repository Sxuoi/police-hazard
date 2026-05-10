<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users table — PRD §6.2.
     * Tenant-scoped via saker_id. Roles: god_admin, saker_admin, officer.
     * NRP (Nomor Registrasi Pokok) is the unique officer identifier.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('saker_id');
            $table->string('name', 100);
            $table->string('nrp', 20)->unique();
            $table->string('email', 150)->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('role', 20);
            $table->string('safung', 50)->nullable();
            $table->string('avatar_path', 255)->nullable();
            $table->string('password', 255);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->foreign('saker_id')->references('id')->on('sakers');

            $table->index('saker_id', 'idx_users_saker');
            $table->index('nrp', 'idx_users_nrp');
            $table->index('role', 'idx_users_role');
        });

        // Self-referencing FKs added after table creation (PK must exist first)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // CHECK constraint for role
        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_user_role CHECK (role IN ('god_admin','saker_admin','officer'))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
