<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReportPbgPtspExport;
use App\Http\Controllers\Controller;
use App\Models\PbgTask;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportPbgPtspController extends Controller
{
    public function export_excel(){
        return Excel::download(new ReportPbgPtspExport, 'laporan-ptsp.xlsx');
    }
    public function export_pdf(){
        $data = PbgTask::select(
                    'status',
                    'status_name', // Keeping this column
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('status', 'status_name')
                ->get();
        $pdf = Pdf::loadView('exports.ptsp_report', compact('data'));
        return $pdf->download('laporan-ptsp.pdf');
    }
}
