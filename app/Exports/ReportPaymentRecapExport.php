<?php

namespace App\Exports;

use App\Models\BigdataResume;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportPaymentRecapExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $startDate;
    protected $endDate;
    public function __construct($startDate, $endDate){
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    public function collection()
    {
        $query = BigdataResume::query()->orderBy('id', 'desc');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        $items = $query->get();

        $categoryMap = [
            'potention_sum' => 'Potensi',
            'non_verified_sum' => 'Belum Terverifikasi',
            'verified_sum' => 'Terverifikasi',
            'business_sum' => 'Usaha',
            'non_business_sum' => 'Non Usaha',
            'spatial_sum' => 'Tata Ruang',
            'waiting_click_dpmptsp_sum' => 'Berproses di DPMPTSP',
            'issuance_realization_pbg_sum' => 'Realisasi SK PBG Terbit',
            'process_in_technical_office_sum' => 'Proses Di Dinas Teknis',
        ];

        // Restructure response
        $data = [];

        foreach ($items as $item) {
            $createdAt = $item->created_at;
            $id = $item->id;

            foreach ($item->toArray() as $key => $value) {
                // Only include columns with "sum" in their names
                if (strpos($key, 'sum') !== false) {
                    $data[] = [
                        'category' => $categoryMap[$key] ?? $key, // Map category
                        'nominal' => number_format($value, 0, ',', '.'), // Format number
                        'created_at' => $createdAt->format('Y-m-d H:i:s'), // Format date
                    ];
                }
            }
        }

        return collect($data);
    }

    public function headings(): array{
        return [
            'Kategori',
            'Nominal',
            'Created'
        ];
    }
}
