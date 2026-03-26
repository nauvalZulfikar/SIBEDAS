<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskAssignmentsResource;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;

class TaskAssignmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $uuid)
    {
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
