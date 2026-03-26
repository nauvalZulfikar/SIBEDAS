<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'user@sibedas.com',
                'name' => 'User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'firstname' => 'user',
                'lastname' => 'user',
                'position' => 'user',
                'remember_token' => Str::random(10),
            ],
            [
                'email' => 'superadmin@sibedas.com',
                'name' => 'Superadmin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'firstname' => 'superadmin',
                'lastname' => 'superadmin',
                'position' => 'superadmin',
                'remember_token' => Str::random(10),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']], // Search condition
                $user // Update or create with this data
            );
        }

        $this->call([
            RoleSeeder::class,
            MenuSeeder::class,
            UsersRoleMenuSeeder::class,
            GlobalSettingSeeder::class,
            RetributionDataSeeder::class,
        ]);
    }
}
