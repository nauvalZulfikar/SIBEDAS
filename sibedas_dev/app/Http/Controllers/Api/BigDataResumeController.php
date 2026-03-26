<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReportDirectorExport;
use App\Exports\ReportPaymentRecapExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\BigdataResumeResource;
use App\Models\BigdataResume;
use App\Models\DataSetting;
use App\Models\SpatialPlanning;
use App\Models\PbgTaskPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BigDataResumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $filterDate = $request->get("filterByDate");
            $type = trim($request->get("type"));

            if (!$filterDate || $filterDate === "latest") {
                $big_data_resume = BigdataResume::where('resume_type', $type)->where('year', date('Y'))->latest()->first();
                if (!$big_data_resume) {
                    return $this->response_empty_resume();
                }
            } else {
                $big_data_resume = BigdataResume::whereDate('created_at', $filterDate)
                    ->where('resume_type', $type)
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$big_data_resume) {
                    return $this->response_empty_resume();
                }
            }

            $data_settings = DataSetting::all();
            $target_pad = 0;
            if($data_settings->where('key', 'TARGET_PAD')->first()){
                $target_pad = floatval($data_settings->where('key', 'TARGET_PAD')->first()->value ?? 0);
            }

            $realisasi_terbit_pbg_sum = $big_data_resume->issuance_realization_pbg_sum;
            $realisasi_terbit_pbg_count = $big_data_resume->issuance_realization_pbg_count;
            $menunggu_klik_dpmptsp_sum = $big_data_resume->waiting_click_dpmptsp_sum;
            $menunggu_klik_dpmptsp_count = $big_data_resume->waiting_click_dpmptsp_count;
            $proses_dinas_teknis_sum = $big_data_resume->process_in_technical_office_sum;
            $proses_dinas_teknis_count = $big_data_resume->process_in_technical_office_count;
            
            // Get real-time spatial planning data using new calculation formula
            $spatialData = $this->getSpatialPlanningData();
            $tata_ruang = $spatialData['sum'];
            $tata_ruang_count = $spatialData['count'];
            
            // Get real-time PBG Task Payments data
            $pbgPaymentsData = $this->getPbgTaskPaymentsData();
            $pbg_task_payments_sum = $pbgPaymentsData['sum'];
            $pbg_task_payments_count = $pbgPaymentsData['count'];
            
            $kekurangan_potensi = $target_pad - $big_data_resume->potention_sum;

            // percentage kekurangan potensi
            $kekurangan_potensi_percentage = $target_pad > 0 && $target_pad > 0 
            ? round(($kekurangan_potensi / $target_pad) * 100, 2) : 0;

            // percentage total potensi
            $total_potensi_percentage = $big_data_resume->potention_sum > 0 && $target_pad > 0 
            ? round(($big_data_resume->potention_sum / $target_pad) * 100, 2) : 0;
            
            // // percentage verified document (verified_sum / potention_sum) - by value/amount
            // $verified_percentage = $big_data_resume->potention_sum > 0 && $big_data_resume->verified_sum >= 0
            // ? round(($big_data_resume->verified_sum / $big_data_resume->potention_sum) * 100, 2) : 0;

            // // percentage non-verified document (non_verified_sum / potention_sum) - by value/amount
            // $non_verified_percentage = $big_data_resume->potention_sum > 0 && $big_data_resume->non_verified_sum >= 0
            // ? round(($big_data_resume->non_verified_sum / $big_data_resume->potention_sum) * 100, 2) : 0;

            // Alternative: percentage by count (if needed)
            $verified_count_percentage = $big_data_resume->potention_count > 0 && $big_data_resume->verified_count > 0
                ? round(($big_data_resume->verified_count / $big_data_resume->potention_count) * 100, 2) : 0;
            $non_verified_count_percentage = $big_data_resume->potention_count > 0 && $big_data_resume->non_verified_count > 0
                ? round(($big_data_resume->non_verified_count / $big_data_resume->potention_count) * 100, 2) : 0;

            // percentage business document (business / non_verified)
            $business_percentage = $big_data_resume->non_verified_sum > 0 && $big_data_resume->business_sum >= 0
            ? round(($big_data_resume->business_sum / $big_data_resume->non_verified_sum) * 100, 2) : 0;

            // percentage non-business document (non_business / non_verified)
            $non_business_percentage = $big_data_resume->non_verified_sum > 0 && $big_data_resume->non_business_sum >= 0
            ? round(($big_data_resume->non_business_sum / $big_data_resume->non_verified_sum) * 100, 2) : 0;

            // percentage tata ruang (spatial / potention)
            $tata_ruang_percentage = $big_data_resume->potention_sum > 0 && $tata_ruang >= 0
            ? round(($tata_ruang / $big_data_resume->potention_sum) * 100, 2) : 0;

            // percentage realisasi terbit pbg (issuance / verified)
            $realisasi_terbit_percentage = $big_data_resume->verified_sum > 0 && $realisasi_terbit_pbg_sum >= 0
            ? round(($realisasi_terbit_pbg_sum / $big_data_resume->verified_sum) * 100, 2) : 0;

            // percentage menunggu klik dpmptsp (waiting / verified)
            $menunggu_klik_dpmptsp_percentage = $big_data_resume->verified_sum > 0 && $menunggu_klik_dpmptsp_sum >= 0
            ? round(($menunggu_klik_dpmptsp_sum / $big_data_resume->verified_sum) * 100, 2) : 0;

            // percentage proses_dinas_teknis (process / verified)
            $proses_dinas_teknis_percentage = $big_data_resume->verified_sum > 0 && $proses_dinas_teknis_sum >= 0
            ? round(($proses_dinas_teknis_sum / $big_data_resume->verified_sum) * 100, 2) : 0;

            // percentage pbg_task_payments (payments / verified)
            $pbg_task_payments_percentage = $realisasi_terbit_pbg_sum > 0 && $pbg_task_payments_sum >= 0
            ? round(($pbg_task_payments_sum / $realisasi_terbit_pbg_sum) * 100, 2) : 0;

            $business_rab_count = $big_data_resume->business_rab_count;
            $business_krk_count = $big_data_resume->business_krk_count;
            $non_business_rab_count = $big_data_resume->non_business_rab_count;
            $non_business_krk_count = $big_data_resume->non_business_krk_count;
            $business_dlh_count = $big_data_resume->business_dlh_count;

            $result = [
                'target_pad' => [
                    'sum' => $target_pad,
                    'percentage' => 100,
                ],
                'tata_ruang' => [
                    'sum' => $tata_ruang,
                    'count' => $tata_ruang_count,
                    'percentage' => $tata_ruang_percentage,
                ],
                'kekurangan_potensi' => [
                    'sum' => $kekurangan_potensi,
                    'percentage' => $kekurangan_potensi_percentage
                ],
                'total_potensi' => [
                    'sum' => (float) $big_data_resume->potention_sum,
                    'count' => $big_data_resume->potention_count,
                    'percentage' => $total_potensi_percentage
                ],
                'verified_document' => [
                    'sum' => (float) $big_data_resume->verified_sum,
                    'count' => $big_data_resume->verified_count,
                    'percentage' => $verified_count_percentage
                ],
                'non_verified_document' => [
                    'sum' => (float) $big_data_resume->non_verified_sum,
                    'count' => $big_data_resume->non_verified_count,
                    'percentage' => $non_verified_count_percentage
                ],
                'business_document' => [
                    'sum' => (float) $big_data_resume->business_sum,
                    'count' => $big_data_resume->business_count,
                    'percentage' => $business_percentage
                ],
                'non_business_document' => [
                    'sum' => (float) $big_data_resume->non_business_sum,
                    'count' => $big_data_resume->non_business_count,
                    'percentage' => $non_business_percentage
                ],
                'realisasi_terbit' => [
                    'sum' => $realisasi_terbit_pbg_sum,
                    'count' => $realisasi_terbit_pbg_count,
                    'percentage' => $realisasi_terbit_percentage
                ],
                'menunggu_klik_dpmptsp' => [
                    'sum' => $menunggu_klik_dpmptsp_sum,
                    'count' => $menunggu_klik_dpmptsp_count,
                    'percentage' => $menunggu_klik_dpmptsp_percentage
                ],
                'proses_dinas_teknis' => [
                    'sum' => $proses_dinas_teknis_sum,
                    'count' => $proses_dinas_teknis_count,
                    'percentage' => $proses_dinas_teknis_percentage
                ],
                'business_rab_count' => $business_rab_count,
                'business_krk_count' => $business_krk_count,
                'non_business_rab_count' => $non_business_rab_count,
                'non_business_krk_count' => $non_business_krk_count,
                'business_dlh_count' => $business_dlh_count,
                'pbg_task_payments' => [
                    'sum' => (float) $pbg_task_payments_sum,
                    'count' => $pbg_task_payments_count,
                    'percentage' => $pbg_task_payments_percentage
                ]
            ];
            return response()->json($result);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error when fetching data'], 500);
        }
    }



    public function bigdata_report(Request $request){
        try{
            $query = BigdataResume::query()->orderBy('id', 'desc');
        
            if($request->filled('search')){
                $query->where('year', 'LIKE', '%'.$request->input('search').'%');
            }

            $query = $query->paginate(config('app.paginate_per_page', 50));
            return  BigdataResumeResource::collection($query)->response()->getData(true);
        }catch(\Exception $e){
            Log::error($e->getMessage());
            return response()->json(['message' => 'Error when fetching data'], 500);
        }
    }

    public function payment_recaps(Request $request)
    {
        try {
            $query = BigdataResume::query()->orderBy('id', 'desc');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            $data = $query->paginate(50);

            // Restructure response
            $transformedData = [];

            foreach ($data as $item) {
                $createdAt = $item->created_at;
                $id = $item->id;

                foreach ($item->toArray() as $key => $value) {
                    // Only include columns with "sum" in their names
                    if (strpos($key, 'sum') !== false) {
                        $transformedData[] = [
                            'id' => $id,
                            'category' => $key,
                            'nominal' => $value,
                            'created_at' => $createdAt,
                        ];
                    }
                }
            }

            return response()->json([
                'data' => $transformedData, // Flat array
                'pagination' => [
                    'total' => count($transformedData),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Error when fetching data'], 500);
        }
    }

    public function export_excel_payment_recaps(Request $request)
    {
        $startDate = null;
        $endDate = null;

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        }

        return Excel::download(new ReportPaymentRecapExport($startDate, $endDate), 'laporan-rekap-pembayaran.xlsx');
    }

    public function export_pdf_payment_recaps(Request $request){
        $query = BigdataResume::query()->orderBy('id', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $items = $query->get();

        // Define category mapping
        $categoryMap = [
            'potention_sum' => 'Potensi',
            'non_verified_sum' => 'Belum Terverifikasi',
            'verified_sum' => 'Terverifikasi',
            'business_sum' => 'Usaha',
            'non_business_sum' => 'Non Usaha',
            'spatial_sum' => 'Tata Ruang',
            'waiting_click_dpmptsp_sum' => 'Menunggu Klik DPMPTSP',
            'issuance_realization_pbg_sum' => 'Realisasi Terbit PBG',
            'process_in_technical_office_sum' => 'Proses Di Dinas Teknis',
        ];

        // Restructure response
        $data = [];

        foreach ($items as $item) {
            $createdAt = $item->created_at;
            $id = $item->id;

            foreach ($item->toArray() as $key => $value) {
                // Only include columns with "sum" in their names
                if (strpos($key, 'sum') !== false) {
                    $data[] = [
                        'id' => $id,
                        'category' => $categoryMap[$key] ?? $key, // Map category
                        'nominal' => $value, // Format number
                        'created_at' => $createdAt->format('Y-m-d H:i:s'), // Format date
                    ];
                }
            }
        }

        $pdf = Pdf::loadView('exports.payment_recaps_report', compact('data'));
        return $pdf->download('laporan-rekap-pembayaran.pdf');
    }


    public function export_excel_report_director(){
        return Excel::download(new ReportDirectorExport, 'laporan-pimpinan.xlsx');
    }

    public function export_pdf_report_director(){
        $data = BigdataResume::select(
            'potention_count',
            'potention_sum',
            'non_verified_count',
            'non_verified_sum',
            'verified_count',
            'verified_sum',
            'business_count',
            'business_sum',
            'non_business_count',
            'non_business_sum',
            'spatial_count',
            'spatial_sum',
            'waiting_click_dpmptsp_count',
            'waiting_click_dpmptsp_sum',
            'issuance_realization_pbg_count',
            'issuance_realization_pbg_sum',
            'process_in_technical_office_count',
            'process_in_technical_office_sum',
            'year',
            'created_at'
        )->orderBy('id', 'desc')->get();
        $pdf = Pdf::loadView('exports.director_report', compact('data'))->setPaper('a4', 'landscape');
        return $pdf->download('laporan-pimpinan.pdf');
    }
    private function response_empty_resume(){
        $data_settings = DataSetting::all();
        $target_pad = 0;
        if($data_settings->where('key', 'TARGET_PAD')->first()){
            $target_pad = floatval($data_settings->where('key', 'TARGET_PAD')->first()->value ?? 0);
        }

        $result = [
            'target_pad' => [
                'sum' => $target_pad,
                'percentage' => 100,
            ],
            'tata_ruang' => [
                'sum' => 0,
                'percentage' => 0,
            ],
            'kekurangan_potensi' => [
                'sum' => 0,
                'percentage' => 0
            ],
            'total_potensi' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'verified_document' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'non_verified_document' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'business_document' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'non_business_document' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'realisasi_terbit' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'menunggu_klik_dpmptsp' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'proses_dinas_teknis' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ],
            'pbg_task_payments' => [
                'sum' => 0,
                'count' => 0,
                'percentage' => 0
            ]
        ];

        return response()->json($result);
    }

    /**
     * Get spatial planning data using new calculation formula
     */
    private function getSpatialPlanningData(): array
    {
        try {
            // Get spatial plannings that are not yet issued (is_terbit = false) and have valid data
            $spatialPlannings = SpatialPlanning::where('land_area', '>', 0)
                ->where('site_bcr', '>', 0)
                ->where('is_terbit', false)
                ->get();

            $totalSum = 0;
            $businessCount = 0;
            $nonBusinessCount = 0;

            foreach ($spatialPlannings as $spatialPlanning) {
                // Use new calculation formula: LUAS LAHAN × BCR × HARGA SATUAN
                $calculatedAmount = $spatialPlanning->calculated_retribution;
                $totalSum += $calculatedAmount;

                // Count business types
                if ($spatialPlanning->is_business_type) {
                    $businessCount++;
                } else {
                    $nonBusinessCount++;
                }
            }

            Log::info("Real-time Spatial Planning Data (is_terbit = false only)", [
                'total_records' => $spatialPlannings->count(),
                'business_count' => $businessCount,
                'non_business_count' => $nonBusinessCount,
                'total_sum' => $totalSum,
                'filtered_by' => 'is_terbit = false'
            ]);

            return [
                'count' => $spatialPlannings->count(),
                'sum' => (float) $totalSum,
                'business_count' => $businessCount,
                'non_business_count' => $nonBusinessCount,
            ];
        } catch (\Exception $e) {
            Log::error("Error getting spatial planning data", ['error' => $e->getMessage()]);
            return [
                'count' => 0,
                'sum' => 0.0,
                'business_count' => 0,
                'non_business_count' => 0,
            ];
        }
    }

    /**
     * Get PBG Task Payments data from database
     */
    private function getPbgTaskPaymentsData(): array
    {
        try {
            // Get sum and count from PbgTaskPayment model
            $stats = PbgTaskPayment::whereNotNull('payment_date_raw')
                ->whereNotNull('retribution_total_pad')
                ->whereYear('payment_date_raw', date('Y'))
                ->selectRaw('SUM(retribution_total_pad) as total_sum, COUNT(*) as total_count')
                ->first();

            $totalSum = $stats->total_sum ?? 0;
            $totalCount = $stats->total_count ?? 0;

            return [
                'sum' => (float) $totalSum,
                'count' => (int) $totalCount,
            ];
        } catch (\Exception $e) {
            Log::error("Error getting PBG task payments data", ['error' => $e->getMessage()]);
            return [
                'sum' => 0.0,
                'count' => 0,
            ];
        }
    }
}
