<?php

namespace App\Http\Controllers\RequestAssignment;

use App\Enums\PbgTaskApplicationTypes;
use App\Enums\PbgTaskFilterData;
use App\Http\Controllers\Controller;
use App\Models\PbgTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Enums\PbgTaskStatus;

class PbgTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $filter = $request->query('filter');

        $permissions = $this->permissions[$menuId]?? []; // Avoid undefined index error
        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;
        
        return view('pbg_task.index', [
            'creator' => $creator,
            'updater' => $updater,
            'destroyer' => $destroyer,
            'filter' => $filter,
            'filterOptions' => PbgTaskFilterData::getAllOptions(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("pbg_task.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = PbgTask::with([
            'pbg_task_retributions',
            'pbg_task_index_integrations', 
            'pbg_task_retributions.pbg_task_prasarana', 
            'pbg_task_detail',
            'pbg_status',
            'dataLists' => function($query) {
                $query->orderBy('data_type')->orderBy('name');
            }
        ])->findOrFail($id);
        
        // Group data lists by data_type for easier display
        $dataListsByType = $data->dataLists->groupBy('data_type');
        
        $statusOptions = PbgTaskStatus::getStatuses();
        $applicationTypes = PbgTaskApplicationTypes::labels();
        
        return view("pbg_task.show", compact("data", 'statusOptions', 'applicationTypes', 'dataListsByType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view("pbg_task.edit");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
