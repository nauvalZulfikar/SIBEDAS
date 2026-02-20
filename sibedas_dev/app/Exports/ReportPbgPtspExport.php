<?php

namespace App\Exports;

use App\Models\PbgTask;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportPbgPtspExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PbgTask::select(
                    'status_name',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('status', 'status_name')
                ->get();
    }

    public function headings(): array
    {
        return [
            'Status Name',
            'Total'
        ];
    }
}
