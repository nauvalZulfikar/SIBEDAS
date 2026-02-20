<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportPaymentRecapsController extends Controller
{
    public function index(Request $request){
        return view('report-payment-recaps.index');
    }
}
