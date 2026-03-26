<?php

namespace App\Http\Controllers\Api;

use App\Models\Tourism;
use Illuminate\Http\Request;
use App\Http\Requests\TourismRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\TourismResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TourismImport;
use Illuminate\Support\Facades\Storage;

class TourismController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = Tourism::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%$search%")
                    ->orWhere('project_name', 'like', "%$search%")
                    ->orWhere('business_address', 'like', "%$search%");
            });
        }
        $tourisms = $query->paginate($perPage);

        $tourisms->getCollection()->transform(function ($tourisms) {
            $village = DB::table('villages')->where('village_code', $tourisms->village_code)->first();
            $tourisms->village_name = $village ? $village->village_name : null;

            $district = DB::table('districts')->where('district_code', $tourisms->district_code)->first();
            $tourisms->district_name = $district ? $district->district_name : null;
            return $tourisms;
        });

        $start = ($tourisms->currentPage()-1) * $perPage + 1;

        $data = $tourisms->map(function ($item, $index) use ($start) {
            return array_merge($item->toArray(), ['no' => $start + $index]);
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $tourisms->total(),
                'per_page' => $tourisms->perPage(),
                'current_page' => $tourisms->currentPage(),
                'last_page'=>$tourisms->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TourismRequest $request): Tourism
    {
        $data = $request->validated();
        $district_code = DB::table('districts')->where('district_name', $data['district_name'])->value('district_code');
        $village_code = DB::table('villages')->where('village_name', $data['village_name'])->where('district_code', $district_code)->value('village_code');

        $data['district_code'] = $district_code;
        $data['village_code'] = $village_code;

        return Tourism::create($data);
    }

    /**
     * Import advertisements from Excel
     */
    public function importFromFile(Request $request)
    {
        //Validasi file
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx, xls|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message'=>'File validation failed.',
                'errors'=>$validator->errors()
            ], 400);
        }

        try {
            $file = $request->file('file');
            Excel::import(new TourismImport, $file);
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

    public function getAllLocation()
    {
        $locations = Tourism::whereNotNull('longitude')
                    ->whereNotNull('latitude')
                    ->select('project_name', 'longitude', 'latitude')
                    ->get();

        return response()->json([
            'data' => $locations
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tourism $tourism): Tourism
    {
        return $tourism;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TourismRequest $request, Tourism $tourism): Tourism
    {
        $data = $request->validated();

        // Cari district_code berdasarkan district_name
        $district_code = DB::table('districts')->where('district_name', $data['district_name'])->value('district_code');
        // Cari village_code berdasarkan village_name
        $village_code = DB::table('villages')->where('village_name', $data['village_name'])->where('district_code', $district_code)->value('village_code');
        // Tambahkan village_code dan district_code ke data
        $data['village_code'] = $village_code;
        $data['district_code'] = $district_code;

        $tourism->update($data);

        return $tourism;
    }

    public function destroy(Tourism $tourism): Response
    {
        $tourism->delete();

        return response()->noContent();
    }

    public function downloadExcelTourism()
    {
        $filePath = public_path('templates/template_pariwisata.xlsx');
        info(sprintf("File Path: %s | Exists: %s", $filePath, file_exists($filePath) ? 'Yes' : 'No'));

        // Cek apakah file ada
        if (!file_exists($filePath)) {
            return response()-> json(['message' => 'File tidak ditemukan!'], Response::HTTP_NOT_FOUND);
        }

        // Return file to download
        return response()->download($filePath);
    }
}
