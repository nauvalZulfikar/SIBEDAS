<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermitStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permit_status')->insert([
            ['permit_status' => 'Not Registered'],
            ['permit_status' => 'Registered'],
            ['permit_status' => 'Application Process'],
        ]);
    }
}
