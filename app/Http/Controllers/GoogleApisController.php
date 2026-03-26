<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GoogleApisController extends Controller
{
    public function index(){
        return view('maps.google-api');
    }
}
