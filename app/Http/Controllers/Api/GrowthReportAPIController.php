<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BigdataResume;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrowthReportAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get current date
        $today = Carbon::today();
    
        // Define default range: 1 month back from today
        $defaultStart = $today->copy()->subMonth();
        $defaultEnd = $today;
    
        // Use request values if provided, else use defaults
        // $startDate = $request->input('start_date', $defaultStart->toDateString());
        // $endDate = $request->input('end_date', $defaultEnd->toDateString());
    
        // Optional year filter (used if specified)
        $year = $request->input('year', now()->year);
    
        // $query = BigdataResume::selectRaw("
        //     DATE(created_at) as date,
        //     SUM(potention_sum) as potention_sum,
        //     SUM(verified_sum) as verified_sum,
        //     SUM(non_verified_sum) as non_verified_sum
        // ")
        // ->whereBetween('created_at', [$startDate, $endDate]);
        $query = BigdataResume::selectRaw("
            DATE(created_at) as date,
            SUM(potention_sum) as potention_sum,
            SUM(verified_sum) as verified_sum,
            SUM(non_verified_sum) as non_verified_sum
        ");
    
        $query->whereNotNull('year')
        ->where('year', '!=', 'all');
    
        $data = $query->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get()
            ->map(function ($item) {
                $item->date = Carbon::parse($item->date)->format('d M Y');
                return $item;
            });
    
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
