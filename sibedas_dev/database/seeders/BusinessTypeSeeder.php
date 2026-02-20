<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('business_type')->insert([
            ['business_type' => 'Villas'],
            ['business_type' => 'Hotels'],
            ['business_type' => 'Restaurants / Food Stores'],
            ['business_type' => 'Cafes'],
            ['business_type' => 'Adventure / Outdoor Activities'],
            ['business_type' => 'Event Organizers'],
            ['business_type' => 'Travel & Tours'],
            ['business_type' => 'Miscellaneous'],
            ['business_type' => 'Others'],
        ]);
    }
}
