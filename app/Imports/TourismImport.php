<?php

namespace App\Imports;

use App\Models\Tourism;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use DateTime;
use Carbon\Carbon;

class TourismImport implements ToCollection
{
    protected static $processed = false;
    /**
     * Process each row in the file.
     */
    public function collection(Collection $rows)
    {
        if (self::$processed) {
            return;
        }
        self::$processed = true;

        if ($rows->isEmpty())
        {
            return;
        }

        // Ambil data districts dengan normalisasi nama
        $districts = DB::table('districts')
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower(trim($item->district_name)) => $item->district_code];
            })
            ->toArray();
        // Cari header secara otomatis
        $header = $rows->first();
        $headerIndex = collect($header)->search(fn($value) => !empty($value));

        // Pastikan header ditemukan
        if ($headerIndex === false) {
            return;
        }
        
        foreach ($rows->skip(1) as $row) {
            // Normalisasi nama kecamatan dan desa
            $districtName = strtolower(trim(str_replace('Kecamatan', '', $row[12])));
            $villageName = strtolower(trim($row[13]));

            // Cari distric_code dari table districts
            $districtCode = $districts[$districtName] ?? null;

            $listTrueVillage = DB::table('villages')
                ->where('district_code', $districtCode)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [strtolower(trim($item->village_name)) => [
                        'village_code' => $item->village_code,
                        'district_code' => $item->district_code
                    ]];
                })
                ->toArray();
            
            // ambill village code yang village_name sama dengan $villageName
            $villageCode = $listTrueVillage[$villageName]['village_code'] ?? '000000';

            $excelSerialDate = $row[16];
            if (is_numeric($excelSerialDate)) {
                $projectSubmissionDate = Carbon::createFromFormat('Y-m-d', '1899-12-30')
                    ->addDays($excelSerialDate)
                    ->format('Y-m-d H:i:s');
            } else {
                $projectSubmissionDate = Carbon::createFromFormat('m/d/Y', $excelSerialDate)
                    ->format('Y-m-d H:i:s');
            }

            $dataToInsert[] = [
                'project_id' => $row[1],
                'project_type_id' => $row[2],
                'nib' => $row[3],
                'business_name' => $row[4],
                'oss_publication_date' => DateTime::createFromFormat('d/m/Y', $row[5]),
                'investment_status_description' => $row[6],
                'business_form' => $row[7],
                'project_risk' => $row[8],
                'project_name' => $row[9],
                'business_scale' => $row[10],
                'business_address' => $row[12],
                'district_code' => $districtCode,
                'village_code' => $villageCode,
                'longitude' => $row[14],
                'latitude' => (string) $row[15],
                'project_submission_date' => $projectSubmissionDate,
                'kbli'=> $row[17],
                'kbli_title'=>$row[18],
                'supervisory_sector'=>$row[19],
                'user_name'=>$row[20],
                'email'=>$row[21],
                'contact'=>$row[22],
                'land_area_in_m2'=>$row[23],
                'investment_amount'=>$row[24],
                'tki'=>$row[25]
            ];
        }

        if(!empty($dataToInsert)) {
            Tourism::insert($dataToInsert);
        } else {
            return;
        }
    }
}