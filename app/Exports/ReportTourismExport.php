<?php

namespace App\Exports;

use App\Models\TourismBasedKBLI;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportTourismExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return TourismBasedKBLI::select('kbli_title', 'total_records')->get();
    }

    public function headings(): array{
        return [
            'Jenis Bisnis Pariwisata',
            'Jumlah Total'
        ];
    }
}
