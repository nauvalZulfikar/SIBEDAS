<?php

namespace App\Http\Controllers\Api;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use App\Http\Requests\AdvertisementRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvertisementResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AdvertisementImport;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15); // Default 15 jika tidak dikirim oleh client
        $search = $request->input('search', ''); // Ambil parameter 'search' jika ada

        // Query dasar untuk mengambil iklan
        $query = Advertisement::query();
        // Jika ada pencarian, filter berdasarkan kolom yang diinginkan
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%$search%")
                    ->orWhere('npwpd', 'like', "%$search%")
                    ->orWhere('advertisement_content', 'like', "%$search%")
                    ->orWhere('business_address', 'like', "%$search%")
                    ->orWhere('advertisement_location', 'like', "%$search%")
                    ->orWhereIn('village_code', function ($subQuery) use ($search) {
                        $subQuery->select('village_code')
                            ->from('villages')
                            ->where('village_name', 'like', "%$search%");
                    })
                    ->orWhereIn('district_code', function ($subQuery) use ($search) {
                        $subQuery->select('district_code')
                            ->from('districts')
                            ->where('district_name', 'like', "%$search%");
                    });
            });
        }

        $advertisements = $query->paginate($perPage);

        $advertisements->getCollection()->transform(function ($advertisement) {
            $village = DB::table('villages')->where('village_code', $advertisement->village_code)->first();
            $advertisement->village_name = $village ? $village->village_name : null;
            
            $district = DB::table('districts')->where('district_code', $advertisement->district_code)->first();
            $advertisement->district_name = $district ? $district->district_name : null;
            return $advertisement;
        });

        return response()->json([
            'data' => AdvertisementResource::collection($advertisements),
            'meta' => [
                'total' => $advertisements->total(),
                'per_page' => $advertisements->perPage(),
                'current_page' => $advertisements->currentPage(),
                'last_page' => $advertisements->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdvertisementRequest $request): Advertisement
    {
        $data = $request->validated();

        // Cari district_code berdasarkan district_name
        $district_code = DB::table('districts')->where('district_name', $data['district_name'])->value('district_code');
        // Cari village_code berdasarkan village_name
        $village_code = DB::table('villages')->where('village_name', $data['village_name'])->where('district_code', $district_code)->value('village_code');

        // Tambahkan village_code dan district_code ke data
        $data['village_code'] = $village_code;
        $data['district_code'] = $district_code;

        // Log data setelah transformasi
        info($data);
        return Advertisement::create($data);
    }

    /**
     * Import advertisements from Excel or CSV.
     */
    public function importFromFile(Request $request)
    {
        // Validasi file
        info($request);
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls|max:10240', // Max 10MB
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
            Excel::import(new AdvertisementImport, $file);

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

    /**
     * Display the specified resource.
     */
    public function show(Advertisement $advertisement): Advertisement
    {
        return $advertisement;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdvertisementRequest $request, Advertisement $advertisement): Advertisement
    {
        $data = $request->validated();

        // Cari district_code berdasarkan district_name
        $district_code = DB::table('districts')->where('district_name', $data['district_name'])->value('district_code');
        // Cari village_code berdasarkan village_name
        $village_code = DB::table('villages')->where('village_name', $data['village_name'])->where('district_code', $district_code)->value('village_code');

        // Tambahkan village_code dan district_code ke data
        $data['village_code'] = $village_code;
        $data['district_code'] = $district_code;

        // Log data setelah transformasi
        info($data);
        
        $advertisement->update($data);

        return $advertisement;
    }

    public function destroy(Advertisement $advertisement): Response
    {
        $advertisement->delete();

        return response()->noContent();
    }

    public function searchOptionsInAdvertisements(Request $request)
    {
        $query = $request->input('query');
        $field = $request->input('field');
        $district = $request->input('district'); // Ambil kecamatan jika ada

        info("Query: $query, Field: $field, District: $district");

        if ($field === 'district_name') {
            $results = DB::table('districts')
                ->where('district_name', 'like', '%' . $query . '%')
                ->limit(10)
                ->get(['district_name AS name', 'district_code AS code']);
        } elseif ($field === 'village_name' && $district) {
            $results = DB::table('villages')
                ->where('village_name', 'like', '%' . $query . '%')
                ->whereExists(function ($query) use ($district) {
                    $query->select(DB::raw(1))
                        ->from('districts')
                        ->whereColumn('villages.district_code', 'districts.district_code')
                        ->where('districts.district_name', $district);
                })
                ->limit(10)
                ->get(['village_name AS name', 'village_code AS code']);
        } else {
            $results = collect();
        }

        return response()->json($results);
    }

    public function downloadExcelAdvertisement()
    {
        $filePath = public_path('templates/template_reklame.xlsx');

        // Cek apakah file ada
        if (!file_exists($filePath)) {
            return response()-> json(['message' => 'File tidak ditemukan!'], Response::HTTP_NOT_FOUND);
        }

        // Return file to download
        return response()->download($filePath);
    }
}
