<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LackOfPotentialController extends Controller
{
    public function lack_of_potential(){
        return view('dashboards.lack_of_potential');
    }
}
