<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\TourismBasedKBLI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportTourismController extends Controller
{
    /**
     * Display a listring of the resource
     */
    public function index()
    {
        $tourismBasedKBLI = TourismBasedKBLI::all();
        return view('report.tourisms.index', compact('tourismBasedKBLI'));
    }
}