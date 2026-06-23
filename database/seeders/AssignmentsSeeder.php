<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Location;
use App\Models\Saker;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $locations = Location::with('zone.operation')->get();
        $godAdmin = Saker::where('code', 'POLDA-JATENG')->sole();

        $assignIdx = 0;

        foreach ($locations as $loc) {
            $zone = $loc->zone;
            $op = $zone->operation;
            $sakerId = $loc->saker_id;

            // Get eligible officers for this saker
            $eligibleOfficers = User::withoutGlobalScopes()
                ->where('saker_id', $sakerId)
                ->orderBy('nrp')
                ->get();

            if ($eligibleOfficers->isEmpty()) {
                continue;
            }

            // Assign 1–minimum_officer officers per location per day
            $assigned = $eligibleOfficers->slice($assignIdx % $eligibleOfficers->count(), $loc->minimum_officer);

            // assigned_by is now the saker itself
            $assignedBy = $sakerId;

            foreach ($assigned as $officer) {
                Assignment::firstOrCreate(
                    [
                        'officer_id' => $officer->id,
                        'location_id' => $loc->id,
                        'start_date' => $yesterday,
                    ],
                    [
                        'operation_id' => $op->id,
                        'saker_id' => $sakerId,
                        'assigned_saker_id' => $sakerId,
                        'end_date' => null,
                        'status' => 'active',
                        'assigned_by' => $assignedBy,
                    ]
                );
            }

            $assignIdx++;
        }

        // Explicitly assign OF0001 to Polrestabes Semarang today/active
        $of1 = User::withoutGlobalScopes()->where('nrp', 'OF0001')->first();
        $locPolrestabes = Location::where('name', 'Polrestabes Semarang')->first();
        if ($of1 && $locPolrestabes) {
            $op = $locPolrestabes->zone->operation;
            $sakerId = $locPolrestabes->saker_id;

            // Delete any other active assignments for OF0001 to prevent conflicts
            Assignment::where('officer_id', $of1->id)->delete();

            Assignment::create([
                'officer_id' => $of1->id,
                'location_id' => $locPolrestabes->id,
                'operation_id' => $op->id,
                'saker_id' => $sakerId,
                'assigned_saker_id' => $sakerId,
                'start_date' => $yesterday,
                'end_date' => null,
                'status' => 'active',
                'assigned_by' => $sakerId,
            ]);
        }

        $count = Assignment::count();
        $this->command->info("✓ AssignmentsSeeder: {$count} Assignments seeded.");
    }
}
