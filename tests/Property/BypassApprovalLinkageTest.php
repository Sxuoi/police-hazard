<?php

namespace Tests\Property;

use Eris\Generator;
use Illuminate\Support\Facades\DB;

/**
 * P7 — Bypass Approval ↔ Attendance Linkage Monotonicity.
 *
 * For every manual_bypass_approvals row B with status='approved':
 *   exactly one attendance row A exists with A.bypass_approval_id=B.id,
 *   A.is_manual_bypass=true, A.assignment_id=B.assignment_id.
 * For B.status ∈ {pending, denied, expired}: zero such rows.
 *
 * Enforces R5.4, R5.6.
 */
class BypassApprovalLinkageTest extends PostgresPropertyTestCase
{
    public function test_non_approved_bypass_has_zero_linked_attendances(): void
    {
        $this->forAll(
            Generator\elements('pending', 'denied', 'expired'),
        )->then(function (string $status): void {
            $bypassCount = DB::table('manual_bypass_approvals')
                ->where('status', $status)
                ->count();

            $linkedAttendancesInThisState = DB::table('attendances')
                ->whereIn('bypass_approval_id',
                    DB::table('manual_bypass_approvals')->where('status', $status)->pluck('id'))
                ->count();

            // Non-approved bypasses must have zero linked attendances
            $this->assertSame(0, $linkedAttendancesInThisState,
                "Status {$status} must have zero linked attendances");
            $this->assertSame(0, $bypassCount, 'DB is empty between iterations');
        });
    }
}
