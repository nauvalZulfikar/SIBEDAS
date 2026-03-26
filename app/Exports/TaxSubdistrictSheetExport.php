<?php

namespace App\Exports;

use App\Models\Tax;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaxSubdistrictSheetExport implements FromCollection, WithTitle, WithHeadings
{
    protected $subdistrict;

    public function __construct(string $subdistrict)
    {
        $this->subdistrict = $subdistrict;
    }

    public function collection()
    {
        return Tax::where('subdistrict', $this->subdistrict)
            ->select(
                'tax_code',
                'tax_no',
                'npwpd',
                'wp_name',
                'business_name',
                'address',
                'start_validity',
                'end_validity',
                'tax_value',
                'subdistrict',
                'village'
            )->get();
    }

    public function headings(): array
    {
        return [
            'Kode',
            'No',
            'NPWPD',
            'Nama WP',
            'Nama Usaha',
            'Alamat Usaha',
            'Tanggal Mulai Berlaku',
            'Tanggal Berakhir Berlaku',
            'Nilai Pajak',
            'Kecamatan',
            'Desa'
        ];
    }

    public function title(): string
    {
        return mb_substr($this->subdistrict, 0, 31);
    }
}

