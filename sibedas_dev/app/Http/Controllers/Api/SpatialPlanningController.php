<?php

namespace App\Http\Controllers\Api;

use App\Models\SpatialPlanning;
use Illuminate\Http\Request;
use App\Http\Requests\SpatialPlanningRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\SpatialPlanningResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SpatialPlanningImport;
use Illuminate\Support\Facades\Storage;

class SpatialPlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        info($request);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = SpatialPlanning::query();
        
        // Only include spatial plannings that are not yet issued (is_terbit = false)
        $query->where('is_terbit', false);
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('kbli', 'like', "%$search%")
                    ->orWhere('activities', 'like', "%$search%")
                    ->orWhere('area', 'like', "%$search%")
                    ->orWhere('location', 'like', "%$search%")
                    ->orWhere('number', 'like', "%$search%");
            });
        }
                
        $spatialPlannings = $query->paginate($perPage);

        // Menambhakan nomor urut (No)
        $start = ($spatialPlannings->currentPage()-1) * $perPage + 1;

        // Tambahkan nomor urut ke dalam data (calculated_retribution sudah auto-append)
        $data = $spatialPlannings->map(function ($item, $index) use ($start) {
            $itemArray = $item->toArray();
            $itemArray['no'] = $start + $index;
            return $itemArray;
        });

        info($data);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $spatialPlannings->total(),
                'per_page' => $spatialPlannings->perPage(),
                'current_page' => $spatialPlannings->currentPage(),
                'last_page'=>$spatialPlannings->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SpatialPlanningRequest $request): SpatialPlanning
    {
        $data = $request->validated();
        return SpatialPlanning::create($data);
    }
    
    /**
     * import spatial planning from excel
     */
    public function importFromFile(Request $request)
    {
        info($request);
        //validasi file
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx, xls|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message'=>'File vaildation failed.',
                "errors"=>$validator->errors()
            ], 400);
        }

        try {
            $file = $request->file('file');
            Excel::import(new SpatialPlanningImport, $file);
            return response()->json([
                'message'=>'File uploaded and imported successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message'=>'Error during file import.',
                'error'=>$e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SpatialPlanning $spatialPlanning): array
    {
        // calculated_retribution and formatted_retribution are already appended via $appends
        return $spatialPlanning->toArray();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SpatialPlanningRequest $request, SpatialPlanning $spatialPlanning): SpatialPlanning
    {
        info($request);
        $data = $request->validated();
        info($data);

        $spatialPlanning->update($data);

        return $spatialPlanning;
    }

    public function destroy(SpatialPlanning $spatialPlanning): Response
    {
        $spatialPlanning->delete();

        return response()->noContent();
    }

    public function downloadExcelSpatialPlanning()
    {
        $filePath = public_path('templates/template_spatial_planning.xlsx');
        info(sprintf("File Path: %s | Exists: %s", $filePath, file_exists($filePath) ? 'Yes' : 'No'));

        // Cek apakah file ada
        if (!file_exists($filePath)) {
            return response()-> json(['message' => 'File tidak ditemukan!'], Response::HTTP_NOT_FOUND);
        }

        // Return file to download
        return response()->download($filePath);
    }
}
