<?php

namespace Tests\Feature\Api\V1\Officer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * Shared base for Officer API feature tests.
 *
 * Seeds a minimal multi-tenant world (2 Sakers × full hierarchy) using raw
 * SQL so PostGIS geometry columns are populated correctly. Subclasses get:
 *
 *   • Saker A with one officer, one location, one shift, one assignment today
 *   • Saker B with one officer + one assignment today (for cross-tenant tests)
 *
 * All Officer API feature tests skip on non-Postgres drivers because the
 * location insert uses PostGIS ST_SetSRID/ST_MakePoint.
 */
abstract class OfficerApiTestCase extends TestCase
{
    use RefreshDatabase;

    // ── Tenant A ─────────────────────────────────────────────────────
    protected string $sakerId;

    protected string $officerId;

    protected string $officerNrp;

    protected string $operationId;

    protected string $zoneId;

    protected string $locationId;

    protected string $assignmentId;

    // ── Tenant B (for cross-tenant tests) ────────────────────────────
    protected string $sakerIdB;

    protected string $officerIdB;

    protected string $officerNrpB;

    protected string $operationIdB;

    protected string $zoneIdB;

    protected string $locationIdB;

    protected string $assignmentIdB;

    // Jakarta Monas coords (used as the location point).
    protected const LAT = -6.2088;

