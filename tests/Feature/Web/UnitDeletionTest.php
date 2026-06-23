<?php

namespace Tests\Feature\Web;

use App\Models\Report110;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UnitDeletionTest extends TestCase
{
    use RefreshDatabase;

    private string $sakerId;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires Postgres + PostGIS.');
        }

        $this->sakerId = Uuid::uuid7()->toString();

        DB::table('sakers')->insert([
            'id' => $this->sakerId,
            'name' => 'Test Saker',
            'code' => 'TS-'.substr($this->sakerId, 0, 6),
            'type' => 'POLDA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerId,
            'name' => 'Test Operator',
            'nrp' => 'OP'.rand(1000, 9999),
            'role' => 'operator_110',
            'password' => 'password',
            'is_active' => true,
        ]);
    }

    public function test_can_soft_delete_unit_not_referenced_by_any_report(): void
    {
        // Set saker bypass or authenticate to query and write within saker scope
        $this->actingAs($this->admin);

        $unit = Unit::create([
            'nama_unit' => 'Unit Patwal A',
            'no_wa' => '628123456789',
            'saker_id' => $this->sakerId,
        ]);

        // Send delete request
        $response = $this->delete(route('units.destroy', $unit->id));

        $response->assertRedirect(route('units.index'));
        $response->assertSessionHas('success', 'Unit armada berhasil dihapus.');

        // Assert it is soft-deleted (not in active queries, but exists in DB)
        $this->assertNull(Unit::find($unit->id));
        $this->assertNotNull(Unit::withTrashed()->find($unit->id));
        $this->assertNotNull(Unit::withTrashed()->find($unit->id)->deleted_at);
    }

    public function test_can_soft_delete_unit_referenced_by_report_without_foreign_key_violation(): void
    {
        $this->actingAs($this->admin);

        $unit = Unit::create([
            'nama_unit' => 'Unit Reserse B',
            'no_wa' => '628987654321',
            'saker_id' => $this->sakerId,
        ]);

        // Create a report referencing the unit
        $report = Report110::create([
            'saker_id' => $this->sakerId,
            'no_tiketing' => 'TKT-' . rand(10000, 99999),
            'unit_id' => $unit->id,
            'token' => Uuid::uuid4()->toString(),
            'status' => 'Butuh penanganan',
            'jenis_gangguan' => 'Pencurian',
            'waktu_kejadian' => now(),
            'waktu_dilaporkan' => now(),
            'tempat_kejadian' => 'Jl. Merdeka No. 10',
        ]);

        $reportId = $report->id;

        // Send delete request for the unit
        $response = $this->delete(route('units.destroy', $unit->id));

        // Assert response status/redirect
        $response->assertRedirect(route('units.index'));

        // Assert unit is soft deleted
        $this->assertNull(Unit::find($unit->id));

        $u = Unit::withTrashed()->find($unit->id);
        $this->assertNotNull($u);
        $this->assertNotNull($u->deleted_at);

        // Assert report still has the correct unit relation withTrashed
        $freshReport = Report110::find($reportId);
        $this->assertNotNull($freshReport);
        $this->assertEquals($unit->id, $freshReport->unit_id);
        $this->assertNotNull($freshReport->unit);
        $this->assertEquals('Unit Reserse B', $freshReport->unit->nama_unit);
    }
}
