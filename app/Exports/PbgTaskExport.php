<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\PbgTask;
use App\Enums\PbgTaskStatus;
use App\Traits\PbgTaskFilterTrait;
use Illuminate\Support\Facades\DB;

class PbgTaskExport implements FromCollection, WithHeadings
{
    use PbgTaskFilterTrait;
    protected $filter;
    protected $year;
    protected $search;
    protected $colFilters;

    public function __construct(string $filter = 'all', int $year = 0, string $search = '', array $colFilters = [])
    {
        $this->filter = $filter;
        $this->year = $year;
        $this->search = $search;
        $this->colFilters = $colFilters;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = PbgTask::query()->where('is_valid', true);

        $isSemua = ($this->year === 0);
        $year = $isSemua ? (int) date('Y') : $this->year;

        $this->applyPbgFilter($query, $this->filter, $year, $isSemua);

        if (!empty($this->search)) {
            $this->applySearch($query, $this->search);
        }

        if (!empty($this->colFilters)) {
            $this->applyColumnFilters($query, $this->colFilters);
        }

        return $query->with(['pbg_task_retributions', 'pbg_task_detail', 'pbg_status'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $retribusi = $item->pbg_task_retributions?->nilai_retribusi_bangunan;
                return [
                    $item->id,
                    $item->name ?: '-',
                    $item->owner_name ?: '-',
                    $item->condition ?: '-',
                    $item->registration_number ?: '-',
                    $item->document_number ?: '-',
                    $item->address ?: '-',
                    $item->status_name ?: '-',
                    $item->function_type ?: '-',
                    $item->pbg_task_detail?->name_building ?: '-',
                    $item->consultation_type ?: '-',
                    $item->task_created_at ? substr($item->task_created_at, 0, 10) : '-',
                    $item->start_date ? substr($item->start_date, 0, 10) : '-',
                    $item->due_date ? substr($item->due_date, 0, 10) : '-',
                    $item->pbg_task_detail?->total_area ? number_format($item->pbg_task_detail->total_area, 0, ',', '.') : '-',
                    $item->pbg_task_detail?->unit ?: '-',
                    $retribusi ? number_format($retribusi, 0, ',', '.') : '-',
                    $item->usulan_retribusi ? number_format($item->usulan_retribusi, 0, ',', '.') : '0',
                    $item->pbg_status?->note ?: '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Pemohon',
            'Nama Pemilik',
            'Kondisi',
            'Nomor Registrasi',
            'Nomor Dokumen',
            'Alamat',
            'Status',
            'Jenis Fungsi',
            'Nama Bangunan',
            'Jenis Konsultasi',
            'Tanggal Dibuat',
            'Tanggal Mulai',
            'Tanggal Jatuh Tempo',
            'Luas (m²)',
            'Unit',
            'Retribusi',
            'Usulan Retribusi',
            'Catatan',
        ];
    }

    private function applySearch($query, string $search)
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
              ->orWhere('registration_number', 'LIKE', "%$search%")
              ->orWhere('owner_name', 'LIKE', "%$search%")
              ->orWhere('address', 'LIKE', "%$search%");
        });

        $namesBuildingUuids = DB::table('pbg_task_details')
            ->where('name_building', 'LIKE', "%$search%")
            ->pluck('pbg_task_uid')
            ->toArray();

        if (!empty($namesBuildingUuids)) {
            $query->orWhereIn('uuid', $namesBuildingUuids);
        }
    }

    private function applyColumnFilters($query, array $filters)
    {
        $directColumns = [
            'id', 'name', 'owner_name', 'condition', 'registration_number',
            'document_number', 'address', 'status_name', 'function_type',
            'consultation_type', 'task_created_at', 'start_date', 'due_date',
            'usulan_retribusi',
        ];
        $detailColumns = ['total_area', 'unit'];

        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            if (in_array($key, $directColumns)) {
                $query->where($key, 'LIKE', "%{$value}%");
            } elseif (in_array($key, $detailColumns)) {
                $query->whereHas('pbg_task_detail', function ($q) use ($key, $value) {
                    $q->where($key, 'LIKE', "%{$value}%");
                });
            } elseif ($key === '_name_building') {
                $query->whereHas('pbg_task_detail', function ($q) use ($value) {
                    $q->where('name_building', 'LIKE', "%{$value}%");
                });
            } elseif ($key === '_retribusi') {
                $query->whereHas('pbg_task_retributions', function ($q) use ($value) {
                    $q->where('nilai_retribusi_bangunan', 'LIKE', "%{$value}%");
                });
            } elseif ($key === '_catatan') {
                $query->whereHas('pbg_status', function ($q) use ($value) {
                    $q->where('note', 'LIKE', "%{$value}%");
                });
            }
        }
    }
}
