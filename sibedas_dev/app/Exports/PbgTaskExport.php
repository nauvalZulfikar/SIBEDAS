<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\PbgTask;
use App\Enums\PbgTaskFilterData;

class PbgTaskExport implements FromCollection, WithHeadings
{
    protected $category;
    protected $year;

    public function __construct(string $category, int $year)
    {
        $this->category = $category;
        $this->year = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = PbgTask::query()
            ->whereYear('task_created_at', $this->year);

        // Menggunakan switch case karena lebih readable dan maintainable
        // untuk multiple conditions yang berbeda
        switch ($this->category) {
            case PbgTaskFilterData::all->value:
                // Tidak ada filter tambahan, ambil semua data
                break;
                
            case PbgTaskFilterData::business->value:
                $query->where('application_type', 'business');
                break;
                
            case PbgTaskFilterData::non_business->value:
                $query->where('application_type', 'non-business');
                break;
                
            case PbgTaskFilterData::verified->value:
                $query->where('is_valid', true);
                break;
                
            case PbgTaskFilterData::non_verified->value:
                $query->where('is_valid', false);
                break;
                
            case PbgTaskFilterData::potention->value:
                $query->where('status', 'potention');
                break;
                
            case PbgTaskFilterData::issuance_realization_pbg->value:
                $query->where('status', 'issuance-realization-pbg');
                break;
                
            case PbgTaskFilterData::process_in_technical_office->value:
                $query->where('status', 'process-in-technical-office');
                break;
                
            case PbgTaskFilterData::waiting_click_dpmptsp->value:
                $query->where('status', 'waiting-click-dpmptsp');
                break;
                
            case PbgTaskFilterData::non_business_rab->value:
                $query->where('application_type', 'non-business')
                      ->where('consultation_type', 'rab');
                break;
                
            case PbgTaskFilterData::non_business_krk->value:
                $query->where('application_type', 'non-business')
                      ->where('consultation_type', 'krk');
                break;
                
            case PbgTaskFilterData::business_rab->value:
                $query->where('application_type', 'business')
                      ->where('consultation_type', 'rab');
                break;
                
            case PbgTaskFilterData::business_krk->value:
                $query->where('application_type', 'business')
                      ->where('consultation_type', 'krk');
                break;
                
            case PbgTaskFilterData::business_dlh->value:
                $query->where('application_type', 'business')
                      ->where('consultation_type', 'dlh');
                break;
                
            default:
                // Jika category tidak dikenali, return empty collection
                return collect();
        }

        return $query->select([
            'registration_number',
            'document_number', 
            'owner_name',
            'address',
            'name as building_name',
            'function_type'
        ])->get();
    }

    public function headings(): array{
        return [
            'Nomor Registrasi',
            'Nomor Dokumen',
            'Nama Pemilik',
            'Alamat Pemilik',
            'Nama Bangunan',
            'Fungsi Bangunan',
        ];
    }
}
