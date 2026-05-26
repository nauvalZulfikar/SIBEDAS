<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\ImportDatasource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BigDataController extends Controller
{
    public function index(){
        $latest_created = Cache::remember('latest_import_created', 86400, function () {
            $ds = ImportDatasource::latest()->first();
            return $ds ? $ds->created_at->format("j F Y H:i:s") : null;
        });
        $menus = Cache::remember('menus_all', 86400, fn() => Menu::all());
        return view('dashboards.bigdata', compact('latest_created', 'menus'));
    }

    public function pbg()
    {
        return view('dashboards.pbg');
    }

    public function leader()
    {
        $latest_created = Cache::remember('latest_import_created', 86400, function () {
            $ds = ImportDatasource::latest()->first();
            return $ds ? $ds->created_at->format("j F Y H:i:s") : null;
        });
        $menus = Cache::remember('menus_all', 86400, fn() => Menu::all());
        return view('dashboards.leader', compact('latest_created', 'menus'));
    }
}
