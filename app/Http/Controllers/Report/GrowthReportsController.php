<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class GrowthReportsController extends Controller
{
    public function index(){
        return view('report.growth-report.index');
    }
}
