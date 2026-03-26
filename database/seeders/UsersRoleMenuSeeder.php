<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersRoleMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        // Fetch roles in a single query
        $roles = Role::whereIn('name', ['superadmin', 'user', 'operator'])->get()->keyBy('name');

        // Fetch all menus in a single query and index by name
        $menus = Menu::whereIn('name', [
            'Dashboard', 'Master', 'Settings', 'Data Settings', 'Data', 'Laporan', 'Neng Bedas', 
            'Approval', 'Tools', 'Users', 'Syncronize', 'Dashboard Pimpinan (SIMBG)',
            'Menu', 'Role', 'Setting Dashboard', 'PBG', 'Reklame', 'Usaha atau Industri', 'Pariwisata', 
            'Lap Pariwisata', 'UMKM', 'Dashboard Potensi', 'Tata Ruang', 'PDAM', 'PETA', 
            'Lap Pimpinan', 'Dalam Sistem', 'Luar Sistem', 'Google Sheets', 'TPA TPT', 'Pajak',
            'Approval Pejabat', 'Undangan', 'Rekap Pembayaran', 'Lap Rekap Data Pembayaran', 'Lap PBG (PTSP)', 'Lap Pertumbuhan'
        ])->get()->keyBy('name');

        // Define access levels for each role
        $permissions = [
            'superadmin' => [
                'Dashboard', 'Master', 'Settings', 'Data Settings', 'Data', 'Laporan', 'Neng Bedas', 
                'Approval', 'Tools', 'Users', 'Syncronize', 'Dashboard Pimpinan (SIMBG)',
                'Menu', 'Role', 'Setting Dashboard', 'PBG', 'Reklame', 'Usaha atau Industri', 'Pariwisata', 
                'Lap Pariwisata', 'UMKM', 'Dashboard Potensi', 'Tata Ruang', 'PDAM', 'Dalam Sistem', 
                'Luar Sistem', 'Lap Pimpinan', 'Google Sheets', 'TPA TPT', 'Approval Pejabat', 
                'Undangan', 'Rekap Pembayaran', 'Lap Rekap Data Pembayaran', 'Lap PBG (PTSP)', 'Lap Pertumbuhan', 'Pajak'
            ],
            'user' => ['Dashboard', 'Data', 'Laporan', 'Neng Bedas', 
                'Approval', 'Tools', 'Users', 'Syncronize', 'Dashboard Pimpinan (SIMBG)',
                'Menu', 'Role', 'Setting Dashboard', 'PBG', 'Reklame', 'Usaha atau Industri', 'Pariwisata', 
                'Lap Pariwisata', 'UMKM', 'Dashboard Potensi', 'Tata Ruang', 'PDAM', 'Dalam Sistem', 
                'Luar Sistem', 'Lap Pimpinan', 'Google Sheets', 'TPA TPT', 'Approval Pejabat', 
                'Undangan', 'Rekap Pembayaran', 'Lap Rekap Data Pembayaran', 'Lap PBG (PTSP)'],
            'operator' => ['Dashboard', 'Data', 'Laporan']
        ];

        // Define permission levels
        $superadminPermissions = ["allow_show" => true, "allow_create" => true, "allow_update" => true, "allow_destroy" => true];
        $userPermissions = ["allow_show" => true, "allow_create" => false, "allow_update" => false, "allow_destroy" => false];
        $operatorPermissions = ["allow_show" => true, "allow_create" => false, "allow_update" => false, "allow_destroy" => false];

        // Assign menus to roles
        foreach ($permissions as $roleName => $menuNames) {
            $role = $roles[$roleName] ?? null;
            if ($role) {
                $role->menus()->sync(
                    collect($menuNames)->mapWithKeys(fn($menuName) => [
                        $menus[$menuName]->id => ($roleName === 'superadmin' ? $superadminPermissions : 
                                                ($roleName === 'operator' ? $operatorPermissions : $userPermissions))
                    ])->toArray()
                );
            }
        }
        
        // Attach User to role super admin
        $accountSuperadmin = User::where('email', 'superadmin@sibedas.com')->first();
        $accountDevelopment = User::where('email', 'development@sibedas.com')->first();
        $accountUser = User::where('email', 'user@sibedas.com')->first();
        $accountSuperadmin->roles()->sync([$roles['superadmin']->id]);
        $accountDevelopment->roles()->sync([$roles['superadmin']->id]);
        $accountUser->roles()->sync([$roles['user']->id]);
    }
}
