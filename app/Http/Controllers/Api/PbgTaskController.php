<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportDatasourceStatus;
use App\Enums\PbgTaskApplicationTypes;
use App\Enums\PbgTaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PbgTaskMultiStepRequest;
use App\Http\Resources\PbgTaskResource;
use App\Models\DataSetting;
use App\Models\ImportDatasource;
use App\Models\PbgTask;
use App\Models\PbgTaskGoogleSheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

class PbgTaskController extends Controller
{
    public function index(Request $request)
    {
        info($request);
        
        $isLastUpdated = filter_var($request->query('isLastUpdated', false), FILTER_VALIDATE_BOOLEAN);

        $query = PbgTask::query();

        if ($isLastUpdated) {
            $query->orderBy('updated_at', 'desc');
        } else {
            $query->where('status', 20);
        }

        // Ambil maksimal 10 data
        $pbg_task = $query->limit(10)->get();
        $totalData = $pbg_task->count();

        // Tambahkan nomor urut
        $data = $pbg_task->map(function ($item, $index) {
            return array_merge($item->toArray(), ['no' => $index + 1]);
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $totalData
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PbgTaskMultiStepRequest $request)
    {
        try {
            $data = PbgTask::create([
                "uuid" => $request->input("step1Form.uuid"),
                "name" => $request->input("step1Form.name"),
                "owner_name" => $request->input("step1Form.owner_name"),
                "application_type" => $request->input("step1Form.application_type"),
                "application_type_name" => $request->input("step1Form.application_type_name"),
                "condition" => $request->input("step1Form.condition"),
                "registration_number" => $request->input("step1Form.registration_number"),
                "document_number" => $request->input("step1Form.document_number"),
                "address" => $request->input("step1Form.address"),
                "status" => $request->input("step1Form.status"),
                "status_name" => $request->input("step1Form.status_name"),
                "slf_status" => $request->input("step1Form.slf_status"),
                "slf_status_name" => $request->input("step1Form.slf_status_name"),
                "function_type" => $request->input("step1Form.function_type"),
                "consultation_type" => $request->input("step1Form.consultation_type"),
                "due_date" => $request->input("step1Form.due_date"),
                "land_certificate_phase" => $request->input("step1Form.land_certificate_phase"),
                "task_created_at" => $request->input("step1Form.task_created_at"),
            ]);

            return response()->json([
                "success" => true,
                "message" => "Step 1 berhasil disimpan!",
                "data" => $data
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Gagal menyimpan data",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $pbg_task = PbgTask::with(['pbg_task_retributions','pbg_task_index_integrations','pbg_task_retributions.pbg_task_prasarana'])->findOrFail($id);
            return response()->json([
                "success"=> true,
                "message"=> "Data ditemukan",
                "data"=> $pbg_task
            ]);
        }catch(\Exception $e){
            return response()->json([
                "success"=> false,
                "message"=> $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $task_uuid)
    {
        try{
            $pbg_task = PbgTask::where('uuid',$task_uuid)->first();

            if(!$pbg_task){
                return response()->json([
                    "success"=> false,
                    "message"=> "Data PBG Task tidak ditemukan",
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'owner_name' => 'nullable|string|max:255',
                'application_type' => ['nullable', new Enum(PbgTaskApplicationTypes::class)],
                'condition' => 'nullable|string|max:255',
                'registration_number' => 'nullable|string|max:255',
                'document_number' => 'nullable|string|max:255',
                'status' => ['nullable', new Enum(PbgTaskStatus::class)],
                'address' => 'nullable|string|max:255',
                'slf_status_name' => 'nullable|string|max:255',
                'function_type' => 'nullable|string|max:255',
                'consultation_type' => 'nullable|string|max:255',
                'due_date' => 'nullable|date',
                'is_valid' => 'nullable|boolean',
            ]);

            $statusLabel = $validated['status'] !== null ? PbgTaskStatus::getLabel($validated['status']) : null;
            $applicationLabel = $validated['application_type'] !== null ? PbgTaskApplicationTypes::getLabel($validated['application_type']) : null;

            // Prepare update data - only include fields that are actually provided
            $updateData = [];
            
            foreach ($validated as $key => $value) {
                if ($value !== null || $request->has($key)) {
                    $updateData[$key] = $value;
                }
            }
            
            // Handle special cases for labels
            if (isset($updateData['status'])) {
                $updateData['status_name'] = $statusLabel;
            }
            
            if (isset($updateData['application_type'])) {
                $updateData['application_type_name'] = $applicationLabel;
            }
            
            // Handle is_valid specifically
            if ($request->has('is_valid')) {
                $updateData['is_valid'] = $validated['is_valid'];
            }

            $pbg_task->update($updateData);
            return response()->json([
                "success"=> true,
                "message"=> "Data berhasil diubah",
                "data"=> $pbg_task
            ]);
        }catch(\Exception $e){
            return response()->json([
                "success"=> false,
                "message"=> $e->getMessage(),
            ]);
         }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    protected function validatePbgTask(Request $request){
        return $request->validate([
            "uuid" => $request->input("step1Form.uuid"),
        ]);
    }

}
