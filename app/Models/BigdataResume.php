<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Enums\PbgTaskStatus;
use App\Services\ServiceGoogleSheet;

class BigdataResume extends Model
{
    protected $table = "bigdata_resumes";

    protected $fillable = [
        'import_datasource_id',
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
        'year',
        'waiting_click_dpmptsp_count',
        'waiting_click_dpmptsp_sum',
        'issuance_realization_pbg_count',
        'issuance_realization_pbg_sum',
        'process_in_technical_office_count',
        'process_in_technical_office_sum',
        'business_rab_count',
        'business_krk_count',
        'business_dlh_count',
        'non_business_rab_count',
        'non_business_krk_count',
        'resume_type',
    ];

    public function importDatasource()
    {
        return $this->belongsTo(ImportDatasource::class, 'import_datasource_id');
    }

    public static function generateResumeData($import_datasource_id, $year, $resume_type){
        // Get accurate counts without joins to avoid duplicates from multiple retributions
        // Filter only valid data (is_valid = true) and only the target year
        // Berkas Lengkap: split tahun (prev = semua 7 status, current = potention minus belum lengkap)
        $berkas_lengkap_statuses = array_values(array_diff(PbgTaskStatus::getPotention(), PbgTaskStatus::getNonVerified()));
        $verified_count_prev = PbgTask::whereIn('status', PbgTaskStatus::getPotentionPreviousYear())
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
            ->count();
        $verified_count_current = PbgTask::whereIn('status', $berkas_lengkap_statuses)
            ->where('is_valid', true)
            ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
            ->count();
        $verified_count = $verified_count_prev + $verified_count_current;
        // Belum Lengkap: split tahun (2025 tidak ada status 1,2 di prev, jadi hanya 2026)
        $non_verified_count = PbgTask::whereIn('status', PbgTaskStatus::getNonVerified())
            ->where('is_valid', true)
            ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
            ->count();
        $waiting_click_dpmptsp_count = PbgTask::whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->count();
        $issuance_realization_pbg_count = PbgTask::where('is_valid', true)
            ->where(function ($q) use ($year) {
                // 1. SK Terbit: data tahun sebelumnya yang document_number tanggalnya di tahun ini
                $q->where(function ($q2) use ($year) {
                    $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                        ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
                        ->whereNotNull('document_number')
                        ->whereRaw("CAST(RIGHT(REGEXP_SUBSTR(document_number, '[0-9]{8}'), 4) AS UNSIGNED) = ?", [$year]);
                })
                // 2. Pengambilan SK PBG tahun ini
                ->orWhere(function ($q2) use ($year) {
                    $q2->where('status', PbgTaskStatus::PENERBITAN_SK_PBG->value)
                        ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                })
                // 3. SK Terbit: data tahun ini
                ->orWhere(function ($q2) use ($year) {
                    $q2->where('status', PbgTaskStatus::SK_PBG_TERBIT->value)
                        ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31']);
                });
            })
            ->count();
        $process_in_technical_office_count = PbgTask::whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->count();
        // Potensi tahun sebelumnya: hanya 7 status terbatas
        $potention_count_prev = PbgTask::whereIn('status', PbgTaskStatus::getPotentionPreviousYear())
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
            ->count();
        // Potensi tahun berjalan: semua status potensi
        $potention_count_current = PbgTask::whereIn('status', PbgTaskStatus::getPotention())
            ->where('is_valid', true)
            ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
            ->count();
        $potention_count = $potention_count_prev + $potention_count_current;

        // Business count: function_type LIKE usaha OR (non-business with unit > 1)
        $business_count = PbgTask::where(function ($q) {
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
        })
        ->where('is_valid', true)
        ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
        ->count();

        // Non-business count: function_type NOT LIKE usaha AND (unit IS NULL OR unit <= 1)
        $non_business_count = PbgTask::where(function ($q) {
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
        })
        ->where('is_valid', true)
        ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
        ->count();

        // Helper: business filter scope (function_type LIKE usaha OR non-business with unit > 1)
        $applyBusinessFilter = function ($q) {
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
        };

        // Helper: non-business filter scope
        $applyNonBusinessFilter = function ($q) {
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
        };

        // Business RAB count (data_type=3, status != 1)
        $business_rab_count = PbgTask::where(function ($q) use ($applyBusinessFilter) {
                $applyBusinessFilter($q);
            })
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 3)
                      ->where('pbg_task_detail_data_lists.status', '!=', 1);
            })
            ->count();

        // Business KRK count (data_type=2, status != 1)
        $business_krk_count = PbgTask::where(function ($q) use ($applyBusinessFilter) {
                $applyBusinessFilter($q);
            })
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 2)
                      ->where('pbg_task_detail_data_lists.status', '!=', 1);
            })
            ->count();

        // Business DLH count (data_type=5, status != 1)
        $business_dlh_count = PbgTask::where(function ($q) use ($applyBusinessFilter) {
                $applyBusinessFilter($q);
            })
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 5)
                      ->where('pbg_task_detail_data_lists.status', '!=', 1);
            })
            ->count();

        // Non-Business RAB count (data_type=3, status != 1)
        $non_business_rab_count = PbgTask::where(function ($q) use ($applyNonBusinessFilter) {
                $applyNonBusinessFilter($q);
            })
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 3)
                      ->where('pbg_task_detail_data_lists.status', '!=', 1);
            })
            ->count();

        // Non-Business KRK count (data_type=2, status != 1)
        $non_business_krk_count = PbgTask::where(function ($q) use ($applyNonBusinessFilter) {
                $applyNonBusinessFilter($q);
            })
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 2)
                      ->where('pbg_task_detail_data_lists.status', '!=', 1);
            })
            ->count();

        // Business/Non-business sum using usulan_retribusi (same filter as count, year only)
        $business_sum = PbgTask::where(function ($q) {
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
        })
        ->where('is_valid', true)
        ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
        ->sum('usulan_retribusi');

        $non_business_sum = PbgTask::where(function ($q) {
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
        })
        ->where('is_valid', true)
        ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
        ->sum('usulan_retribusi');

        // Get sum values using usulan_retribusi (no join needed)
        $stats = PbgTask::where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->selectRaw("
                SUM(CASE WHEN status in (".implode(',', PbgTaskStatus::getVerified()).") THEN COALESCE(usulan_retribusi, 0) ELSE 0 END) AS verified_total,
                SUM(CASE WHEN status in (".implode(',', PbgTaskStatus::getWaitingClickDpmptsp()).") THEN COALESCE(usulan_retribusi, 0) ELSE 0 END) AS waiting_click_dpmptsp_total,
                SUM(CASE WHEN status in (".implode(',', PbgTaskStatus::getProcessInTechnicalOffice()).") THEN COALESCE(usulan_retribusi, 0) ELSE 0 END) AS process_in_technical_office_total
            ")
            ->first();

        // Realisasi Terbit PBG: pakai nilai_retribusi_bangunan (nilai resmi, bukan estimasi)
        $issuance_realization_pbg_total = PbgTask::leftJoin('pbg_task_retributions as ptr', 'pbg_task.uuid', '=', 'ptr.pbg_task_uid')
            ->where('pbg_task.is_valid', true)
            ->whereBetween('pbg_task.start_date', [($year - 1) . '-01-01', $year . '-12-31'])
            ->selectRaw("
                SUM(CASE WHEN (pbg_task.status = ".PbgTaskStatus::SK_PBG_TERBIT->value." AND YEAR(pbg_task.start_date) = ".($year-1)." AND pbg_task.document_number IS NOT NULL AND CAST(RIGHT(REGEXP_SUBSTR(pbg_task.document_number, '[0-9]{8}'), 4) AS UNSIGNED) = $year) OR (pbg_task.status = ".PbgTaskStatus::PENERBITAN_SK_PBG->value." AND YEAR(pbg_task.start_date) = $year) OR (pbg_task.status = ".PbgTaskStatus::SK_PBG_TERBIT->value." AND YEAR(pbg_task.start_date) = $year) THEN COALESCE(ptr.nilai_retribusi_bangunan, 0) ELSE 0 END) AS total
            ")
            ->value('total');

        // Potention sum menggunakan kolom usulan_retribusi (tanpa join retributions)
        // Tahun sebelumnya: hanya 7 status terbatas
        $potention_sum_prev = PbgTask::whereIn('status', PbgTaskStatus::getPotentionPreviousYear())
            ->where('is_valid', true)
            ->whereBetween('start_date', [($year - 1) . '-01-01', ($year - 1) . '-12-31'])
            ->sum('usulan_retribusi');
        // Tahun berjalan: semua status potensi
        $potention_sum_current = PbgTask::whereIn('status', PbgTaskStatus::getPotention())
            ->where('is_valid', true)
            ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
            ->sum('usulan_retribusi');
        $potention_total = $potention_sum_prev + $potention_sum_current;

        // Belum Lengkap sum (status 1,2 hanya tahun berjalan, pakai usulan_retribusi)
        $non_verified_sum = PbgTask::whereIn('status', PbgTaskStatus::getNonVerified())
            ->where('is_valid', true)
            ->whereBetween('start_date', [$year . '-01-01', $year . '-12-31'])
            ->sum('usulan_retribusi');

        // Berkas Lengkap sum = potention_total - non_verified_sum
        $verified_sum = $potention_total - $non_verified_sum;

        $service_google_sheet = app(ServiceGoogleSheet::class);

        return self::create([
            'import_datasource_id' => $import_datasource_id,
            'spatial_count' => $service_google_sheet->getSpatialPlanningWithCalculationCount() ?? 0,
            'spatial_sum' => $service_google_sheet->getSpatialPlanningCalculationSum() ?? 0.00,
            'potention_count' => $potention_count,
            'potention_sum' => $potention_total,
            'non_verified_count' => $non_verified_count,
            'non_verified_sum' => $non_verified_sum,
            'verified_count' => $verified_count,
            'verified_sum' => $verified_sum,
            'business_count' => $business_count,
            'business_sum' => $business_sum,
            'non_business_count' => $non_business_count,
            'non_business_sum' => $non_business_sum,
            'year' => $year,
            'waiting_click_dpmptsp_count' => $waiting_click_dpmptsp_count,
            'waiting_click_dpmptsp_sum' => $stats->waiting_click_dpmptsp_total ?? 0.00,
            'issuance_realization_pbg_count' => $issuance_realization_pbg_count,
            'issuance_realization_pbg_sum' => $issuance_realization_pbg_total ?? 0.00,
            'process_in_technical_office_count' => $process_in_technical_office_count,
            'process_in_technical_office_sum' => $stats->process_in_technical_office_total ?? 0.00,
            'business_rab_count' => $business_rab_count,
            'business_krk_count' => $business_krk_count,
            'business_dlh_count' => $business_dlh_count,
            'non_business_rab_count' => $non_business_rab_count,
            'non_business_krk_count' => $non_business_krk_count,
            'resume_type' => $resume_type,
        ]);
    }
}
