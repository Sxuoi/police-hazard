<?php

namespace Tests\Property;

use Eris\Generator;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * P5 — Audit Log Append-Only.
 *
 * For every audit_logs row inserted: attempts to update the row are rejected
 * by the DB rule, leaving the original row byte-identical.
 *
 * Enforces R8.4.
 */
class AuditLogAppendOnlyTest extends PostgresPropertyTestCase
{
    public function test_audit_log_rows_cannot_be_mutated(): void
    {
        $this->forAll(
            Generator\elements(
                'CHECKIN_ATTEMPT',
                'CHECKIN_VERIFIED',
                'CHECKIN_REJECTED',
                'MANUAL_BYPASS_REQUESTED',
                'MANUAL_BYPASS_APPROVED',
            ),
        )->then(function (string $eventType): void {
            $id = Uuid::uuid7()->toString();

            DB::table('audit_logs')->insert([
                'id' => $id,
                'actor_id' => null,
                'saker_id' => null,
                'event_type' => $eventType,
                'entity_type' => 'System',
                'entity_id' => null,
                'metadata' => json_encode(['prop' => 'test']),
                'created_at' => now(),
            ]);

            // Attempt to mutate — must be a silent no-op
            DB::table('audit_logs')->where('id', $id)->update([
                'event_type' => 'TAMPERED',
            ]);

            $row = DB::table('audit_logs')->where('id', $id)->first();
            $this->assertSame($eventType, $row->event_type,
                'audit_logs row must be immutable (DB rule rejects UPDATE)');
        });
    }
}
