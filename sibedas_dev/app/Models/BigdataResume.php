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
        $verified_count = PbgTask::whereIn('status', PbgTaskStatus::getVerified())
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->count();
        $non_verified_count = PbgTask::whereIn('status', PbgTaskStatus::getNonVerified())
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->count();
        $waiting_click_dpmptsp_count = PbgTask::whereIn('status', PbgTaskStatus::getWaitingClickDpmptsp())
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->count();
        $issuance_realization_pbg_count = PbgTask::whereIn('status', PbgTaskStatus::getIssuanceRealizationPbg())
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->count();
        $process_in_technical_office_count = PbgTask::whereIn('status', PbgTaskStatus::getProcessInTechnicalOffice())
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->count();
        $potention_count = PbgTask::whereIn('status', PbgTaskStatus::getPotention())
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->count();

        // Business count: function_type LIKE usaha OR (non-business with unit > 1)
        $business_count = PbgTask::where(function ($q) {
            $q->where(function ($q2) {
                // Traditional business: function_type LIKE usaha
                $q2->where(function ($q3) {
                    $q3->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                    ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                })
                // OR non-business with unit > 1 (becomes business)
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
        ->whereYear('task_created_at', $year)
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
            // Additional condition: unit IS NULL OR unit <= 1
            ->where(function ($q3) {
                $q3->whereDoesntHave('pbg_task_detail', function ($q4) {
                    $q4->where('unit', '>', 1);
                })
                ->orWhereDoesntHave('pbg_task_detail');
            });
        })
        ->where('is_valid', true)
        ->whereYear('task_created_at', $year)
        ->count();

        // Business RAB count - for each business task with data_type=3:
        // if any status != 1 then return 1, if all status = 1 then return 0, then sum all
        $business_rab_count = DB::table('pbg_task')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                    ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                })
                ->whereIn("status", PbgTaskStatus::getNonVerified());
            })
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 3);
            })
            ->selectRaw('
                SUM(
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM pbg_task_detail_data_lists ptddl 
                            WHERE ptddl.pbg_task_uuid = pbg_task.uuid 
                            AND ptddl.data_type = 3 
                            AND ptddl.status != 1
                        ) THEN 1 
                        ELSE 0 
                    END
                ) as total_count
            ')
            ->value('total_count') ?? 0;

        // Business KRK count - for each business task with data_type=2:
        // if any status != 1 then return 1, if all status = 1 then return 0, then sum all
        $business_krk_count = DB::table('pbg_task')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                    ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                })
                ->whereIn("status", PbgTaskStatus::getNonVerified());
            })
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 2);
            })
            ->selectRaw('
                SUM(
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM pbg_task_detail_data_lists ptddl 
                            WHERE ptddl.pbg_task_uuid = pbg_task.uuid 
                            AND ptddl.data_type = 2 
                            AND ptddl.status != 1
                        ) THEN 1 
                        ELSE 0 
                    END
                ) as total_count
            ')
            ->value('total_count') ?? 0;

        // Business DLH count - for each business task with data_type=5:
        // if any status != 1 then return 1, if all status = 1 then return 0, then sum all
        $business_dlh_count = DB::table('pbg_task')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%fungsi usaha%'])
                    ->orWhereRaw("LOWER(TRIM(function_type)) LIKE ?", ['%sebagai tempat usaha%']);
                })
                ->whereIn("status", PbgTaskStatus::getNonVerified());
            })
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 5);
            })
            ->selectRaw('
                SUM(
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM pbg_task_detail_data_lists ptddl 
                            WHERE ptddl.pbg_task_uuid = pbg_task.uuid 
                            AND ptddl.data_type = 5 
                            AND ptddl.status != 1
                        ) THEN 1 
                        ELSE 0 
                    END
                ) as total_count
            ')
            ->value('total_count') ?? 0;

        // Non-Business RAB count - for each non-business task with data_type=3:
        // if any status != 1 then return 1, if all status = 1 then return 0, then sum all
        $non_business_rab_count = DB::table('pbg_task')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                        ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                    })
                    ->orWhereNull('function_type');
                })
                ->whereIn("status", PbgTaskStatus::getNonVerified());
            })
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 3);
            })
            ->selectRaw('
                SUM(
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM pbg_task_detail_data_lists ptddl 
                            WHERE ptddl.pbg_task_uuid = pbg_task.uuid 
                            AND ptddl.data_type = 3 
                            AND ptddl.status != 1
                        ) THEN 1 
                        ELSE 0 
                    END
                ) as total_count
            ')
            ->value('total_count') ?? 0;

        // Non-Business KRK count - for each non-business task with data_type=2:
        // if any status != 1 then return 1, if all status = 1 then return 0, then sum all
        $non_business_krk_count = DB::table('pbg_task')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%fungsi usaha%'])
                        ->whereRaw("LOWER(TRIM(function_type)) NOT LIKE ?", ['%sebagai tempat usaha%']);
                    })
                    ->orWhereNull('function_type');
                })
                ->whereIn("status", PbgTaskStatus::getNonVerified());
            })
            ->where('is_valid', true)
            ->whereYear('task_created_at', $year)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('pbg_task_detail_data_lists')
                      ->whereColumn('pbg_task_detail_data_lists.pbg_task_uuid', 'pbg_task.uuid')
                      ->where('pbg_task_detail_data_lists.data_type', 2);
            })
            ->selectRaw('
                SUM(
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM pbg_task_detail_data_lists ptddl 
                            WHERE ptddl.pbg_task_uuid = pbg_task.uuid 
                            AND ptddl.data_type = 2 
                            AND ptddl.status != 1
                        ) THEN 1 
                        ELSE 0 
                    END
                ) as total_count
            ')
            ->value('total_count') ?? 0;

        // Calculate totals using count-based formula
        // Business: $business_count * 200 * 44300
        // Non-Business: $non_business_count * 72 * 16000
        $business_total = $business_count * 200 * 44300;
        $non_business_total = $non_business_count * 72 * 16000;
        $non_verified_total = $business_total + $non_business_total;

        // Get other sum values using proper aggregation to handle multiple retributions
        $stats = PbgTask::leftJoin('pbg_task_retributions as ptr', 'pbg_task.uuid', '=', 'ptr.pbg_task_uid')
            ->where('pbg_task.is_valid', true)
            ->whereYear('pbg_task.task_created_at', $year)
            ->selectRaw("
                SUM(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getVerified()).") THEN COALESCE(ptr.nilai_retribusi_bangunan, 0) ELSE 0 END) AS verified_total,
                SUM(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getWaitingClickDpmptsp()).") THEN COALESCE(ptr.nilai_retribusi_bangunan, 0) ELSE 0 END) AS waiting_click_dpmptsp_total,
                SUM(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getIssuanceRealizationPbg()).") THEN COALESCE(ptr.nilai_retribusi_bangunan, 0) ELSE 0 END) AS issuance_realization_pbg_total,
                SUM(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getProcessInTechnicalOffice()).") THEN COALESCE(ptr.nilai_retribusi_bangunan, 0) ELSE 0 END) AS process_in_technical_office_total,
                SUM(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getPotention()).") THEN COALESCE(ptr.nilai_retribusi_bangunan, 0) ELSE 0 END) AS potention_total,
                COUNT(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getNonVerified()).") THEN 1 END) AS non_verified_tasks_count,
                COUNT(CASE WHEN pbg_task.status in (".implode(',', PbgTaskStatus::getNonVerified()).") AND ptr.nilai_retribusi_bangunan IS NOT NULL THEN 1 END) AS non_verified_with_retribution_count
            ")
            ->first();

        $service_google_sheet = app(ServiceGoogleSheet::class);

        return self::create([
            'import_datasource_id' => $import_datasource_id,
            'spatial_count' => $service_google_sheet->getSpatialPlanningWithCalculationCount() ?? 0,
            'spatial_sum' => $service_google_sheet->getSpatialPlanningCalculationSum() ?? 0.00,
            'potention_count' => $potention_count,
            'potention_sum' => ($stats->potention_total ?? 0),
            'non_verified_count' => $non_verified_count,
            'non_verified_sum' => $non_verified_total,
            'verified_count' => $verified_count,
            'verified_sum' => $stats->verified_total ?? 0.00,
            'business_count' => $business_count,
            'business_sum' => $business_total,
            'non_business_count' => $non_business_count,
            'non_business_sum' => $non_business_total,
            'year' => $year,
            'waiting_click_dpmptsp_count' => $waiting_click_dpmptsp_count,
            'waiting_click_dpmptsp_sum' => $stats->waiting_click_dpmptsp_total ?? 0.00,
            'issuance_realization_pbg_count' => $issuance_realization_pbg_count,
            'issuance_realization_pbg_sum' => $stats->issuance_realization_pbg_total ?? 0.00,
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
