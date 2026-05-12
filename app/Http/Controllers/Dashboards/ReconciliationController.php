<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;

class ReconciliationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $clearance = 'level_1';
        if ($user) {
            $best = $user->roles()->pluck('pbb_clearance')->all();
            $rank = ['level_1' => 1, 'level_2' => 2, 'level_3' => 3];
            foreach ($best as $l) {
                if (($rank[$l] ?? 0) > ($rank[$clearance] ?? 0)) $clearance = $l;
            }
        }
        return view('dashboards.reconciliation', ['pbbClearance' => $clearance]);
    }
}
