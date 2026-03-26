<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Log;

class CustomersImport implements ToCollection, WithMultipleSheets, WithChunkReading, WithBatchInserts, ShouldQueue, WithHeadingRow
{
    /**
    * @param Collection $collection
    */    
    public function collection(Collection $collection)
    {
        $batchData = [];
        $batchSize = 1000;

        foreach ($collection as $row) {
            if (!isset($row['nomor_pelanggan']) || empty($row['nomor_pelanggan'])) {
                continue;
            }

            $latitude = '0';
            $longitude = '0';

            if (isset($row['latkor']) && !empty(trim($row['latkor']))) {
                $latitude = str_replace(',', '.', trim($row['latkor']));
                if (is_numeric($latitude)) {
                    $latitude = bcadd($latitude, '0', 18); 
                } else {
                    $latitude = '0'; 
                }
            } else {
                $latitude = '0';
            }

            if (isset($row['lonkor']) && !empty(trim($row['lonkor']))) {
                $longitude = str_replace(',', '.', trim($row['lonkor'])); 
                if (is_numeric($longitude)) {
                    $longitude = bcadd($longitude, '0', 18); 
                } else {
                    $longitude = '0';
                }
            } else {
                $longitude = '0';
            }

            $batchData[] = [
                'nomor_pelanggan' => $row['nomor_pelanggan'] ?? '',
                'kota_pelayanan' => $row['kota_pelayanan'] ?? '',
                'nama' => $row['nama'] ?? '',
                'alamat' => $row['alamat'] ?? '',
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            if (count($batchData) >= $batchSize) {
                Customer::upsert($batchData, ['nomor_pelanggan'], ['kota_pelayanan', 'nama', 'alamat', 'latitude', 'longitude']);
                $batchData = [];
            }
        }

        if (!empty($batchData)) {
            Customer::upsert($batchData, ['nomor_pelanggan'], ['kota_pelayanan', 'nama', 'alamat', 'latitude', 'longitude']);
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
