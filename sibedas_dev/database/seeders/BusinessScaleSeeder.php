<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessScaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('business_scale')->insert([
            ['business_scale' => 'Micro'],
            ['business_scale' => 'Kecil'],
            ['business_scale' => 'Menengah'],
        ]);
    }
}
