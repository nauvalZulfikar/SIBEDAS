<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class PotentialsController extends Controller
{
    public function inside_system(){
        $menus = Menu::all();
        return view('dashboards.potentials.inside_system', compact('menus'));
    }
    public function outside_system(){
        return view('dashboards.potentials.outside_system');
    }
}
