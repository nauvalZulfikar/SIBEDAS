<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Tax;

class TaxationsImport implements ToCollection, WithMultipleSheets, WithChunkReading, WithBatchInserts, ShouldQueue, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $batchData = [];
        $batchSize = 1000;

        foreach ($collection as $row) {

            $masaPajak = trim($row['masa_pajak']) ?? '';

            $masaParts = explode('-', $masaPajak);
            
            $startValidity = null;
            $endValidity = null;
            
            if (count($masaParts) === 2) {
                $startValidity = \Carbon\Carbon::createFromFormat('d/m/Y', trim($masaParts[0]))->format('Y-m-d');
                $endValidity = \Carbon\Carbon::createFromFormat('d/m/Y', trim($masaParts[1]))->format('Y-m-d');
            }

            $batchData[] = [
                'tax_code' => trim($row['kode']) ?? '',
                'tax_no' => trim($row['no']) ?? '',
                'npwpd' => trim($row['npwpd']) ?? '',
                'wp_name' => trim($row['nama_wp']) ?? '',
                'business_name' => trim($row['nama_usaha']) ?? '',
                'address' => trim($row['alamat_usaha']) ?? '',
                'start_validity' => $startValidity,
                'end_validity' => $endValidity,
                'tax_value' => (float) str_replace(',', '', trim($row['nilai_pajak']) ?? '0'),
                'subdistrict' => trim($row['kecamatan']) ?? '',
                'village' => trim($row['desa']) ?? '',
            ];

            if (count($batchData) >= $batchSize) {
                Tax::upsert($batchData, ['tax_no'], ['tax_code', 'tax_no', 'npwpd', 'wp_name', 'business_name', 'address', 'start_validity', 'end_validity', 'tax_value', 'subdistrict', 'village']);
                $batchData = [];
            }
        }

        if (!empty($batchData)) {
            Tax::upsert($batchData, ['tax_no'], ['tax_code', 'tax_no', 'npwpd', 'wp_name', 'business_name', 'address', 'start_validity', 'end_validity', 'tax_value', 'subdistrict', 'village']);
        }
        
    }
    public function sheets(): array {
        return [
            0 => $this
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
