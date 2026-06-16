<?php

namespace Database\Seeders;

use App\Models\Saker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SakersSeeder extends Seeder
{
    public function run(): void
    {
        // God Admin / Super Administrator (Top Level)
        $godAdmin = Saker::firstOrCreate(
            ['code' => 'MABES-POLRI'],
            [
                'name' => 'Super Administrator',
                'type' => 'MABES',
                'email' => 'superadmin@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $polda = Saker::firstOrCreate(
            ['code' => 'POLDA-JATENG'],
            [
                'name' => 'POLDA JAWA TENGAH',
                'type' => 'POLDA',
                'email' => 'poldajateng@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $polrestabes = Saker::firstOrCreate(
            ['code' => 'PRTBS-SMG'],
            [
                'name' => 'POLRESTABES SEMARANG',
                'type' => 'POLRESTABES',
                'parent_id' => $polda->id,
                'email' => 'polrestabessemarang@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        Saker::firstOrCreate(
            ['code' => 'PLSK-SMGTGH'],
            [
                'name' => 'POLSEK SEMARANG TENGAH',
                'type' => 'POLSEK',
                'parent_id' => $polrestabes->id,
                'email' => 'polseksmrgtgh@gmail.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $this->command->info('✓ SakersSeeder: 4 Sakers (Admins) seeded.');
    }
}
