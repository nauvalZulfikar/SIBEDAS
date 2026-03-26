<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PbgTaskGoogleSheetResource;
use App\Models\PbgTaskGoogleSheet;
use Illuminate\Http\Request;

class PbgTaskGoogleSheetsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PbgTaskGoogleSheet::query()->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $query->where('no_registrasi', 'like', "%{$request->get('search')}%");
        }
        return PbgTaskGoogleSheetResource::collection($query->paginate(config('app.paginate_per_page', 50)));
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
        try{
            $data = PbgTaskGoogleSheet::find($id);
            $data->delete();
            return response()->json(['message' => 'Data deleted successfully'], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Failed to delete data'], 500);
        }
    }
}
