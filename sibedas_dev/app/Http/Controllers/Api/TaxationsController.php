<?php

namespace App\Http\Controllers\Api;

use App\Exports\TaxationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExcelUploadRequest;
use App\Http\Requests\TaxationsRequest;
use App\Http\Resources\TaxationsResource;
use App\Imports\TaxationsImport;
use Illuminate\Http\Request;
use App\Models\Tax;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TaxationsController extends Controller
{
    public function index(Request $request)
    {
        try{
            $query = Tax::query()->orderBy('id', 'desc');

            if($request->has('search') && !empty($request->get('search'))){
                $query->where('tax_no', 'like', '%'. $request->get('search') . '%')
                ->orWhere('wp_name', 'like', '%'. $request->get('search') . '%')
                ->orWhere('business_name', 'like', '%'. $request->get('search') . '%');
            }

            return TaxationsResource::collection($query->paginate(config('app.paginate_per_page', 50)));
        }catch(\Exception $e){
            Log::info($e->getMessage());
            return response()->json([
                'error' => 'Failed to get data',
               'message' => $e->getMessage()
            ], 500);
        }
    }

    public function upload(ExcelUploadRequest $request)
    {
        try{
            if(!$request->hasFile('file')){
                return response()->json([
                    'error' => 'No file provided'
                ], 400);
            }

            $file = $request->file('file');
            Excel::import(new TaxationsImport, $file);
            return response()->json(['message' => 'File uploaded successfully'], 200);
        }catch(\Exception $e){
            Log::info($e->getMessage());
            return response()->json([
                'error' => 'Failed to upload file',
               'message' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        return Excel::download(new TaxationsExport, 'pajak_per_kecamatan.xlsx');
    }

    public function delete(Request $request)
    {
        try{
            $tax = Tax::find($request->id);
            $tax->delete();
            return response()->json(['message' => 'Data deleted successfully'], 200);
        }catch(\Exception $e){
            Log::info($e->getMessage());
            return response()->json([
                'error' => 'Failed to delete data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(TaxationsRequest $request, string $id)
    {
        try{
            $tax = Tax::find($id);
            if($tax){
                $tax->update($request->validated());
                return response()->json(['message' => 'Successfully updated', new TaxationsResource($tax)]);
            } else {
                return response()->json(['message' => 'Tax not found'], 404);
            }
        }catch(\Exception $e){
            Log::info($e->getMessage());
            return response()->json([
                'error' => 'Failed to update tax',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
