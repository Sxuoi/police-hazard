<?php

namespace Database\Seeders;

use App\Models\Operation;
use App\Models\Saker;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Saker::where('code', 'POLDA-JATENG')->sole();
        $polrestabes = Saker::where('code', 'PRTBS-SMG')->sole();
        $polsek = Saker::where('code', 'PLSK-SMGTGH')->sole();

        $godAdmin = User::where('nrp', 'GA001')->sole();

        $op1 = Operation::where('name', 'Operasi Keamanan Natal 2026')->sole();
        $op2 = Operation::where('name', 'Patroli Wilayah Semarang')->sole();
        $op3 = Operation::where('name', 'PH Pos Pemeriksaan Semarang Tengah')->sole();
        $op4 = Operation::where('name', 'Draft Operasi Akhir Tahun')->sole();

        // Look up zones by name + operation_id
        $zoneUtara = Zone::where('name', 'Zona Utara')->where('operation_id', $op1->id)->sole();
        $zoneSelatan = Zone::where('name', 'Zona Selatan')->where('operation_id', $op1->id)->sole();
        $zoneTembalang = Zone::where('name', 'Zona Tembalang')->where('operation_id', $op2->id)->sole();
        $zoneBanyumanik = Zone::where('name', 'Zona Banyumanik')->where('operation_id', $op2->id)->sole();
        $zoneSimpangLima = Zone::where('name', 'Zona Simpang Lima')->where('operation_id', $op3->id)->sole();
        $zonePasarJohar = Zone::where('name', 'Zona Pasar Johar')->where('operation_id', $op3->id)->sole();

        $locationData = [
            // Zone Utara (POLDA)
            [$zoneUtara, $polda, 'Masjid Agung Jawa Tengah', -6.9836, 110.4452, 'Jl. Gajah Raya'],
            [$zoneUtara, $polda, 'Tugu Muda', -6.9844, 110.4100, 'Jl. Pemuda'],
            [$zoneUtara, $polda, 'Balai Kota Semarang', -6.9822, 110.4137, 'Jl. Pemuda No.148'],
            // Zone Selatan (POLDA)
            [$zoneSelatan, $polda, 'Lawang Sewu', -6.9840, 110.4104, 'Jl. Pemuda'],
            [$zoneSelatan, $polda, 'Sam Poo Kong', -6.9961, 110.3980, 'Jl. Simongan'],
            // Zone Tembalang (POLRESTABES)
            [$zoneTembalang, $polrestabes, 'UNDIP Tembalang', -7.0494, 110.4400, 'Jl. Prof. Sudarto'],
            [$zoneTembalang, $polrestabes, 'RSND', -7.0526, 110.4371, 'Tembalang'],
            [$zoneTembalang, $polrestabes, 'Tol Banyumanik', -7.0754, 110.4287, 'Banyumanik'],
            // Zone Banyumanik (POLRESTABES)
            [$zoneBanyumanik, $polrestabes, 'Transmart Setiabudi', -7.0505, 110.4146, 'Jl. Setiabudi'],
            [$zoneBanyumanik, $polrestabes, 'Taman Tirto Agung', -7.0573, 110.4246, 'Jl. Tirto Agung'],
            // Zone Simpang Lima (POLSEK)
            [$zoneSimpangLima, $polsek, 'Simpang Lima', -6.9904, 110.4228, 'Simpang Lima'],
            [$zoneSimpangLima, $polsek, 'Citraland Mall', -6.9912, 110.4239, 'Simpang Lima'],
            [$zoneSimpangLima, $polsek, 'Masjid Raya Baiturrahman', -6.9906, 110.4219, 'Simpang Lima'],
            // Zone Pasar Johar (POLSEK)
            [$zonePasarJohar, $polsek, 'Pasar Johar', -6.9740, 110.4243, 'Jl. KH. Agus Salim'],
            [$zonePasarJohar, $polsek, 'Kota Lama', -6.9680, 110.4276, 'Jl. Letjen Suprapto'],
        ];

        foreach ($locationData as [$zone, $saker, $name, $lat, $lng, $address]) {
            // Check existence first for idempotence
            $exists = DB::table('locations')
                ->where('name', $name)
                ->where('zone_id', $zone->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $id = Uuid::uuid7()->toString();
            $radius = fake()->randomElement([50, 75, 100]);
            $minOfficer = fake()->numberBetween(1, 3);

            // Determine timezone based on longitude (R12.2)
            $timezone = 'Asia/Jakarta'; // default
            if ($lng >= 135) {
                $timezone = 'Asia/Jayapura';
            } elseif ($lng >= 115) {
                $timezone = 'Asia/Makassar';
            }

            // Pick a random officer from the same saker as PADAL
            $padalId = User::where('saker_id', $saker->id)
                ->where('role', 'officer')
                ->where('is_active', true)
                ->inRandomOrder()
                ->value('id');

            DB::statement('
                INSERT INTO locations (id, zone_id, saker_id, name, address, coordinates, radius_meters, minimum_officer, padal_id, timezone, coords_locked, is_active, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?, ?, false, true, ?, NOW(), NOW())
            ', [$id, $zone->id, $saker->id, $name, $address, $lng, $lat, $radius, $minOfficer, $padalId, $timezone, $godAdmin->id]);
        }

        $this->command->info('✓ LocationsSeeder: 15 Locations seeded.');
    }
}
