<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Customer;
use App\Models\SpatialPlanning;
use Illuminate\Http\Request;
use App\Models\TourismBasedKBLI;
use App\Models\Tax;
use Illuminate\Support\Facades\Log;

class LackOfPotentialController extends Controller
{

    public function count_lack_of_potential(){
        try{
            $total_reklame = Advertisement::count();
            $total_pdam = Customer::count();
            $total_tata_ruang = SpatialPlanning::count();
            $total_tata_ruang_usaha = SpatialPlanning::where('building_function','like', '%usaha%')->count();
            $total_tata_ruang_non_usaha = SpatialPlanning::where('building_function','not like', '%usaha%')->count();
            $data_report_tourism = TourismBasedKBLI::all();
            $data_pajak_reklame = Tax::where('tax_code','Reklame')->distinct('business_name')->count();
            $data_pajak_restoran = Tax::where('tax_code','Restoran')->distinct('business_name')->count();
            $data_pajak_hiburan = Tax::where('tax_code','Hiburan')->distinct('business_name')->count();
            $data_pajak_hotel = Tax::where('tax_code','Hotel')->distinct('business_name')->count();
            $data_pajak_parkir = Tax::where('tax_code','Parkir')->distinct('business_name')->count();

            return response()->json([
                'total_reklame' => $total_reklame,
                'total_pdam' => $total_pdam,
                'total_tata_ruang' => $total_tata_ruang,
                'total_tata_ruang_usaha' => $total_tata_ruang_usaha,
                'total_tata_ruang_non_usaha' => $total_tata_ruang_non_usaha,
                'data_report' => $data_report_tourism,
                'data_pajak_reklame' => $data_pajak_reklame,
                'data_pajak_restoran' => $data_pajak_restoran,
                'data_pajak_hiburan' => $data_pajak_hiburan,
                'data_pajak_hotel' => $data_pajak_hotel,
                'data_pajak_parkir' => $data_pajak_parkir,
                'tata_ruang' => $this->getSpatialPlanningData()
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error: '.$e->getMessage()
            ], 500);
        }
    }

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
            $businessSum = 0;
            $nonBusinessSum = 0;

            foreach ($spatialPlannings as $spatialPlanning) {
                // Use new calculation formula: LUAS LAHAN × BCR × HARGA SATUAN
                $calculatedAmount = $spatialPlanning->calculated_retribution;
                $totalSum += $calculatedAmount;

                // Count business types
                if ($spatialPlanning->is_business_type) {
                    $businessCount++;
                    $businessSum += $calculatedAmount;
                } else {
                    $nonBusinessCount++;
                    $nonBusinessSum += $calculatedAmount;
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
                'business_sum' => (float) $businessSum,
                'non_business_sum' => (float) $nonBusinessSum,
            ];
        } catch (\Exception $e) {
            Log::error("Error getting spatial planning data", ['error' => $e->getMessage()]);
            return [
                'count' => 0,
                'sum' => 0.0,
                'business_count' => 0,
                'non_business_count' => 0,
                'business_sum' => 0.0,
                'non_business_sum' => 0.0,
            ];
        }
    }
}
