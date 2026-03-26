<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Role::query()->orderBy('id', 'desc');

        if($request->has('search') && !empty($request->get('search'))){
            $query = $query->where('name', 'like', '%'. $request->get('search') . '%');
        }

        return response()->json($query->paginate(config('app.paginate_per_page', 50)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        try{
            $role = Role::create($request->validated());
            return response()->json(['message' => 'Successfully created', 'data' => $role]);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error when creating role', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $role = Role::find($id);
            if($role){
                return response()->json(['data' => $role]);
            } else {
                return response()->json(['message' => 'Role not found'], 404);
            }
        }catch(\Exception $e){
            return response()->json(['message' => 'Error when getting role', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, string $id)
    {
        try{
            $role = Role::find($id);
            if($role){
                $role->update($request->validated());
                return response()->json(['message' => 'Successfully updated', 'data' => $role]);
            } else {
                return response()->json(['message' => 'Role not found'], 404);
            }
        }catch(\Exception $e){
            return response()->json(['message' => 'Error when updating role', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $role = Role::find($id);
            if($role){
                $role->delete();
                return response()->json(['message' => 'Successfully deleted']);
            } else {
                return response()->json(['message' => 'Role not found'], 404);
            }
        }catch(\Exception $e){
            return response()->json(['message' => 'Error when deleting role', 'error' => $e->getMessage()], 500);
        }
    }
}
