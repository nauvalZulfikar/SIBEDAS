<?php

namespace App\Http\Controllers\Api;

use App\Enums\PbgTaskStatus;
use App\Http\Controllers\Controller;
use App\Models\PbgTask;
use App\Traits\GlobalApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use GlobalApiResponse;

    public function businnessDocument(Request $request)
    {
        $request->validate([
            "year" => "required|integer"
        ]);

        $year = $request->get('year');

        // Business: function_type LIKE usaha OR (non-business with unit > 1), status non-verified
        $query = PbgTask::where(function ($q) {
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
            ->whereYear('start_date', $year);

        $taskCount = $query->count();
        $taskTotal = (clone $query)
            ->leftJoin('pbg_task_retributions as ptr', 'pbg_task.uuid', '=', 'ptr.pbg_task_uid')
            ->sum('ptr.nilai_retribusi_bangunan') ?? 0;

        return $this->resSuccess([
            "count" => $taskCount,
            "total" => $taskTotal
        ]);
    }
    public function nonBusinnessDocument(Request $request){
        $request->validate([
            "year" => "required|integer"
        ]);

        $year = $request->get('year');

        // Non-business: function_type NOT LIKE usaha AND (unit IS NULL OR unit <= 1), status non-verified
        $query = PbgTask::where(function ($q) {
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
            ->whereYear('start_date', $year);

        $taskCount = $query->count();
        $taskTotal = (clone $query)
            ->leftJoin('pbg_task_retributions as ptr', 'pbg_task.uuid', '=', 'ptr.pbg_task_uid')
            ->sum('ptr.nilai_retribusi_bangunan') ?? 0;

        return $this->resSuccess([
            "count" => $taskCount,
            "total" => $taskTotal
        ]);
    }
    public function allTaskDocuments(Request $request){
        $request->validate([
            "year" => "required|integer"
        ]);

        $current_year = $request->get('year');

        $startOfYear = "$current_year-01-01 00:00:00";
        $endOfYear = "$current_year-12-31 23:59:59";
        $query = once( function () use ($startOfYear, $endOfYear) {
            return DB::table('pbg_task as pt')
            ->leftJoin('pbg_task_retributions as ptr', 'pt.uuid', '=', 'ptr.pbg_task_uid')
            ->whereBetween("pt.start_date", [$startOfYear, $endOfYear])
            ->select(
                DB::raw('COUNT(DISTINCT pt.id) as task_count'),
                DB::raw('SUM(ptr.nilai_retribusi_bangunan) as total_retribution')
            )
            ->first();
        });
        $taskCount = $query->task_count ?? 0;
        $taskTotal = $query->total_retribution ?? 0;
        return $this->resSuccess([
            "count" => $taskCount,
            "total" => $taskTotal
        ]);
    }

    public function verificationDocuments(Request $request){
        $request->validate([
            "year" => "required|integer"
        ]);

        $current_year = $request->get('year');

        $startOfYear = "$current_year-01-01 00:00:00";
        $endOfYear = "$current_year-12-31 23:59:59";
        $query = once( function () use ($startOfYear, $endOfYear){
            return DB::table('pbg_task AS pt')
                ->leftJoin('pbg_task_google_sheet AS ptgs', 'pt.registration_number', '=', 'ptgs.no_registrasi')
                ->leftJoin('pbg_task_retributions AS ptr', 'pt.uuid', '=', 'ptr.pbg_task_uid')
                ->whereBetween("pt.start_date", [$startOfYear, $endOfYear])
                ->whereRaw('LOWER(TRIM(ptgs.status_verifikasi)) = ?', [strtolower(trim('Selesai Verifikasi'))])
                ->selectRaw('COUNT(pt.id) AS total_data, 
                            SUM(ptr.nilai_retribusi_bangunan) AS total_retribution') 
                ->first();
        }); 

        $taskCount = $query->total_data ?? 0;
        $taskTotal = $query->total_retribution ?? 0;

        return $this->resSuccess([
            "count"=> $taskCount,
            "total"=> $taskTotal
        ]);
    }

    public function nonVerificationDocuments(Request $request){
        $request->validate([
            "year" => "required|integer"
        ]);

        $current_year = $request->get('year');

        $startOfYear = "$current_year-01-01 00:00:00";
        $endOfYear = "$current_year-12-31 23:59:59";

        $query = once(function () use ($startOfYear, $endOfYear) {
            return DB::table('pbg_task AS pt')
            ->leftJoin('pbg_task_google_sheet AS ptgs', 'pt.registration_number', '=', 'ptgs.no_registrasi')
            ->leftJoin('pbg_task_retributions AS ptr', 'pt.uuid', '=', 'ptr.pbg_task_uid') // Join tabel pbg_task_retributions
            ->whereBetween("pt.start_date", [$startOfYear, $endOfYear])
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(ptgs.status_verifikasi)) != ?', [strtolower(trim('Selesai Verifikasi'))])
                    ->orWhereNull('ptgs.status_verifikasi'); // Include NULL values
            })
            ->selectRaw('COUNT(pt.id) AS total_data, 
                        SUM(ptr.nilai_retribusi_bangunan) AS total_retribution') // Menambahkan SUM dari pbg_task_retributions
            ->first();
        });

        $taskCount = $query->total_data ?? 0;
        $taskTotal = $query->total_retribution ?? 0;

        return $this->resSuccess([
            "count"=> $taskCount,
            "total"=> $taskTotal
        ]);
    }

    public function pbgTaskDocuments(Request $request){
        $request->validate([
            'status' => 'required|string'
        ]);
        
        $businessData = DB::table('pbg_task')
        ->leftJoin('pbg_task_retributions', 'pbg_task.uuid', '=', 'pbg_task_retributions.pbg_task_uid')
        ->select(
            DB::raw('COUNT(DISTINCT pbg_task.id) as task_count'),
            DB::raw('SUM(pbg_task_retributions.nilai_retribusi_bangunan) as total_retribution')
        )
        ->where(function ($query) use ($request) {
            $query->where("pbg_task.status", "=", $request->get('status'));
        })
        ->first();
        $taskCount = $businessData->task_count;
        $taskTotal = $businessData->total_retribution;
        $result = [
            "count" => $taskCount,
            "series" => [$taskCount],
            "total" => $taskTotal
        ];
        return $this->resSuccess($result);
    }
}
