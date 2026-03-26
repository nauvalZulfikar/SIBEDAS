<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class SyncronizeController extends Controller
{
    public function index(Request $request){
        $menuId = $request->query('menu_id');
        $user = Auth::user();
        $userId = $user->id;

        // Ambil role_id yang dimiliki user
        $roleIds = DB::table('user_role')
            ->where('user_id', $userId)
            ->pluck('role_id');

        // Ambil data akses berdasarkan role_id dan menu_id
        $roleAccess = DB::table('role_menu')
            ->whereIn('role_id', $roleIds)
            ->where('menu_id', $menuId)
            ->first();

        // Pastikan roleAccess tidak null sebelum mengakses properti
        $creator = $roleAccess->allow_create ?? 0;
        $updater = $roleAccess->allow_update ?? 0;
        $destroyer = $roleAccess->allow_destroy ?? 0;

        return view('settings.syncronize.index', compact('creator', 'updater', 'destroyer'));
    }
}