    protected const LNG = 106.8456;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Officer API feature tests require Postgres + PostGIS.');
        }

        // Use a fake private disk so check-in/bypass photo persistence never
        // touches the real storage during tests.
        Storage::fake(config('policehazard.photo.private_disk', 'local'));
        Storage::fake(config('policehazard.photo.s3_disk', 's3'));

        $this->seedTenantA();
        $this->seedTenantB();
    }

    // ── Auth helpers ─────────────────────────────────────────────────

    /**
     * Issue a Sanctum token for an officer and return the bearer value.
     */
    protected function tokenFor(string $userId): string
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);

        return $user->createToken('officer-mobile')->plainTextToken;
    }

    /**
     * Build an array of Authorization + JSON headers for a given officer.
     *
     * @return array<string, string>
     */
    protected function authHeaders(string $userId): array
    {
        return [
            'Authorization' => 'Bearer '.$this->tokenFor($userId),
            'Accept' => 'application/json',
        ];
    }

    // ── Photo helpers ────────────────────────────────────────────────

    /**
     * Build a fake JPEG with the correct magic bytes that the Action's
     * photo-magic-bytes guard will accept.
     */
    protected function fakeJpegWithMagicBytes(string $name = 'photo.jpg', int $sizeKb = 50): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ph_');
        // Use Intervention Image via the real file-fake mechanism: build a
        // tiny valid JPEG by leveraging UploadedFile::fake()->image() which
        // writes a real PNG/JPEG via GD.
        $image = UploadedFile::fake()->image($name, 64, 64);
        $contents = file_get_contents($image->getPathname());
        file_put_contents($tmp, $contents);

        return new UploadedFile(
            $tmp,
            $name,
            'image/jpeg',
            null,
            true, // mark as test
        );
    }

    /**
     * Build a fake text file disguised as a jpeg — triggers PHOTO_INVALID.
     */
    protected function fakeTextFile(string $name = 'not-a-photo.jpg'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ph_');
        file_put_contents($tmp, str_repeat("this is plain text content\n", 20));

        return new UploadedFile(
            $tmp,
            $name,
            'text/plain',
            null,
            true,
        );
    }

    // ── Seeding ──────────────────────────────────────────────────────

    private function seedTenantA(): void
    {
        $this->sakerId = Uuid::uuid7()->toString();
        $this->officerId = Uuid::uuid7()->toString();
        $this->operationId = Uuid::uuid7()->toString();
        $this->zoneId = Uuid::uuid7()->toString();
        $this->locationId = Uuid::uuid7()->toString();
        $this->assignmentId = Uuid::uuid7()->toString();
        $this->officerNrp = 'A'.strtoupper(substr($this->officerId, 0, 10));

        $this->seedSaker($this->sakerId, 'Test Polda A');
        $this->seedUser($this->officerId, $this->sakerId, 'Officer A', $this->officerNrp, true);
        $this->seedOperation($this->operationId, $this->sakerId, $this->officerId);
        $this->seedZone($this->zoneId, $this->operationId, $this->sakerId, $this->officerId);
        $this->seedLocation($this->locationId, $this->zoneId, $this->sakerId, $this->officerId);
        $this->seedAssignment(
            $this->assignmentId,
            $this->officerId,
            $this->locationId,
            $this->operationId,
            $this->sakerId,
        );
    }

    private function seedTenantB(): void
    {
        $this->sakerIdB = Uuid::uuid7()->toString();
        $this->officerIdB = Uuid::uuid7()->toString();
        $this->operationIdB = Uuid::uuid7()->toString();
        $this->zoneIdB = Uuid::uuid7()->toString();
        $this->locationIdB = Uuid::uuid7()->toString();
        $this->assignmentIdB = Uuid::uuid7()->toString();
        $this->officerNrpB = 'B'.strtoupper(substr($this->officerIdB, 0, 10));

        $this->seedSaker($this->sakerIdB, 'Test Polda B');
        $this->seedUser($this->officerIdB, $this->sakerIdB, 'Officer B', $this->officerNrpB, true);
        $this->seedOperation($this->operationIdB, $this->sakerIdB, $this->officerIdB);
        $this->seedZone($this->zoneIdB, $this->operationIdB, $this->sakerIdB, $this->officerIdB);
        $this->seedLocation($this->locationIdB, $this->zoneIdB, $this->sakerIdB, $this->officerIdB);
        $this->seedAssignment(
            $this->assignmentIdB,
            $this->officerIdB,
            $this->locationIdB,
            $this->operationIdB,
            $this->sakerIdB,
        );
    }

    protected function seedSaker(string $id, string $name): void
    {
        DB::table('sakers')->insert([
            'id' => $id,
            'name' => $name,
            'code' => 'T-'.bin2hex(random_bytes(6)),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    protected function seedUser(
        string $id,
        string $sakerId,
        string $name,
        string $nrp,
        bool $isActive,
        string $role = 'officer',
        string $password = 'password',
    ): void {
        DB::table('users')->insert([
            'id' => $id,
            'saker_id' => $sakerId,
            'name' => $name,
            'nrp' => $nrp,
            'role' => $role,
            'password' => bcrypt($password),
            'is_active' => $isActive,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    protected function seedOperation(string $id, string $sakerId, string $createdBy, string $type = 'PH'): void
    {
        DB::table('operations')->insert([
            'id' => $id,
            'saker_id' => $sakerId,
            'name' => 'Test Op '.substr($id, 0, 6),
            'operation_type' => $type,
            'status' => 'active',
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
            'created_by' => $createdBy,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    protected function seedZone(string $id, string $operationId, string $sakerId, string $createdBy): void
    {
        DB::table('zones')->insert([
            'id' => $id,
            'operation_id' => $operationId,
            'saker_id' => $sakerId,
            'name' => 'Test Zone',
            'is_active' => true,
            'created_by' => $createdBy,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    protected function seedLocation(
        string $id,
        string $zoneId,
        string $sakerId,
        string $createdBy,
        float $lat = self::LAT,
        float $lng = self::LNG,
        int $radius = 50,
    ): void {
        DB::statement("
            INSERT INTO locations (
                id, zone_id, saker_id, name, coordinates,
                radius_meters, minimum_officer, coords_locked, is_active,
                created_by, created_at, updated_at, timezone
            ) VALUES (
                '{$id}',
                '{$zoneId}',
                '{$sakerId}',
                'Test Location',
                ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326),
                {$radius}, 1, false, true,
                '{$createdBy}',
                NOW(), NOW(), 'Asia/Jakarta'
            )
        ");
    }

    protected function seedAssignment(
        string $id,
        string $officerId,
        string $locationId,
        string $operationId,
        string $sakerId,
        ?string $date = null,
    ): void {
        DB::table('assignments')->insert([
            'id' => $id,
            'officer_id' => $officerId,
            'location_id' => $locationId,
            'operation_id' => $operationId,
            'saker_id' => $sakerId,
            'assigned_saker_id' => $sakerId,
            'start_date' => $date ?? Carbon::today()->toDateString(),
            'status' => 'active',
            'assigned_by' => $officerId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
