<?php

namespace App\Exports;

use App\Models\Tax;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TaxationsExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $sheets = [];

        // Ambil semua subdistrict unik
        $subdistricts = Tax::select('subdistrict')->distinct()->pluck('subdistrict');

        foreach ($subdistricts as $subdistrict) {
            $sheets[] = new TaxSubdistrictSheetExport($subdistrict);
        }

        return $sheets;
    }
}
