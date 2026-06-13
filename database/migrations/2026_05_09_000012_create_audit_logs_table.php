<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Audit Logs table — PRD §6.2. IMMUTABLE — append-only.
     */
    public function up(): void
    {
        DB::statement('
            CREATE TABLE audit_logs (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                actor_id UUID REFERENCES users(id),
                actor_ip INET,
                actor_user_agent TEXT,
                saker_id UUID REFERENCES sakers(id),
                event_type VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id UUID,
                payload_before JSONB,
                payload_after JSONB,
                metadata JSONB,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ');

        DB::statement('CREATE RULE no_update_audit_logs AS ON UPDATE TO audit_logs DO INSTEAD NOTHING');
        DB::statement('CREATE RULE no_delete_audit_logs AS ON DELETE TO audit_logs DO INSTEAD NOTHING');
        DB::statement('CREATE INDEX idx_audit_logs_actor ON audit_logs(actor_id)');
        DB::statement('CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id)');
        DB::statement('CREATE INDEX idx_audit_logs_event ON audit_logs(event_type)');
        DB::statement('CREATE INDEX idx_audit_logs_created ON audit_logs(created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP RULE IF EXISTS no_update_audit_logs ON audit_logs');
        DB::statement('DROP RULE IF EXISTS no_delete_audit_logs ON audit_logs');
        DB::statement('DROP TABLE IF EXISTS audit_logs');
    }
};
