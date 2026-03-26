<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('provincies')->insert([
            ['province_code' => 11, 'province_name' => 'ACEH'],
            ['province_code' => 12, 'province_name' => 'SUMATERA UTARA'],
            ['province_code' => 13, 'province_name' => 'SUMATERA BARAT'],
            ['province_code' => 14, 'province_name' => 'RIAU'],
            ['province_code' => 15, 'province_name' => 'JAMBI'],
            ['province_code' => 16, 'province_name' => 'SUMATERA SELATAN'],
            ['province_code' => 17, 'province_name' => 'BENGKULU'],
            ['province_code' => 18, 'province_name' => 'LAMPUNG'],
            ['province_code' => 19, 'province_name' => 'KEPULAUAN BANGKA BELITUNG'],
            ['province_code' => 21, 'province_name' => 'KEPULAUAN RIAU'],
            ['province_code' => 31, 'province_name' => 'DKI JAKARTA'],
            ['province_code' => 32, 'province_name' => 'JAWA BARAT'],
            ['province_code' => 33, 'province_name' => 'JAWA TENGAH'],
            ['province_code' => 34, 'province_name' => 'DAERAH ISTIMEWA YOGYAKARTA'],
            ['province_code' => 35, 'province_name' => 'JAWA TIMUR'],
            ['province_code' => 36, 'province_name' => 'BANTEN'],
            ['province_code' => 51, 'province_name' => 'BALI'],
            ['province_code' => 52, 'province_name' => 'NUSA TENGGARA BARAT'],
            ['province_code' => 53, 'province_name' => 'NUSA TENGGARA TIMUR'],
            ['province_code' => 61, 'province_name' => 'KALIMANTAN BARAT'],
            ['province_code' => 62, 'province_name' => 'KALIMANTAN TENGAH'],
            ['province_code' => 63, 'province_name' => 'KALIMANTAN SELATAN'],
            ['province_code' => 64, 'province_name' => 'KALIMANTAN TIMUR'],
            ['province_code' => 65, 'province_name' => 'KALIMANTAN UTARA'],
            ['province_code' => 71, 'province_name' => 'SULAWESI UTARA'],
            ['province_code' => 72, 'province_name' => 'SULAWESI TENGAH'],
            ['province_code' => 73, 'province_name' => 'SULAWESI SELATAN'],
            ['province_code' => 74, 'province_name' => 'SULAWESI TENGGARA'],
            ['province_code' => 75, 'province_name' => 'GORONTALO'],
            ['province_code' => 76, 'province_name' => 'SULAWESI BARAT'],
            ['province_code' => 81, 'province_name' => 'MALUKU'],
            ['province_code' => 82, 'province_name' => 'MALUKU UTARA'],
            ['province_code' => 91, 'province_name' => 'PAPUA'],
            ['province_code' => 92, 'province_name' => 'PAPUA BARAT'],
            ['province_code' => 93, 'province_name' => 'PAPUA SELATAN'],
            ['province_code' => 94, 'province_name' => 'PAPUA TENGAH'],
            ['province_code' => 95, 'province_name' => 'PAPUA PEGUNUNGAN'],
        ]);
    }
}
