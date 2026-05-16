<?php

namespace Database\Seeders;

use App\Models\Saker;
use Illuminate\Database\Seeder;

class SakersSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Saker::firstOrCreate(
            ['code' => 'POLDA-JATENG'],
            [
                'name' => 'POLDA JAWA TENGAH',
                'type' => 'POLDA',
                'is_active' => true,
            ]
        );

        $polrestabes = Saker::firstOrCreate(
            ['code' => 'PRTBS-SMG'],
            [
                'name' => 'POLRESTABES SEMARANG',
                'type' => 'POLRESTABES',
                'parent_id' => $polda->id,
                'is_active' => true,
            ]
        );

        Saker::firstOrCreate(
            ['code' => 'PLSK-SMGTGH'],
            [
                'name' => 'POLSEK SEMARANG TENGAH',
                'type' => 'POLSEK',
                'parent_id' => $polrestabes->id,
                'is_active' => true,
            ]
        );

        $this->command->info('✓ SakersSeeder: 3 Sakers seeded.');
    }
}
