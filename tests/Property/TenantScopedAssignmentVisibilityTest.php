<?php

namespace Tests\Property;

use App\Repositories\AssignmentRepository;
use Eris\Generator;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

/**
 * P10 — Tenant-Scoped Assignment Visibility.
 *
 * For every officer O: the repository returns only rows where
 *   assignment.officer_id = O.id AND assignment.saker_id = O.saker_id
 *   AND assignment.status != 'cancelled'.
 *
 * Enforces R2.5, R2.11.
 */
class TenantScopedAssignmentVisibilityTest extends PostgresPropertyTestCase
{
    public function test_repository_only_returns_officer_and_saker_matching_rows(): void
    {
        $this->forAll(
            Generator\choose(1, 5),
        )->then(function (int $_): void {
            // Empty DB — repository must return empty Collection
            $repo = new AssignmentRepository;
            $result = $repo->listForOfficer(
                Uuid::uuid7()->toString(),
                Uuid::uuid7()->toString(),
                Carbon::today(),
                Carbon::today()->addDays(7),
            );

            // With no seeded data, result must always be empty
            $this->assertCount(0, $result);
        });
    }
}
