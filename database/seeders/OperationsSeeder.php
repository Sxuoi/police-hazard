<?php

namespace Database\Seeders;

use App\Models\Operation;
use App\Models\Saker;
use App\Models\User;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Saker::where('code', 'POLDA-JATENG')->sole();
        $polrestabes = Saker::where('code', 'PRTBS-SMG')->sole();
        $polsek = Saker::where('code', 'PLSK-SMGTGH')->sole();

        $operations = [
            [
                'saker_id' => $polda->id,
                'name' => 'Operasi Keamanan Natal 2026',
                'description' => 'Pengamanan tempat ibadah dan pusat perbelanjaan.',
                'operation_type' => 'PH',
                'status' => 'active',
                'start_time' => '06:00',
                'end_time' => '18:00',
                'created_by' => $polda->id,
            ],
            [
                'saker_id' => $polrestabes->id,
                'name' => 'Patroli Wilayah Semarang',
                'description' => 'Patroli rutin wilayah Semarang.',
                'operation_type' => 'PATROL',
                'status' => 'active',
                'start_time' => '18:00',
                'end_time' => '06:00',
                'created_by' => $polrestabes->id,
            ],
            [
                'saker_id' => $polsek->id,
                'name' => 'PH Pos Pemeriksaan Semarang Tengah',
                'description' => 'Pengamanan stasiun dan area komersil.',
                'operation_type' => 'PH',
                'status' => 'active',
                'start_time' => '08:00',
                'end_time' => null,
                'created_by' => $polsek->id,
            ],
            [
                'saker_id' => $polda->id,
                'name' => 'Draft Operasi Akhir Tahun',
                'description' => 'Rencana operasi akhir tahun 2026.',
                'operation_type' => 'PH',
                'status' => 'draft',
                'start_time' => '20:00',
                'end_time' => '02:00',
                'created_by' => $polda->id,
            ],
        ];

        foreach ($operations as $opData) {
            Operation::firstOrCreate(
                ['name' => $opData['name']],
                $opData
            );
        }

        $this->command->info('✓ OperationsSeeder: 4 Operations seeded.');
    }
}
