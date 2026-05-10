<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifications table — PRD §6.2.
     * Custom notification system (NOT Laravel's default notifications).
     * read_at is the only mutable field.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('recipient_id');
            $table->uuid('saker_id');
            $table->string('type', 50);
            $table->string('title', 200);
            $table->text('body');
            $table->string('action_url', 500)->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('recipient_id')->references('id')->on('users');
            $table->foreign('saker_id')->references('id')->on('sakers');

            $table->index(['recipient_id', 'read_at'], 'idx_notifications_recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
