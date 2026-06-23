<?php

namespace Database\Seeders;

use App\Models\Operation;
use App\Models\Saker;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZonesSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Saker::where('code', 'POLDA-JATENG')->sole();
        $polrestabes = Saker::where('code', 'PRTBS-SMG')->sole();
        $polsek = Saker::where('code', 'PLSK-SMGTGH')->sole();

        $godAdmin = Saker::where('code', 'POLDA-JATENG')->sole();

        $op1 = Operation::where('name', 'Operasi Keamanan Natal 2026')->sole();
        $op2 = Operation::where('name', 'Patroli Wilayah Semarang')->sole();
        $op3 = Operation::where('name', 'PH Pos Pemeriksaan Semarang Tengah')->sole();
        $op4 = Operation::where('name', 'Operasi Lilin Candi 2025')->sole();

        $zoneData = [
            [$op1, $polda, 'Zona Utara'],
            [$op1, $polda, 'Zona Selatan'],
            [$op2, $polrestabes, 'Zona Tembalang'],
            [$op2, $polrestabes, 'Zona Banyumanik'],
            [$op3, $polsek, 'Zona Simpang Lima'],
            [$op3, $polsek, 'Zona Pasar Johar'],
            [$op4, $polda, 'Zona Pusat'],
            [$op4, $polda, 'Zona Barat'],
        ];

        foreach ($zoneData as [$op, $saker, $name]) {
            Zone::firstOrCreate(
                ['name' => $name, 'operation_id' => $op->id],
                [
                    'saker_id' => $saker->id,
                    'is_active' => true,
                    'created_by' => $godAdmin->id,
                ]
            );
        }

        $this->command->info('✓ ZonesSeeder: 8 Zones seeded.');
    }
}
