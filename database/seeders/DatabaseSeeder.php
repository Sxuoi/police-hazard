<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SakersSeeder::class,
            OfficersSeeder::class,
            OperationsSeeder::class,
            ZonesSeeder::class,
            LocationsSeeder::class,
            AssignmentsSeeder::class,
            AttendancesSeeder::class,
        ]);

        DB::statement('REFRESH MATERIALIZED VIEW daily_attendance_summary');

        $this->command->info('✓ All seeders complete. Materialized view refreshed.');
    }
}
