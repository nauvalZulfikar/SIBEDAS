<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Displya a listing of the resource
     */
    public function index()
    {
        return view('chatbot.index');
    }
}
