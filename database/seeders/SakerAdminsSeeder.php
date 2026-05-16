<?php

namespace Database\Seeders;

use App\Models\Saker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SakerAdminsSeeder extends Seeder
{
    public function run(): void
    {
        $sakerCodes = ['POLDA-JATENG', 'PRTBS-SMG', 'PLSK-SMGTGH'];

        foreach ($sakerCodes as $idx => $code) {
            $saker = Saker::where('code', $code)->sole();

            User::firstOrCreate(
                ['nrp' => 'SA'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'saker_id' => $saker->id,
                    'name' => "Admin {$saker->code}",
                    'role' => 'saker_admin',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✓ SakerAdminsSeeder: 3 Saker Admins seeded.');
    }
}
