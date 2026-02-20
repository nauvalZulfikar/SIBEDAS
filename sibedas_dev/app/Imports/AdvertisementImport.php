<?php

namespace App\Imports;

use App\Models\Advertisement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;

class AdvertisementImport implements ToCollection
{
    /**
     * Process each row in the file.
     */
    public function collection(Collection $rows)
    {
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

        $dataToInsert = [];

        foreach ($rows->skip(1) as $row) {
            // Normalisasi nama kecamatan dan desa
            $districtName = strtolower(trim(str_replace('Kecamatan ', '', $row[8])));
            $villageName = strtolower(trim($row[7]));

            // Cari district_code dari tabel districts
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
                'no' => $row[0],
                'business_name' => $row[1],
                'npwpd' => $row[2],
                'advertisement_type' => $row[3],
                'advertisement_content' => $row[4],
                'business_address' => $row[5],
                'advertisement_location' => $row[6],
                'village_code' => $villageCode,
                'district_code' => $districtCode,
                'length' => $row[9],
                'width' => $row[10],
                'viewing_angle' => $row[11],
                'face' => $row[12],
                'area' => $row[13],
                'angle' => $row[14],
                'contact' => $row[15] ?? "-",
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        // Bulk insert untuk efisiensi
        if (!empty($dataToInsert)) {
            Advertisement::insert($dataToInsert);
        } else {
            return;
        }
    }
}

