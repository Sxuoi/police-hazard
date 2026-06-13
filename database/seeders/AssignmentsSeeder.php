<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Location;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $locations = Location::with('zone.operation')->get();
        $godAdmin = User::where('nrp', 'GA001')->sole();

        $assignIdx = 0;

        foreach ($locations as $loc) {
            $zone = $loc->zone;
            $op = $zone->operation;
            $sakerId = $loc->saker_id;

            // Get the two shifts for this location (Pagi first)
            $shiftPagi = Shift::where('location_id', $loc->id)
                ->where('name', 'Shift Pagi')
                ->first();

            if (! $shiftPagi) {
                continue;
            }

            // Get eligible officers for this saker
            $eligibleOfficers = User::withoutGlobalScopes()
                ->where('saker_id', $sakerId)
                ->where('role', 'officer')
                ->orderBy('nrp')
                ->get();

            if ($eligibleOfficers->isEmpty()) {
                continue;
            }

            // Assign 1–minimum_officer officers per location per day
            $assigned = $eligibleOfficers->slice($assignIdx % $eligibleOfficers->count(), $loc->minimum_officer);

            // Look up the saker admin for assigned_by
            $sakerAdmin = User::withoutGlobalScopes()
                ->where('saker_id', $sakerId)
                ->where('role', 'saker_admin')
                ->first();
            $assignedBy = $sakerAdmin ? $sakerAdmin->id : $godAdmin->id;

            foreach ($assigned as $officer) {
                foreach ([$today, $yesterday] as $date) {
                    Assignment::firstOrCreate(
                        [
                            'officer_id' => $officer->id,
                            'location_id' => $loc->id,
                            'shift_id' => $shiftPagi->id,
                            'assignment_date' => $date,
                        ],
                        [
                            'operation_id' => $op->id,
                            'saker_id' => $sakerId,
                            'assigned_saker_id' => $sakerId,
                            'status' => 'active',
                            'assigned_by' => $assignedBy,
                        ]
                    );
                }
            }

            $assignIdx++;
        }

        $count = Assignment::count();
        $this->command->info("✓ AssignmentsSeeder: {$count} Assignments seeded.");
    }
}
