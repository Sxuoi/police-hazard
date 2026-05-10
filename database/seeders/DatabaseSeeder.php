<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\Operation;
use App\Models\Saker;
use App\Models\Shift;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Sakers (3-level hierarchy) ────────────────────────────
        $polda = Saker::create([
            'name'    => 'POLDA JAWA TIMUR',
            'code'    => 'POLDA-JATIM',
            'type'    => 'POLDA',
            'is_active' => true,
        ]);

        $polrestabes = Saker::create([
            'name'      => 'POLRESTABES SURABAYA',
            'code'      => 'PRTBS-SBY',
            'type'      => 'POLRESTABES',
            'parent_id' => $polda->id,
            'is_active'  => true,
        ]);

        $polsek = Saker::create([
            'name'      => 'POLSEK GUBENG',
            'code'      => 'PLSK-GBG',
            'type'      => 'POLSEK',
            'parent_id' => $polrestabes->id,
            'is_active'  => true,
        ]);

        // ── 2. Users ─────────────────────────────────────────────────
        $godAdmin = User::create([
            'saker_id' => $polda->id,
            'name'     => 'Super Administrator',
            'nrp'      => 'GA001',
            'email'    => 'admin@policehazard.id',
            'role'     => 'god_admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $sakerAdmins = [];
        foreach ([$polda, $polrestabes, $polsek] as $idx => $saker) {
            $sakerAdmins[$saker->id] = User::create([
                'saker_id' => $saker->id,
                'name'     => "Admin {$saker->code}",
                'nrp'      => 'SA' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                'role'     => 'saker_admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
        }

        // Officers — 10 per Saker (30 total)
        $officers = [];
        $officerIdx = 1;
        foreach ([$polda, $polrestabes, $polsek] as $saker) {
            for ($i = 0; $i < 10; $i++) {
                $officers[] = User::create([
                    'saker_id' => $saker->id,
                    'name'     => fake('id_ID')->name(),
                    'nrp'      => 'OF' . str_pad($officerIdx++, 4, '0', STR_PAD_LEFT),
                    'phone'    => fake('id_ID')->phoneNumber(),
                    'role'     => 'officer',
                    'safung'   => fake()->randomElement(['Bhabinkamtibmas', 'Reskrim', 'Sabhara', 'Intelkam', 'Lantas']),
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]);
            }
        }

        // ── 3. Operations ────────────────────────────────────────────
        $ops = [];
        $ops[] = Operation::create([
            'saker_id'       => $polda->id,
            'name'           => 'Operasi Keamanan Natal 2026',
            'description'    => 'Pengamanan tempat ibadah dan pusat perbelanjaan.',
            'operation_type' => 'PH',
            'status'         => 'active',
            'start_date'     => '2026-05-01',
            'end_date'       => '2026-06-30',
            'created_by'     => $godAdmin->id,
        ]);
        $ops[] = Operation::create([
            'saker_id'       => $polrestabes->id,
            'name'           => 'Patroli Wilayah Timur',
            'description'    => 'Patroli rutin wilayah Surabaya Timur.',
            'operation_type' => 'PATROL',
            'status'         => 'active',
            'start_date'     => '2026-05-01',
            'end_date'       => '2026-07-31',
            'created_by'     => $sakerAdmins[$polrestabes->id]->id,
        ]);
        $ops[] = Operation::create([
            'saker_id'       => $polsek->id,
            'name'           => 'PH Pos Pemeriksaan Gubeng',
            'description'    => 'Pengamanan stasiun dan area komersil.',
            'operation_type' => 'PH',
            'status'         => 'active',
            'start_date'     => '2026-05-01',
            'end_date'       => null,
            'created_by'     => $sakerAdmins[$polsek->id]->id,
        ]);
        $ops[] = Operation::create([
            'saker_id'       => $polda->id,
            'name'           => 'Draft Operasi Akhir Tahun',
            'description'    => 'Rencana operasi akhir tahun 2026.',
            'operation_type' => 'PH',
            'status'         => 'draft',
            'start_date'     => '2026-12-01',
            'end_date'       => '2027-01-05',
            'created_by'     => $godAdmin->id,
        ]);

        // ── 4. Zones ─────────────────────────────────────────────────
        $zones = [];
        $zoneData = [
            [$ops[0], $polda, 'Zona Utara', 'Zona Selatan'],
            [$ops[1], $polrestabes, 'Zona Rungkut', 'Zona Mulyosari'],
            [$ops[2], $polsek, 'Zona Stasiun', 'Zona Pasar'],
            [$ops[3], $polda, 'Zona Pusat', 'Zona Barat'],
        ];
        foreach ($zoneData as [$op, $saker, $name1, $name2]) {
            $zones[] = Zone::create([
                'operation_id' => $op->id,
                'saker_id'     => $saker->id,
                'name'         => $name1,
                'is_active'    => true,
                'created_by'   => $godAdmin->id,
            ]);
            $zones[] = Zone::create([
                'operation_id' => $op->id,
                'saker_id'     => $saker->id,
                'name'         => $name2,
                'is_active'    => true,
                'created_by'   => $godAdmin->id,
            ]);
        }

        // ── 5. Locations (with real Surabaya coordinates) ────────────
        $locationData = [
            // Zone 0 — Zona Utara (POLDA)
            [$zones[0], $polda, 'Masjid Al-Akbar Surabaya', -7.3160, 112.7275, 'Jl. Masjid Al Akbar Timur No.1'],
            [$zones[0], $polda, 'Tugu Pahlawan', -7.2458, 112.7378, 'Jl. Pahlawan'],
            [$zones[0], $polda, 'Balai Kota Surabaya', -7.2575, 112.7521, 'Jl. Taman Surya No.1'],
            // Zone 1 — Zona Selatan (POLDA)
            [$zones[1], $polda, 'Kebun Binatang Surabaya', -7.2930, 112.7363, 'Jl. Setail No.1'],
            [$zones[1], $polda, 'Monumen Kapal Selam', -7.2652, 112.7538, 'Jl. Pemuda'],
            // Zone 2 — Zona Rungkut (POLRESTABES)
            [$zones[2], $polrestabes, 'SIER Industrial Park', -7.3283, 112.7620, 'Jl. Rungkut Industri'],
            [$zones[2], $polrestabes, 'Pasar Rungkut', -7.3321, 112.7701, 'Jl. Rungkut Mejoyo'],
            [$zones[2], $polrestabes, 'Terminal Bratang', -7.2948, 112.7603, 'Jl. Bratang Binangun'],
            // Zone 3 — Zona Mulyosari (POLRESTABES)
            [$zones[3], $polrestabes, 'ITS Sukolilo', -7.2819, 112.7950, 'Kampus ITS Sukolilo'],
            [$zones[3], $polrestabes, 'Pakuwon City Mall', -7.2835, 112.8025, 'Jl. Laguna'],
            // Zone 4 — Zona Stasiun (POLSEK)
            [$zones[4], $polsek, 'Stasiun Gubeng', -7.2655, 112.7521, 'Jl. Gubeng Masjid'],
            [$zones[4], $polsek, 'Hotel Majapahit', -7.2619, 112.7470, 'Jl. Tunjungan No.65'],
            [$zones[4], $polsek, 'Plaza Surabaya', -7.2692, 112.7508, 'Jl. Pemuda No.31-37'],
            // Zone 5 — Zona Pasar (POLSEK)
            [$zones[5], $polsek, 'Pasar Genteng', -7.2574, 112.7435, 'Jl. Genteng Besar'],
            [$zones[5], $polsek, 'Pasar Kapasan', -7.2380, 112.7451, 'Jl. Kapasan'],
        ];

        $locations = [];
        foreach ($locationData as [$zone, $saker, $name, $lat, $lng, $address]) {
            $id = Uuid::uuid7()->toString();
            $radius = fake()->randomElement([50, 75, 100]);
            $minOfficer = fake()->numberBetween(1, 3);

            // Single INSERT with PostGIS coordinates to satisfy NOT NULL constraint
            DB::statement("
                INSERT INTO locations (id, zone_id, saker_id, name, address, coordinates, radius_meters, minimum_officer, coords_locked, is_active, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, false, true, ?, NOW(), NOW())
            ", [$id, $zone->id, $saker->id, $name, $address, $lng, $lat, $radius, $minOfficer, $godAdmin->id]);

            $locations[] = Location::find($id);
        }

        // ── 6. Shifts (2 per location) ───────────────────────────────
        $shifts = [];
        foreach ($locations as $loc) {
            $shifts[] = Shift::create([
                'location_id' => $loc->id,
                'name'        => 'Shift Pagi',
                'shift_start' => '06:00',
                'shift_end'   => '14:00',
                'active_days' => [1, 2, 3, 4, 5],
                'is_active'   => true,
            ]);
            $shifts[] = Shift::create([
                'location_id' => $loc->id,
                'name'        => 'Shift Sore',
                'shift_start' => '14:00',
                'shift_end'   => '22:00',
                'active_days' => [1, 2, 3, 4, 5, 6],
                'is_active'   => true,
            ]);
        }

        // ── 7. Assignments (today + yesterday) ───────────────────────
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $assignIdx = 0;

        foreach ($locations as $locIdx => $loc) {
            $zone = $zones[intdiv($locIdx, 3)] ?? $zones[0];
            $op = $zone->operation;
            $sakerId = $loc->saker_id;
            $shiftPagi = $shifts[$locIdx * 2];
            $shiftSore = $shifts[$locIdx * 2 + 1];

            // Assign 1–2 officers per location per day
            $eligibleOfficers = collect($officers)->where('saker_id', $sakerId)->values();
            $assigned = $eligibleOfficers->slice($assignIdx % $eligibleOfficers->count(), $loc->minimum_officer);

            foreach ($assigned as $officer) {
                foreach ([$today, $yesterday] as $date) {
                    Assignment::create([
                        'officer_id'       => $officer->id,
                        'location_id'      => $loc->id,
                        'shift_id'         => $shiftPagi->id,
                        'operation_id'     => $op->id,
                        'saker_id'         => $sakerId,
                        'assigned_saker_id' => $sakerId,
                        'assignment_date'  => $date,
                        'status'           => 'active',
                        'assigned_by'      => $sakerAdmins[$sakerId]->id ?? $godAdmin->id,
                    ]);
                }
            }
            $assignIdx++;
        }

        // ── 8. Sample Attendances (for yesterday's assignments) ──────
        $yesterdayAssignments = Assignment::where('assignment_date', $yesterday)->get();
        foreach ($yesterdayAssignments->take(30) as $assignment) {
            $loc = $assignment->location;
            $shift = $assignment->shift;

            // Random check-in time within shift window
            $checkinTime = now()->subDay()
                ->setTimeFromTimeString($shift->shift_start)
                ->addMinutes(fake()->numberBetween(0, 60));

            $distance = fake()->randomFloat(2, 0, 80);
            $isWithin = $distance <= ($loc->radius_meters ?? 50);

            $attId = Uuid::uuid7()->toString();
            $lat = -7.26 + fake()->randomFloat(4, -0.05, 0.05);
            $lng = 112.75 + fake()->randomFloat(4, -0.03, 0.03);

            // Single INSERT with PostGIS coordinates (cannot UPDATE — immutable table)
            DB::statement("
                INSERT INTO attendances (id, assignment_id, officer_id, location_id, saker_id,
                    checkin_coordinates, gps_accuracy_meters, distance_from_point, is_within_geofence,
                    checked_in_at, shift_window_start, shift_window_end, is_within_shift,
                    is_manual_bypass, status, spoofing_score, spoofing_signals,
                    device_metadata, checksum, created_at)
                VALUES (?, ?, ?, ?, ?,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?,
                    ?, ?, ?, true,
                    false, ?, ?, ?,
                    ?, ?, ?)
            ", [
                $attId, $assignment->id, $assignment->officer_id, $assignment->location_id, $assignment->saker_id,
                $lng, $lat,
                fake()->randomFloat(2, 3, 25),
                $distance,
                $isWithin,
                $checkinTime,
                now()->subDay()->setTimeFromTimeString($shift->shift_start),
                now()->subDay()->setTimeFromTimeString($shift->shift_end),
                $isWithin ? 'verified' : 'flagged',
                $isWithin ? 0 : 1,
                $isWithin ? null : json_encode([['signal' => 'OUTSIDE_GEOFENCE']]),
                json_encode(['platform' => 'Android', 'browser' => 'Chrome Mobile 120', 'device_id' => Uuid::uuid4()->toString()]),
                hash('sha256', $assignment->id . $checkinTime),
                $checkinTime,
            ]);
        }

        // ── Refresh materialized view ────────────────────────────────
        DB::statement('REFRESH MATERIALIZED VIEW daily_attendance_summary');

        $this->command->info('✓ Seeded: 3 Sakers, 34 Users (1 God + 3 SA + 30 Officers), 4 Ops, 8 Zones, 15 Locations, 30 Shifts, Assignments, Attendances');
    }
}
