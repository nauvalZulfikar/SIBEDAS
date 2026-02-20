<?php

namespace App\Imports;

use App\Models\BusinessOrIndustry;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Log;

class BusinessIndustriesImport implements ToCollection, WithMultipleSheets, WithChunkReading, WithBatchInserts, ShouldQueue, WithHeadingRow
{
    /**
    * @param Collection $collection
    */    
    public function collection(Collection $collection)
    {
        try{
            $batchData = [];
            $batchSize = 1000;
    
            foreach ($collection as $row){
                if(!isset($row['nop']) || empty($row['nop'])){
                    continue;
                }
    
    
                $clean_nop = preg_replace('/[^A-Za-z0-9]/', '', $row['nop']);
    
                $batchData[] = [
                        'nama_kecamatan'     => $row['nama_kecamatan'],
                        'nama_kelurahan'     => $row['nama_kelurahan'],
                        'nop'                => $clean_nop,
                        'nama_wajib_pajak'   => $row['nama_wajib_pajak'],
                        'alamat_wajib_pajak' => $row['alamat_wajib_pajak'], 
                        'alamat_objek_pajak' => $row['alamat_objek_pajak'], 
                        'luas_bumi'          => $row['luas_bumi'], 
                        'luas_bangunan'      => $row['luas_bangunan'], 
                        'njop_bumi'          => $row['njop_bumi'], 
                        'njop_bangunan'      => $row['njop_bangunan'], 
                        'ketetapan'          => $row['ketetapan'], 
                        'tahun_pajak'        => $row['tahun_pajak'], 
                ];
    
                if(count($batchData) >= $batchSize){
                    BusinessOrIndustry::upsert($batchData, ['nop'], [
                        'nama_kecamatan',
                        'nama_kelurahan',
                        'nama_wajib_pajak',
                        'alamat_wajib_pajak',
                        'alamat_objek_pajak',
                        'luas_bumi',
                        'luas_bangunan',
                        'njop_bumi',
                        'njop_bangunan',
                        'ketetapan',
                        'tahun_pajak',
                    ]);
                    $batchData = [];
                }
            }
            if(!empty($batchData)){
                BusinessOrIndustry::upsert($batchData, ['nop'], [
                    'nama_kecamatan',
                    'nama_kelurahan',
                    'nama_wajib_pajak',
                    'alamat_wajib_pajak',
                    'alamat_objek_pajak',
                    'luas_bumi',
                    'luas_bangunan',
                    'njop_bumi',
                    'njop_bangunan',
                    'ketetapan',
                    'tahun_pajak',
                ]);
            }
        }catch(\Exception $exception){
            Log::error('Error while importing Business Industries data:', ['error' => $exception->getMessage()]);
            return;
        }
    }

    public function sheets(): array {
        return [
            0 => $this
        ];
    }

    public function headingRow(): int
    {
        return 1;
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
