<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftsSeeder extends Seeder
{
    public function run(): void
    {
        $locations = Location::all();

        foreach ($locations as $loc) {
            Shift::firstOrCreate(
                ['location_id' => $loc->id, 'name' => 'Shift Pagi'],
                [
                    'shift_start' => '06:00',
                    'shift_end' => '14:00',
                    'active_days' => [1, 2, 3, 4, 5],
                    'is_active' => true,
                ]
            );

            Shift::firstOrCreate(
                ['location_id' => $loc->id, 'name' => 'Shift Sore'],
                [
                    'shift_start' => '14:00',
                    'shift_end' => '22:00',
                    'active_days' => [1, 2, 3, 4, 5, 6],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✓ ShiftsSeeder: '.($locations->count() * 2).' Shifts seeded.');
    }
}
