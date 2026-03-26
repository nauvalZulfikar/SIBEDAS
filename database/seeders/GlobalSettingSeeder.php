<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class GlobalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $globalSettings = [
            [
                "key" => "SIMBG_HOST",
                "value" => "https://simbg.pu.go.id",
                "type" => "string",
                "description" => "Host SIMBG",
                "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
            ],
            [
                "key" => "SIMBG_PASSWORD",
                "value" => "LogitechG29",
                "type" => "string",
                "description" => "Password SIMBG",
                "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
            ],
            [
                "key" => "SIMBG_EMAIL",
                "value" => "dputr@bandungkab.go.id",
                "type" => "string",
                "description" => "Email SIMBG",
                "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
            ],
            [
                "key" => "FETCH_PER_PAGE",
                "value" => "100",
                "type" => "integer",
                "description" => "Total data per page",
                "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
            ],
        ];
        foreach($globalSettings as $setting){
            GlobalSetting::updateOrCreate(
                ["key" => $setting["key"]],
                [
                    "value" =>$setting["value"],
                    "type" => $setting["type"],
                    "description" => $setting["description"],
                    "created_at" => Carbon::now()->format("Y-m-d H:i:s"),
                    "updated_at" => Carbon::now()->format("Y-m-d H:i:s"),
                ]
            );
        }
    }
}
