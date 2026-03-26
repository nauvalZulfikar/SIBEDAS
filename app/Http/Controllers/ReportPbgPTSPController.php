<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportPbgPTSPController extends Controller
{
    public function index(Request $request){
        return view('report-pbg-ptsp.index');
    }
}
