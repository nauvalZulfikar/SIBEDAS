<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvitationsController extends Controller
{
    public function index(Request $request){
        return view('invitations.index');
    }
}
