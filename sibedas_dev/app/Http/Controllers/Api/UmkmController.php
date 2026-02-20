<?php

namespace App\Http\Controllers\Api;

use App\Models\Umkm;
use Illuminate\Http\Request;
use App\Http\Requests\UmkmRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UmkmImport;
use Illuminate\Support\Facades\Storage;

class UmkmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        info($request);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = Umkm::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%$search%")
                    ->orWhere('business_address', 'like', "%$search%")
                    ->orWhere('business_desc', 'like', "%$search%")
                    ->orWhere('business_id_number', 'like', "%$search%")
                    ->orWhere('owner_id', 'like', "%$search%")
                    ->orWhere('owner_name', 'like', "%$search%");
            });
        }

        $umkm = $query->paginate($perPage);

        $umkm->getCollection()->transform(function ($umkm) {
            $village = DB::table('villages')->where('village_code', $umkm->village_code)->first();
            $umkm->village_name = $village ? $village->village_name : null;
            
            $district = DB::table('districts')->where('district_code', $umkm->district_code)->first();
            $umkm->district_name = $district ? $district->district_name : null;

            $business_scale = DB::table('business_scale')->where('id', $umkm->business_scale_id)->first();
            $umkm->business_scale = $business_scale ? $business_scale->business_scale : null;

            $permit_status = DB::table('permit_status')->where('id', $umkm->permit_status_id)->first();
            $umkm->permit_status = $permit_status ? $permit_status->permit_status : null;

            $business_form = DB::table('business_form')->where('id', $umkm->business_form_id)->first();
            $umkm->business_form = $business_form ? $business_form->business_form : null;
            return $umkm;
        });

        $start = ($umkm->currentPage()-1) * $perPage + 1;

        $data = $umkm->map(function ($item, $index) use ($start) {
            return array_merge($item->toArray(), ['no' => $start + $index]);
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $umkm->total(),
                'per_page' => $umkm->perPage(),
                'current_page' => $umkm->currentPage(),
                'last_page' => $umkm->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UmkmRequest $request): Umkm
    {
        info($request);
        $data = $request->validated();

        // Cari kode berdasarkan nama
        $district_code = DB::table('districts')->where('district_name', $data['district_name'])->value('district_code');
        $village_code = DB::table('villages')->where('village_name', $data['village_name'])->where('district_code', $district_code)->value('village_code');
        $business_scale_id = DB::table('business_scale')->where('id', $data['business_scale_id'])->value('id');
        $permit_status_id = DB::table('permit_status')->where('id', $data['permit_status_id'])->value('id');
        $business_form_id = DB::table('business_form')->where('id', $data['business_form_id'])->value('id');

        info($business_scale_id);

        // Update data dengan kode yang ditemukan
        $data['village_code'] = $village_code;
        $data['district_code'] = $district_code;
        $data['land_area'] = (double) $request['land_area'];
        $data['business_scale_id'] = (int) $business_scale_id;
        $data['permit_status_id'] = (int) $permit_status_id;
        $data['business_form_id'] = (int) $business_form_id;

        info($data);

        // Simpan ke database
        return Umkm::create($data);
    }

    /**
     * Import advertisements from Excel or CSV.
     */
    public function importFromFile(Request $request)
    {
        // Validasi file
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx, xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'File validation failed.',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Ambil file dari request
            $file = $request->file('file');

            // Menggunakan Laravel Excel untuk mengimpor file
            Excel::import(new UmkmImport, $file);

            // Jika sukses, kembalikan response sukses
            return response()->json([
                'message' => 'File uploaded and imported successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error during file import.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Umkm $umkm): Umkm
    {
        return $umkm;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UmkmRequest $request, Umkm $umkm): Umkm
    {
        info($request);
        $data = $request->validated();

        
        // Cari district_code berdasarkan district_name
        $district_code = DB::table('districts')->where('district_name', $data['district_name'])->value('district_code');
        // Cari village_code berdasarkan village_name
        $village_code = DB::table('villages')->where('village_name', $data['village_name'])->where('district_code', $district_code)->value('village_code');
        $business_scale_id = DB::table('business_scale')->where('id', $data['business_scale_id'])->value('id');
        $permit_status_id = DB::table('permit_status')->where('id', $data['permit_status_id'])->value('id');
        $business_form_id = DB::table('business_form')->where('id', $data['business_form_id'])->value('id');

        
        // Tambahkan village_code dan district_code ke data
        $data['village_code'] = $village_code;
        $data['district_code'] = $district_code;
        $data['land_area'] = (double) $request['land_area'];
        $data['business_scale_id'] = (int) $business_scale_id;
        $data['permit_status_id'] = (int) $permit_status_id;
        $data['business_form_id'] = (int) $business_form_id;

        // Log data setelah transformasi
        info($data);

        $umkm->update($data);

        return $umkm;
    }

    public function destroy(Umkm $umkm): Response
    {
        $umkm->delete();

        return response()->noContent();
    }

    public function downloadExcelUmkm()
    {
        $filePath = public_path('templates/template_umkm.xlsx');

        // Cek apakah file ada
        if (!file_exists($filePath)) {
            return response()-> json(['message' => 'File tidak ditemukan!'], Response::HTTP_NOT_FOUND);
        }

        // Return file to download
        return response()->download($filePath);
    }
}
