<?php

namespace App\Http\Controllers\ChatbotPimpinan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotPimpinanController extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index()
    {
        return view('chatbot-pimpinan.index');
    }
}