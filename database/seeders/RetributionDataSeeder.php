<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetributionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Building Types berdasarkan Excel (without coefficient)
        DB::table('building_types')->insert([
            // Parent Functions
            ['id' => 1, 'code' => 'KEAGAMAAN', 'name' => 'Fungsi Keagamaan', 'parent_id' => null, 'level' => 1, 'is_free' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'SOSBUDAYA', 'name' => 'Fungsi Sosial Budaya', 'parent_id' => null, 'level' => 1, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'code' => 'CAMPURAN', 'name' => 'Fungsi Campuran (lebih dari 1)', 'parent_id' => null, 'level' => 1, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'code' => 'USAHA', 'name' => 'Fungsi Usaha', 'parent_id' => null, 'level' => 1, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'code' => 'HUNIAN', 'name' => 'Fungsi Hunian', 'parent_id' => null, 'level' => 1, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],

            // Child Functions
            ['id' => 6, 'code' => 'CAMP_KECIL', 'name' => 'Campuran Kecil', 'parent_id' => 3, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'code' => 'CAMP_BESAR', 'name' => 'Campuran Besar', 'parent_id' => 3, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'code' => 'UMKM', 'name' => 'Fungsi Usaha (UMKM)', 'parent_id' => 4, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'code' => 'USH_BESAR', 'name' => 'Usaha Besar (Non-Mikro)', 'parent_id' => 4, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'code' => 'HUN_SEDH', 'name' => 'Hunian Sederhana <100', 'parent_id' => 5, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'code' => 'HUN_TSEDH', 'name' => 'Hunian Tidak Sederhana >100', 'parent_id' => 5, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'code' => 'MBR', 'name' => 'Rumah Tinggal MBR', 'parent_id' => 5, 'level' => 2, 'is_free' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed Retribution Indices berdasarkan Excel (with coefficient moved here)
        DB::table('retribution_indices')->insert([
            ['building_type_id' => 1, 'coefficient' => 0.0000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.0000, 'locality_index' => 0.0000, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Keagamaan
            ['building_type_id' => 2, 'coefficient' => 0.3000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.6000, 'locality_index' => 0.0030, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Sosial Budaya
            ['building_type_id' => 6, 'coefficient' => 0.6000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.6000, 'locality_index' => 0.0050, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Campuran Kecil
            ['building_type_id' => 7, 'coefficient' => 0.8000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.6000, 'locality_index' => 0.0050, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Campuran Besar
            ['building_type_id' => 8, 'coefficient' => 0.5000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.6000, 'locality_index' => 0.0040, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // UMKM
            ['building_type_id' => 9, 'coefficient' => 0.7000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.6000, 'locality_index' => 0.0050, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Usaha Besar
            ['building_type_id' => 10, 'coefficient' => 0.1500, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.3000, 'locality_index' => 0.0040, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Hunian Sederhana
            ['building_type_id' => 11, 'coefficient' => 0.1700, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.6000, 'locality_index' => 0.0040, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // Hunian Tidak Sederhana
            ['building_type_id' => 12, 'coefficient' => 0.0000, 'ip_permanent' => 0.4000, 'ip_complexity' => 0.0000, 'locality_index' => 0.0000, 'infrastructure_factor' => 0.5000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()], // MBR
        ]);

        // Seed Height Indices berdasarkan Excel
        DB::table('height_indices')->insert([
            ['floor_number' => 1, 'height_index' => 1.0000, 'created_at' => now(), 'updated_at' => now()],
            ['floor_number' => 2, 'height_index' => 1.0900, 'created_at' => now(), 'updated_at' => now()],
            ['floor_number' => 3, 'height_index' => 1.1200, 'created_at' => now(), 'updated_at' => now()],
            ['floor_number' => 4, 'height_index' => 1.1350, 'created_at' => now(), 'updated_at' => now()],
            ['floor_number' => 5, 'height_index' => 1.1620, 'created_at' => now(), 'updated_at' => now()],
            ['floor_number' => 6, 'height_index' => 1.1970, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed Retribution Configs
        DB::table('retribution_configs')->insert([
            ['key' => 'BASE_VALUE', 'value' => 7035000.00, 'description' => 'Nilai dasar perhitungan retribusi', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'INFRASTRUCTURE_MULTIPLIER', 'value' => 0.50, 'description' => 'Pengali asumsi prasarana (50%)', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'HEIGHT_MULTIPLIER', 'value' => 0.50, 'description' => 'Pengali indeks ketinggian dalam formula', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅ Retribution data seeded successfully!');
        $this->command->info('📊 Building Types: 12 records');
        $this->command->info('📊 Retribution Indices: 9 records');
        $this->command->info('📊 Height Indices: 6 records');
        $this->command->info('📊 Retribution Configs: 3 records');
    }
}
