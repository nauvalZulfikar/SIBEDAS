<?php

namespace Database\Seeders;

use App\Models\DataSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data_settings = [
            [
            "key" => "TARGET_PAD",
            "value" => "50000000000",
            "type" => "integer"
            ],
            [
            "key" => "TATA_RUANG",
            "value" => "10000000000",
            "type" => "integer"
            ],
            [
                "key" => "REALISASI_TERBIT_PBG_SUM",
                "value" => "1507253788",
                "type" => "integer"
            ],
            [
                "key" => "REALISASI_TERBIT_PBG_COUNT",
                "value" => "88",
                "type" => "integer"
            ],
            [
                "key" => "MENUNGGU_KLIK_DPMPTSP_SUM",
                "value" => "83457536",
                "type" => "integer"
            ],
            [
                "key" => "MENUNGGU_KLIK_DPMPTSP_COUNT",
                "value" => "266",
                "type" => "integer"
            ],
            [
                "key" => "PROSES_DINAS_TEKNIS_SUM",
                "value" => "83457536",
                "type" => "integer"
            ],
            [
                "key" => "PROSES_DINAS_TEKNIS_COUNT",
                "value" => "11",
                "type" => "integer"
            ],
        ];

        foreach ($data_settings as $setting) {
            DataSetting::updateOrCreate([
                "key" => $setting["key"],
            ],[
                "value" => $setting["value"],
                "type" => $setting["type"],
                "created_at" => now(),
                "updated_at" => now(),
            ]);
        }
    }
}
