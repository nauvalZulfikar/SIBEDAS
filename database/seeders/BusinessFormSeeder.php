<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('business_form')->insert([
            ['business_form' => 'Perseorangan'],
            ['business_form' => 'Persekutuan'],
            ['business_form' => 'Koperasi'],
            ['business_form' => 'CV'],
            ['business_form' => 'PT'],
            ['business_form' => 'PTTB'],
            ['business_form' => 'Perseroan'],
        ]);
    }
}
