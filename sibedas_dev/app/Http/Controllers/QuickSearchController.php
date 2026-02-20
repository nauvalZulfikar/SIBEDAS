<?php

namespace App\Http\Controllers;

use App\Enums\PbgTaskApplicationTypes;
use App\Enums\PbgTaskStatus;
use App\Http\Resources\TaskAssignmentsResource;
use App\Models\PbgTask;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuickSearchController extends Controller
{
    public function index(){
        return view("quick-search.index");
    }

    public function public_search(){
        return view("public-search.index");
    }

    public function search_result(Request $request){
        $keyword = $request->get("keyword");

        return view('quick-search.result', compact('keyword'));
    }

    public function quick_search_datatable(Request $request)
    {
        try {
            // Gunakan subquery untuk performa yang lebih baik dan menghindari duplikasi
            $query = PbgTask::select([
                'pbg_task.*',
                DB::raw('(SELECT name_building FROM pbg_task_details WHERE pbg_task_details.pbg_task_uid = pbg_task.uuid LIMIT 1) as name_building'),
                DB::raw('(SELECT nilai_retribusi_bangunan FROM pbg_task_retributions WHERE pbg_task_retributions.pbg_task_uid = pbg_task.uuid LIMIT 1) as nilai_retribusi_bangunan'),
                DB::raw('(SELECT note FROM pbg_statuses WHERE pbg_statuses.pbg_task_uuid = pbg_task.uuid LIMIT 1) as note')
            ])
            ->orderBy('pbg_task.id', 'desc');

            if ($request->filled('search')) {
                $search = trim($request->get('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('pbg_task.registration_number', 'LIKE', "%$search%")
                      ->orWhere('pbg_task.name', 'LIKE', "%$search%")
                      ->orWhere('pbg_task.owner_name', 'LIKE', "%$search%")
                      ->orWhere('pbg_task.address', 'LIKE', "%$search%")
                      ->orWhereExists(function ($subQuery) use ($search) {
                          $subQuery->select(DB::raw(1))
                                   ->from('pbg_task_details')
                                   ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                                   ->where('pbg_task_details.name_building', 'LIKE', "%$search%");
                      });
                });
            }

            return response()->json($query->paginate());
        } catch (\Throwable $e) {
            Log::error("Error fetching datatable data: " . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function public_search_datatable(Request $request)
    {
        try {
            // Hanya proses jika ada keyword search
            if (!$request->filled('search') || trim($request->get('search')) === '') {
                return response()->json([
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'from' => null,
                    'to' => null
                ]);
            }

            $search = trim($request->get('search'));
            
            // Validasi minimal 3 karakter
            if (strlen($search) < 3) {
                return response()->json([
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'from' => null,
                    'to' => null,
                    'message' => 'Minimal 3 karakter untuk pencarian'
                ]);
            }

            // Gunakan subquery untuk performa yang lebih baik dan menghindari duplikasi
            $query = PbgTask::select([
                'pbg_task.*',
                DB::raw('(SELECT name_building FROM pbg_task_details WHERE pbg_task_details.pbg_task_uid = pbg_task.uuid LIMIT 1) as name_building'),
                DB::raw('(SELECT nilai_retribusi_bangunan FROM pbg_task_retributions WHERE pbg_task_retributions.pbg_task_uid = pbg_task.uuid LIMIT 1) as nilai_retribusi_bangunan'),
                DB::raw('(SELECT note FROM pbg_statuses WHERE pbg_statuses.pbg_task_uuid = pbg_task.uuid LIMIT 1) as note')
            ])
            ->where(function ($q) use ($search) {
                $q->where('pbg_task.registration_number', 'LIKE', "%$search%")
                  ->orWhere('pbg_task.name', 'LIKE', "%$search%")
                  ->orWhere('pbg_task.owner_name', 'LIKE', "%$search%")
                  ->orWhere('pbg_task.address', 'LIKE', "%$search%")
                  ->orWhereExists(function ($subQuery) use ($search) {
                      $subQuery->select(DB::raw(1))
                               ->from('pbg_task_details')
                               ->whereColumn('pbg_task_details.pbg_task_uid', 'pbg_task.uuid')
                               ->where('pbg_task_details.name_building', 'LIKE', "%$search%");
                  });
            })
            ->orderBy('pbg_task.id', 'desc');

            $result = $query->paginate();
            
            // Tambahkan message jika tidak ada hasil
            if ($result->total() === 0) {
                $result = $result->toArray();
                $result['message'] = 'Tidak ada data yang ditemukan';
            }

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error("Error fetching datatable data: " . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = PbgTask::with([
                'pbg_task_retributions',
                'pbg_task_index_integrations',
                'pbg_task_retributions.pbg_task_prasarana',
                'pbg_status'
            ])->findOrFail($id);

            $statusOptions = PbgTaskStatus::getStatuses();
            $applicationTypes = PbgTaskApplicationTypes::labels();

            return view("quick-search.detail", compact("data", 'statusOptions', 'applicationTypes'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("PbgTask with ID {$id} not found.");
            return redirect()->route('quick-search.index')->with('error', 'Data tidak ditemukan.');
        } catch (\Throwable $e) {
            Log::error("Error in QuickSearchController@show: " . $e->getMessage());
            return response()->view('pages.404', [], 500); // Optional: create `resources/views/errors/500.blade.php`
        }
    }

    public function task_assignments(Request $request, $uuid){
        try{
            $query = TaskAssignment::query()
            ->where('pbg_task_uid', $uuid)
                ->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $query->where('name', 'like', "%{$request->get('search')}%")
                ->orWhere('email', 'like', "%{$request->get('search')}%");
            }

            return TaskAssignmentsResource::collection($query->paginate(config('app.paginate_per_page', 50)));
        }catch(\Exception $exception){
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
