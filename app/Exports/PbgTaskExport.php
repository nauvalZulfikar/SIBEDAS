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

    public function __construct(string $category, int $year = 0)
    {
        $this->category = $category;
        $this->year = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = PbgTask::query();

        if ($this->year > 0) {
            $query->whereYear('task_created_at', $this->year);
        }

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
            'id',
            'registration_number',
            'document_number',
            'name as pemohon',
            'owner_name',
            'address',
            'status_name',
            'function_type',
            'consultation_type',
            'condition',
            'start_date',
            'due_date',
            'task_created_at',
            'created_at',
            'total_area',
            'unit',
        ])->with(['pbg_task_retributions'])->get()->map(function ($item) {
            return [
                $item->id,
                $item->registration_number,
                $item->document_number,
                $item->pemohon,
                $item->owner_name,
                $item->address,
                $item->status_name,
                $item->function_type,
                $item->consultation_type,
                $item->condition,
                $item->start_date,
                $item->due_date,
                $item->task_created_at,
                $item->created_at,
                $item->total_area,
                $item->unit,
                $item->pbg_task_retributions ? $item->pbg_task_retributions->nilai_retribusi_bangunan : null,
            ];
        });
    }

    public function headings(): array{
        return [
            'ID',
            'Nomor Registrasi',
            'Nomor Dokumen',
            'Nama Pemohon',
            'Nama Pemilik',
            'Alamat',
            'Status',
            'Fungsi Bangunan',
            'Jenis Konsultasi',
            'Kondisi',
            'Tanggal Mulai',
            'Tanggal Jatuh Tempo',
            'Tanggal SIMBG',
            'Tanggal Input',
            'Luas (m2)',
            'Unit',
            'Retribusi',
        ];
    }
}
