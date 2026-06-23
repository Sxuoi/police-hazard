<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Saker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class AttendancesSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotence: skip if attendances already exist
        if (Attendance::count() > 0) {
            $this->command->info('✓ AttendancesSeeder: Attendances already exist, skipping.');

            return;
        }

        $yesterday = now()->subDay()->format('Y-m-d');

        $yesterdayAssignments = Assignment::withoutGlobalScopes()
            ->where('start_date', '<=', $yesterday)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $yesterday))
            ->with(['location', 'operation'])
            ->get();

        foreach ($yesterdayAssignments->take(30) as $assignment) {
            $loc = $assignment->location;
            $op = $assignment->operation;

            if (! $loc || ! $op) {
                continue;
            }

            $opEnd = $op->end_time ?? '23:59:00';

            // Random check-in time within shift window
            $checkinTime = now()->subDay()
                ->setTimeFromTimeString($op->start_time)
                ->addMinutes(fake()->numberBetween(0, 60));

            $distance = fake()->randomFloat(2, 0, 80);
            $isWithin = $distance <= ($loc->radius_meters ?? 50);

            $attId = Uuid::uuid7()->toString();
            $lat = -6.99 + fake()->randomFloat(4, -0.05, 0.05);
            $lng = 110.42 + fake()->randomFloat(4, -0.03, 0.03);

            DB::statement('
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
            ', [
                $attId,
                $assignment->id,
                $assignment->officer_id,
                $assignment->location_id,
                $assignment->saker_id,
                $lng,
                $lat,
                fake()->randomFloat(2, 3, 25),
                $distance,
                $isWithin,
                $checkinTime,
                now()->subDay()->setTimeFromTimeString($op->start_time),
                now()->subDay()->setTimeFromTimeString($opEnd),
                $isWithin ? 'verified' : 'flagged',
                $isWithin ? 0 : 1,
                $isWithin ? null : json_encode([['signal' => 'OUTSIDE_GEOFENCE']]),
                json_encode(['platform' => 'Android', 'browser' => 'Chrome Mobile 120', 'device_id' => Uuid::uuid4()->toString()]),
                hash('sha256', $assignment->id.$checkinTime),
                $checkinTime,
            ]);
        }

        $count = DB::table('attendances')->count();
        $this->command->info("✓ AttendancesSeeder: {$count} Attendances seeded.");
    }
}
