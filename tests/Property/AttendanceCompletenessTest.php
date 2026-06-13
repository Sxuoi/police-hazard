<?php

namespace Tests\Property;

use Eris\Generator;
use Illuminate\Support\Facades\DB;

/**
 * P9 — Attendance Record Completeness.
 *
 * For every attendances row: required NOT NULL fields are populated.
 *
 * Enforces R3.13.
 *
 * The DB schema itself enforces non-nullability on the listed columns.
 * This property test verifies that the schema constraints are in place.
 */
class AttendanceCompletenessTest extends PostgresPropertyTestCase
{
    public function test_required_fields_are_non_nullable_in_schema(): void
    {
        $requiredColumns = [
            'id', 'assignment_id', 'officer_id', 'location_id', 'saker_id',
            'distance_from_point', 'is_within_geofence', 'checked_in_at',
            'shift_window_start', 'shift_window_end', 'is_within_shift',
            'spoofing_score', 'device_metadata', 'checksum',
        ];

        $this->forAll(
            Generator\elements(...$requiredColumns),
        )->then(function (string $column): void {
            $row = DB::selectOne("
                SELECT is_nullable FROM information_schema.columns
                WHERE table_name = 'attendances' AND column_name = ?
            ", [$column]);

            $this->assertNotNull($row, "Column {$column} must exist");
            $this->assertSame('NO', $row->is_nullable,
                "Column {$column} must be NOT NULL");
        });
    }
}
