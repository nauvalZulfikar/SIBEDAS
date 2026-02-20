<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\ImportDatasource;
use App\Models\Menu;
use Illuminate\Http\Request;

class BigDataController extends Controller
{
    public function index(){
        $latest_import_datasource = ImportDatasource::latest()->first();
        $latest_created = $latest_import_datasource ? 
        $latest_import_datasource->created_at->format("j F Y H:i:s") : null;
        $menus = Menu::all();
        return view('dashboards.bigdata', compact('latest_created', 'menus'));
    }

    public function pbg()
    {
        return view('dashboards.pbg');
    }

    public function leader()
    {
        $latest_import_datasource = ImportDatasource::latest()->first();
        $latest_created = $latest_import_datasource ? 
        $latest_import_datasource->created_at->format("j F Y H:i:s") : null;
        $menus = Menu::all();
        return view('dashboards.leader', compact('latest_created', 'menus'));
    }
}
