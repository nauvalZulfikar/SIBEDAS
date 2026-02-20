<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReportTourismExport;
use App\Http\Controllers\Controller;
use App\Models\TourismBasedKBLI;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportTourismsController extends Controller
{
    public function export_excel(){
        return Excel::download(new ReportTourismExport, 'laporan-pariwisata.xlsx');
    }
    public function export_pdf(){
        $data = TourismBasedKBLI::all();
        $pdf = Pdf::loadView('exports.tourisms_report', compact('data'));
        return $pdf->download('laporan-pariwisata.pdf');
    }
}
