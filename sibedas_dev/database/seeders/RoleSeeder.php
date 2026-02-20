<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                "name" => "superadmin",
                "description" => "show all menus for super admins",
            ],
            [
                "name" => "operator",
                "description" => "show only necessary menus for operators",
            ],
            [
                "name" => "user",
                "description" => "show only necessary menus for users",
            ]
        ];

        Role::upsert($roles, ['name']);
    }
}
