<?php

namespace Database\Seeders;

use App\Models\Saker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OfficersSeeder extends Seeder
{
    public function run(): void
    {
        $sakerCodes = ['POLDA-JATENG', 'PRTBS-SMG', 'PLSK-SMGTGH'];
        $officerIdx = 1;

        foreach ($sakerCodes as $code) {
            $saker = Saker::where('code', $code)->sole();

            for ($i = 0; $i < 10; $i++) {
                $nrp = 'OF'.str_pad($officerIdx, 4, '0', STR_PAD_LEFT);

                User::firstOrCreate(
                    ['nrp' => $nrp],
                    [
                        'saker_id' => ($nrp === 'OF0001') ? Saker::where('code', 'PRTBS-SMG')->sole()->id : $saker->id,
                        'name' => fake('id_ID')->name(),
                        'phone' => fake('id_ID')->phoneNumber(),
                        'safung' => fake()->randomElement(['Bhabinkamtibmas', 'Reskrim', 'Sabhara', 'Intelkam', 'Lantas']),
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]
                );

                $officerIdx++;
            }
        }

        $this->command->info('✓ OfficersSeeder: 30 Officers seeded (10 per Saker).');
    }
}
