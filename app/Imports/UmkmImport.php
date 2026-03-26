<?php

namespace App\Imports;

use App\Models\Umkm;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;

class UmkmImport implements ToCollection
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

        info($rows);

        foreach ($rows->skip(1) as $row) {
            // Normalisasi nama kecamatan dan desa
            $districtName = strtolower(trim(str_replace('Kecamatan', '', $row[14])));
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
            
            // ambil village code yang village_name sama dengan $villageName
            $villageCode = $listTrueVillage[$villageName]['village_code'] ?? '0000';

            $dataToInsert[] = [
                'business_name' => $row[0],
                'business_address' => $row[1],
                'business_desc' => $row[2],
                'business_contact' => $row[3],
                'business_id_number' => $row[4],
                'business_scale_id' => $row[5],
                'owner_id' => $row[6],
                'owner_name' => $row[7],
                'owner_address' => $row[8],
                'owner_contact' => $row[9],
                'business_type' => $row[10],
                'business_form_id' => $row[11],
                'revenue' => $row[12],
                'village_code' => $villageCode,
                'district_code' => $districtCode,
                'number_of_employee' => $row[15],
                'land_area' => $row[16],
                'permit_status_id' => $row[17],
            ];
        }

        info($dataToInsert);
        if (!empty($dataToInsert)) {
            Umkm::insert($dataToInsert);
        } else {
            return;
        }
    }
}