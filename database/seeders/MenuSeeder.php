<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;
use Illuminate\Support\Arr;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::whereIn('name', ['Data Pajak','Pajak'])->delete();

        $menus = [
            [
                "name" => "Neng Bedas",
                "url" => "main-chatbot.index",
                "icon" => "mingcute:wechat-line",
                "parent_id" => null,
                "sort_order" => 1,
            ],
            [
                "name" => "Dashboard",
                "url" => "/dashboard",
                "icon" => "mingcute:home-3-line",
                "parent_id" => null,
                "sort_order" => 2,
                "children" => [
                    [
                        "name" => "Dashboard Pimpinan (SIMBG)",
                        "url" => "dashboard.home",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                    [
                        "name" => "Dashboard PBG",
                        "url" => "dashboard.pbg",
                        "icon" => null,
                        "sort_order" => 2,
                    ],
                    [
                        "name" => "Dashboard Potensi",
                        "url" => '/potentials',
                        "icon" => null,
                        "sort_order" => 3,
                        "children" => [
                            [
                                "name" => "Luar Sistem",
                                "url" => "dashboard.potentials.inside_system",
                                "icon" => null,
                                "sort_order" => 1,
                            ],
                            [
                                "name" => "Dalam Sistem",
                                "url" => "dashboard.potentials.outside_system",
                                "icon" => null,
                                "sort_order" => 2,
                            ],
                        ]
                    ],
                    [
                        "name" => "PETA",
                        "url" => "dashboard.maps",
                        "icon" => null,
                        "sort_order" => 4,
                    ],
                ],
            ],
            [
                "name" => "Master",
                "url" => "/master",
                "icon" => "mingcute:cylinder-line",
                "parent_id" => null,
                "sort_order" => 3,
                "children" => [
                    [
                        "name" => "Users",
                        "url" => "users.index",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                ]
            ],
            [
                "name" => "Settings",
                "url" => "/settings",
                "icon" => "mingcute:settings-6-line",
                "parent_id" => null,
                "sort_order" => 4,
                "children" => [
                    [
                        "name" => "Syncronize",
                        "url" => "settings.syncronize",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                    [
                        "name" => "Menu",
                        "url" => "menus.index",
                        "icon" => null,
                        "sort_order" => 2,
                    ],
                    [
                        "name" => "Role",
                        "url" => "roles.index",
                        "icon" => null,
                        "sort_order" => 3,
                    ],
                ]
            ],
            [
                "name" => "Data Settings",
                "url" => "/data-settings",
                "icon" => "mingcute:settings-1-line",
                "parent_id" => null,
                "sort_order" => 5,
                "children" => [
                    [
                        "name" => "Setting Dashboard",
                        "url" => "data-settings.index",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                ]
            ],
            [
                "name" => "Data",
                "url" => "/data",
                "icon" => "mingcute:task-line",
                "parent_id" => null,
                "sort_order" => 6,
                "children" => [
                    [
                        "name" => "PBG",
                        "url" => "pbg-task.index",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                    [
                        "name" => "Reklame",
                        "url" => "web-advertisements.index",
                        "icon" => null,
                        "sort_order" => 2,
                    ],
                    [
                        "name" => "Usaha atau Industri",
                        "url" => "business-industries.index",
                        "icon" => null,
                        "sort_order" => 3,
                    ],
                    [
                        "name" => "UMKM",
                        "url" => "web-umkm.index",
                        "icon" => null,
                        "sort_order" => 4,
                    ],
                    [
                        "name" => "Pariwisata",
                        "url" => "web-tourisms.index",
                        "icon" => null,
                        "sort_order" => 5,
                    ],
                    [
                        "name" => "Tata Ruang",
                        "url" => "web-spatial-plannings.index",
                        "icon" => null,
                        "sort_order" => 6,
                    ],
                    [
                        "name" => "PDAM",
                        "url" => "customers",
                        "icon" => null,
                        "sort_order" => 7,
                    ],
                    [
                        "name" => "Google Sheets",
                        "url" => "google-sheets",
                        "icon" => null,
                        "sort_order" => 8,
                    ],
                    [
                        "name" => "TPA TPT",
                        "url" => "tpa-tpt.index",
                        "icon" => null,
                        "sort_order" => 9,
                    ],
                    [
                        "name" => "Pajak",
                        "url" => "taxation",
                        "icon" => null,
                        "sort_order" => 10,
                    ]
                ]
            ],
            [
                "name" => "Laporan",
                "url" => "/laporan",
                "icon" => "mingcute:report-line",
                "parent_id" => null,
                "sort_order" => 7,
                "children" => [
                    [
                        "name" => "Lap Pariwisata",
                        "url" => "tourisms-report.index",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                    [
                        "name" => "Lap Pimpinan",
                        "url" => "bigdata-resumes",
                        "icon" => null,
                        "sort_order" => 2,
                    ],
                    [
                        "name" => "Lap Pertumbuhan",
                        "url" => "growths",
                        "icon" => null,
                        "sort_order" => 3,
                    ],
                    [
                        "name" => "Rekap Pembayaran",
                        "url" => "payment-recaps",
                        "icon" => null,
                        "sort_order" => 4,
                    ],
                    [
                        "name" => "Lap Rekap Data Pembayaran",
                        "url" => "report-payment-recaps",
                        "icon" => null,
                        "sort_order" => 5,
                    ],
                    [
                        "name" => "Lap PBG (PTSP)",
                        "url" => "report-pbg-ptsp",
                        "icon" => null,
                        "sort_order" => 6,
                    ],
                ]
            ],
            [
                "name" => "Approval",
                "url" => "/approval",
                "icon" => "mingcute:user-follow-2-line",
                "parent_id" => null,
                "sort_order" => 8,
                "children" => [
                    [
                        "name" => "Approval Pejabat",
                        "url" => "approval-list",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                ]
            ],
            [
                "name" => "Tools",
                "url" => "/tools",
                "icon" => "mingcute:tool-line",
                "parent_id" => null,
                "sort_order" => 9,
                "children" => [
                    [
                        "name" => "Undangan",
                        "url" => "invitations",
                        "icon" => null,
                        "sort_order" => 1,
                    ],
                ]
            ],
        ];

        foreach ($menus as $menuData) {
            $this->createOrUpdateMenu($menuData);
        }
    }

    private function createOrUpdateMenu($menuData, $parentId = null){
        $menuData['parent_id'] = $parentId;

        $menu = Menu::updateOrCreate(['url' => $menuData['url']], Arr::except($menuData, ['children']));

        if(!empty($menuData['children'])){
            foreach($menuData['children'] as $child){
                $this->createOrUpdateMenu($child, $menu->id);
            }
        }
    }
}
