<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\PbgTask;
use App\Enums\PbgTaskStatus;
use Illuminate\Support\Facades\DB;

class PbgTaskExport implements FromCollection, WithHeadings
{
    protected $filter;
    protected $year;

    public function __construct(string $filter = 'all', int $year = 0)
    {
        $this->filter = $filter;
        $this->year = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = PbgTask::query()->where('is_valid', true);

        if ($this->year > 0) {
            $query->whereYear('start_date', $this->year);
        }

        $this->applyFilter($query);

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

    private function applyFilter($query)
    {
        switch ($this->filter) {
            case 'all':
                break;

            case 'non-business':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                            ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhereNull('function_type');
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified())
                    ->where(function ($q3) {
                        $q3->whereDoesntHave('pbg_task_detail', function ($q4) {
                            $q4->where('unit', '>', 1);
                        })
                        ->orWhereDoesntHave('pbg_task_detail');
                    });
                });
                break;

            case 'business':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereHas('pbg_task_detail', function ($q4) {
                                $q4->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                });
                break;

            case 'verified':
                $query->whereIn("status", PbgTaskStatus::getVerified());
                break;

            case 'non-verified':
                $query->whereIn("status", PbgTaskStatus::getNonVerified());
                break;

            case 'potention':
                $query->whereIn("status", PbgTaskStatus::getPotention());
                break;

            case 'issuance-realization-pbg':
                if ($this->year > 0 && $this->year < (int) date('Y')) {
                    $query->whereIn("status", PbgTaskStatus::getIssuanceRealizationPbgPrev());
                } else {
                    $query->whereIn("status", PbgTaskStatus::getIssuanceRealizationPbg());
                }
                break;

            case 'process-in-technical-office':
                $query->whereIn("status", PbgTaskStatus::getProcessInTechnicalOffice());
                break;

            case 'waiting-click-dpmptsp':
                $query->whereIn("status", PbgTaskStatus::getWaitingClickDpmptsp());
                break;

            case 'non-business-rab':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                            ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhereNull('function_type');
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified())
                    ->whereNotExists(function ($subq) {
                        $subq->select(DB::raw(1))
                              ->from('pbg_task_details')
                              ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                              ->where('unit', '>', 1);
                    });
                })
                ->whereExists(function ($subq) {
                    $subq->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('data_type', 3)
                          ->where('status', '!=', 1);
                });
                break;

            case 'non-business-krk':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                            ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhereNull('function_type');
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified())
                    ->whereNotExists(function ($subq) {
                        $subq->select(DB::raw(1))
                              ->from('pbg_task_details')
                              ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                              ->where('unit', '>', 1);
                    });
                })
                ->whereExists(function ($subq) {
                    $subq->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('data_type', 2)
                          ->where('status', '!=', 1);
                });
                break;

            case 'business-rab':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereExists(function ($subq) {
                                $subq->select(DB::raw(1))
                                      ->from('pbg_task_details')
                                      ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                                      ->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($subq) {
                    $subq->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('data_type', 3)
                          ->where('status', '!=', 1);
                });
                break;

            case 'business-krk':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereExists(function ($subq) {
                                $subq->select(DB::raw(1))
                                      ->from('pbg_task_details')
                                      ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                                      ->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($subq) {
                    $subq->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('data_type', 2)
                          ->where('status', '!=', 1);
                });
                break;

            case 'business-dlh':
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                            ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                        })
                        ->orWhere(function ($q3) {
                            $q3->where(function ($q4) {
                                $q4->where(function ($q5) {
                                    $q5->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                                    ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                                })
                                ->orWhereNull('function_type');
                            })
                            ->whereExists(function ($subq) {
                                $subq->select(DB::raw(1))
                                      ->from('pbg_task_details')
                                      ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                                      ->where('unit', '>', 1);
                            });
                        });
                    })
                    ->whereIn("status", PbgTaskStatus::getNonVerified());
                })
                ->whereExists(function ($subq) {
                    $subq->select(DB::raw(1))
                          ->from('pbg_task_detail_data_lists')
                          ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                          ->where('data_type', 5)
                          ->where('status', '!=', 1);
                });
                break;
        }
    }
}
