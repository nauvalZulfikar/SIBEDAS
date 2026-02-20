<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Models\PbgTaskGoogleSheet;
use Illuminate\Http\Request;

class GoogleSheetsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menu_id = $request->query('menu_id');
        $user_menu_permission = $this->permissions[$menu_id];
        return view('data.google-sheet.index', compact('user_menu_permission'));
    }

    public function create()
    {
        return view('data.google-sheet.create');
    }

    public function show(string $id)
    {
        $data = PbgTaskGoogleSheet::find($id);
        return view('data.google-sheet.show', compact('data'));
    }

    public function edit(string $id)
    {
        return view('data.google-sheet.edit');
    }
}
