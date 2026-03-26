<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $permissions = $this->permissions[$menuId]?? []; // Avoid undefined index error
        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;

        return view('customers.index', compact('creator', 'updater', 'destroyer', 'menuId'));
    }
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id');
        return view('customers.create', compact('menuId'));
    }
    public function edit(Request $request, string $id)
    {
        $data = Customer::findOrFail($id);
        $menuId = $request->query('menu_id');
        return view('customers.edit', compact('data', 'menuId'));
    }
    public function upload(Request $request){
        $menuId = $request->query('menu_id');
        return view('customers.upload', compact('menuId'));
    }
}
