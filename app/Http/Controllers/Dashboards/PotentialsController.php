<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PotentialsController extends Controller
{
    public function inside_system(){
        $menus = Cache::remember('menus_all', 86400, fn() => Menu::all());
        return view('dashboards.potentials.inside_system', compact('menus'));
    }
    public function outside_system(){
        return view('dashboards.potentials.outside_system');
    }
}
