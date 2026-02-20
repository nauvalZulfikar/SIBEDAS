<?php

namespace App\Http\Controllers;

use App\Models\BusinessOrIndustry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BusinessOrIndustriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $permissions = $this->permissions[$menuId]?? []; // Avoid undefined index error
        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;
        return view('business-industries.index', compact('creator', 'updater', 'destroyer','menuId'));
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        return view("business-industries.create", compact('menuId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, Request $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $data = BusinessOrIndustry::findOrFail($id);
        return view('business-industries.edit', compact('data', 'menuId'));
    }
}
