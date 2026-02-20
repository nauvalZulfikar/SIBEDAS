<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RoutingController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            return view('home.index');
        } else {
            return redirect('auth.signin');
        }
    }
}
