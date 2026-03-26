<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetributionEstimateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('retribution_estimates')->truncate();

        $now = now();

        $data = [
            [
                'no' => 1,
                'fungsi_bg' => 'Fungsi Campuran',
                'usulan_retribusi_per_m2' => null,
                'children' => [
                    ['fungsi_bg' => 'Campuran Kecil', 'usulan_retribusi_per_m2' => 37990.00],
                    ['fungsi_bg' => 'Campuran Besar', 'usulan_retribusi_per_m2' => 50652.00],
                    ['fungsi_bg' => 'Fungsi Hunian;Fungsi Usaha', 'usulan_retribusi_per_m2' => 37990.00],
                    ['fungsi_bg' => 'Fungsi Hunian;Fungsi Usaha (UMKM)', 'usulan_retribusi_per_m2' => 37990.00],
                    ['fungsi_bg' => 'Fungsi Usaha (UMKM);Fungsi Hunian', 'usulan_retribusi_per_m2' => 37990.00],
                    ['fungsi_bg' => 'Fungsi Usaha;Fungsi Usaha (UMKM)', 'usulan_retribusi_per_m2' => 37990.00],
                    ['fungsi_bg' => 'Fungsi Sosial Budaya;Fungsi Usaha (UMKM)', 'usulan_retribusi_per_m2' => 37990.00],
                    ['fungsi_bg' => 'Fungsi Usaha (UMKM);Fungsi Sosial Budaya', 'usulan_retribusi_per_m2' => 37990.00],
                ],
            ],
            [
                'no' => 2,
                'fungsi_bg' => 'Fungsi Hunian',
                'usulan_retribusi_per_m2' => 9181.50,
                'children' => [
                    ['fungsi_bg' => 'Fungsi Hunian Tidak Sederhana', 'usulan_retribusi_per_m2' => 10764.00],
                    ['fungsi_bg' => 'Fungsi Hunian Sederhana', 'usulan_retribusi_per_m2' => 7599.00],
                ],
            ],
            [
                'no' => 3,
                'fungsi_bg' => 'Fungsi Keagamaan',
                'usulan_retribusi_per_m2' => 0,
                'children' => [],
            ],
            [
                'no' => 4,
                'fungsi_bg' => 'Fungsi Sosial dan Budaya',
                'usulan_retribusi_per_m2' => 18995.00,
                'children' => [
                    ['fungsi_bg' => 'Fungsi Sosial Budaya', 'usulan_retribusi_per_m2' => 18995.00],
                ],
            ],
            [
                'no' => 5,
                'fungsi_bg' => 'Fungsi Khusus',
                'usulan_retribusi_per_m2' => 63316.00,
                'children' => [],
            ],
            [
                'no' => 6,
                'fungsi_bg' => 'Fungsi Usaha',
                'usulan_retribusi_per_m2' => 28000.00,
                'children' => [
                    ['fungsi_bg' => 'Fungsi Usaha', 'usulan_retribusi_per_m2' => 44321.00],
                    ['fungsi_bg' => 'Fungsi Usaha (UMKM)', 'usulan_retribusi_per_m2' => 31659.00],
                ],
            ],
            [
                'no' => 7,
                'fungsi_bg' => 'Kolektif',
                'usulan_retribusi_per_m2' => 16000.00,
                'children' => [],
            ],
            [
                'no' => 8,
                'fungsi_bg' => 'Bangunan Prasarana',
                'usulan_retribusi_per_m2' => null,
                'children' => [
                    ['fungsi_bg' => 'Konstruksi Reklame/Papan Nama', 'usulan_retribusi_per_m2' => 500000.00],
                    ['fungsi_bg' => 'Konstruksi Menara', 'usulan_retribusi_per_m2' => 2000000.00],
                    ['fungsi_bg' => 'Konstruksi Pembatas/Penahan', 'usulan_retribusi_per_m2' => 2000000.00],
                    ['fungsi_bg' => 'Bangunan Pemerintahan', 'usulan_retribusi_per_m2' => null],
                    ['fungsi_bg' => 'Konstruksi', 'usulan_retribusi_per_m2' => 2000000.00],
                    ['fungsi_bg' => 'Konstruksi Instalasi/Gardu', 'usulan_retribusi_per_m2' => 2000000.00],
                    ['fungsi_bg' => 'Konstruksi Kolam/Reservoir Bawah/Rph', 'usulan_retribusi_per_m2' => 2000000.00],
                ],
            ],
        ];

        foreach ($data as $parent) {
            $parentId = DB::table('retribution_estimates')->insertGetId([
                'parent_id' => null,
                'no' => $parent['no'],
                'fungsi_bg' => $parent['fungsi_bg'],
                'usulan_retribusi_per_m2' => $parent['usulan_retribusi_per_m2'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($parent['children'] as $child) {
                DB::table('retribution_estimates')->insert([
                    'parent_id' => $parentId,
                    'no' => null,
                    'fungsi_bg' => $child['fungsi_bg'],
                    'usulan_retribusi_per_m2' => $child['usulan_retribusi_per_m2'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
