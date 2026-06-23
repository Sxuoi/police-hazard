<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sakers (Satuan Kerja) — Organizational Units.
     * PRD §4.1, §6.2 — Root tenant table. NOT tenant-scoped.
     * Self-referential hierarchy: POLDA → POLRESTABES → POLSEK.
     */
    public function up(): void
    {
        Schema::create('sakers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('email', 150)->nullable()->unique();
            $table->string('password', 255)->nullable();
            $table->string('code', 20)->unique();
            $table->string('type', 20);
            $table->uuid('parent_id')->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // Self-referencing FK added after table creation (PK must exist first)
        Schema::table('sakers', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('sakers');
        });

        // CHECK constraint for type — matches PRD exactly + MABES for God Admin
        DB::statement("ALTER TABLE sakers ADD CONSTRAINT chk_saker_type CHECK (type IN ('MABES','POLDA','POLRESTABES','POLSEK'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('sakers');
    }
};
