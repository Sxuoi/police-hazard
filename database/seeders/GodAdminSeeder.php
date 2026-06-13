<?php

namespace Database\Seeders;

use App\Models\Saker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GodAdminSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Saker::where('code', 'POLDA-JATENG')->sole();

        User::firstOrCreate(
            ['nrp' => 'GA001'],
            [
                'saker_id' => $polda->id,
                'name' => 'Super Administrator',
                'email' => 'admin@policehazard.id',
                'role' => 'god_admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $this->command->info('✓ GodAdminSeeder: 1 God Admin seeded.');
    }
}
