<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessIndustryRequest;
use App\Imports\BusinessIndustriesImport;
use App\Models\BusinessOrIndustry;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use \Illuminate\Support\Facades\Validator;
use App\Http\Requests\ExcelUploadRequest;
class BusinessOrIndustriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BusinessOrIndustry::query()->orderBy('id', 'desc');

        if ($request->has("search") && !empty($request->get("search"))) {
            $search = $request->get("search");

            info($request); // Debugging log

            $query->where(function ($q) use ($search) {
                $q->where("nop", "LIKE", "%{$search}%")
                ->orWhere("nama_kecamatan", "LIKE", "%{$search}%")
                ->orWhere("nama_kelurahan", "LIKE", "%{$search}%");
            });
        }

        return response()->json($query->paginate(config('app.paginate_per_page', 50)));
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
    public function update(BusinessIndustryRequest $request, string $id)
    {
        try{
            $data = BusinessOrIndustry::findOrFail($id);
            $data->update($request->validated());
            return response()->json(['message' => 'Data updated successfully.'], 200);
        }catch(\Exception $e){
            \Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to update data'],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $data = BusinessOrIndustry::findOrFail($id);
            $data->delete();
            return response()->json(['message' => 'Data deleted successfully.'], 200);
        }catch(\Exception $e){
            \Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to delete data'],500);
        }
    }

    public function upload(ExcelUploadRequest $request){
        try {
            if(!$request->hasFile('file')){
                return response()->json([
                   'error' => 'No file provided'
                ], 400);
            }

            $file = $request->file('file');
            Excel::import(new BusinessIndustriesImport, $file);

            // Jika sukses, kembalikan respons sukses
            return response()->json([
                'message' => 'File uploaded and imported successfully!'
            ], 200);
        } catch (\Exception $e) {
            // Jika ada error, kembalikan error response
            return response()->json([
                'message' => 'Error during file import.',
                'error' => $e->getMessage()
            ], 500);
        }

    }
}
