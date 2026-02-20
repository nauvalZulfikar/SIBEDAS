<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request){
        if(Auth::check()){
            return view('home.index');
        }else{
            return view('auth.signin');
        }
    }
}
