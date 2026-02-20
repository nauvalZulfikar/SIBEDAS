<?php

namespace App\Exports;

use App\Models\BigdataResume;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportDirectorExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return BigdataResume::select(
            'potention_count',
            'potention_sum',
            'non_verified_count',
            'non_verified_sum',
            'verified_count',
            'verified_sum',
            'business_count',
            'business_sum',
            'non_business_count',
            'non_business_sum',
            'spatial_count',
            'spatial_sum',
            'waiting_click_dpmptsp_count',
            'waiting_click_dpmptsp_sum',
            'issuance_realization_pbg_count',
            'issuance_realization_pbg_sum',
            'process_in_technical_office_count',
            'process_in_technical_office_sum',
            'year',
            'created_at'
        )->orderBy('id', 'desc')->get();
    }
    public function headings(): array{
        return [
            "Jumlah Potensi" ,
            "Total Potensi" ,
            "Jumlah Berkas Belum Terverifikasi" ,
            "Total Berkas Belum Terverifikasi" ,
            "Jumlah Berkas Terverifikasi" ,
            "Total Berkas Terverifikasi" ,
            "Jumlah Usaha" ,
            "Total Usaha" ,
            "Jumlah Non Usaha" ,
            "Total Non Usaha" ,
            "Jumlah Tata Ruang" ,
            "Total Tata Ruang" ,
            "Jumlah Menunggu Klik DPMPTSP" ,
            "Total Menunggu Klik DPMPTSP" ,
            "Jumlah Realisasi Terbit PBG" ,
            "Total Realisasi Terbit PBG" ,
            "Jumlah Proses Dinas Teknis" ,
            "Total Proses Dinas Teknis", 
            "Tahun",
            "Created"
        ];
    }

    public function map($row): array
    {
        return [
            $row->potention_count,
            $row->potention_sum,
            $row->non_verified_count,
            $row->non_verified_sum,
            $row->verified_count,
            $row->verified_sum,
            $row->business_count,
            $row->business_sum,
            $row->non_business_count,
            $row->non_business_sum,
            $row->spatial_count,
            $row->spatial_sum,
            $row->waiting_click_dpmptsp_count,
            $row->waiting_click_dpmptsp_sum,
            $row->issuance_realization_pbg_count,
            $row->issuance_realization_pbg_sum,
            $row->process_in_technical_office_count,
            $row->process_in_technical_office_sum,
            $row->year,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : null, // Format created_at as Y-m-d
        ];
    }
}
