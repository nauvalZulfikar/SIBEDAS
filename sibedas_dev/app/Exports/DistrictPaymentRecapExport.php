<?php

namespace App\Exports;

use App\Models\PbgTaskGoogleSheet;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DistrictPaymentRecapExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PbgTaskGoogleSheet::select(
                    'kecamatan',
                    DB::raw('SUM(nilai_retribusi_keseluruhan_simbg) as total')
                )
                ->groupBy('kecamatan')->get();
    }

    public function headings(): array{
        return [
            'Kecamatan',
            'Total'
        ];
    }
    
}
