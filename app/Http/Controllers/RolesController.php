<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $permissions = $this->permissions[$menuId]?? []; // Avoid undefined index error
        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;

        return view("roles.index", compact('creator', 'updater', 'destroyer', 'menuId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id');
        return view("roles.create", compact('menuId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        try{
            $validate_role = $request->validated();

            DB::beginTransaction();
            Role::create($validate_role);
            DB::commit();
            return response()->json(['message' => 'Role created successfully'], 201);
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, Request $request)
    {
        $menuId = $request->query('menu_id');
        $role = Role::findOrFail($id);
        return view("roles.edit", compact('role', 'menuId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, string $id)
    {
        try{
            $validate_role = $request->validated();
            $role = Role::findOrFail($id);

            DB::beginTransaction();
            $role->update($validate_role);
            DB::commit();
            return response()->json(['message' => 'Role updated successfully'], 200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            DB::beginTransaction();
            Role::findOrFail($id)->delete();
            DB::commit();
            return response()->json(['success' => true, "message" => "Successfully deleted"]);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['success' => false, "message" => $e->getMessage()]);
        }
    }

    public function menu_permission(string $role_id, Request $request){
        try{
            $menuId = $request->query('menu_id');
            $role = Role::findOrFail($role_id);
            $menus = Menu::all();
            $role_menus = RoleMenu::where('role_id', $role_id)->get() ?? collect();
            return view('roles.role_menu', compact('role', 'menus', 'role_menus', 'menuId'));
        }catch(\Exception $e){
            return redirect()->back()->with("error", $e->getMessage());
        }
    }

    public function update_menu_permission(Request $request, string $role_id){
        try{
            $menuId = $request->query('menu_id');
            $validateData = $request->validate([
                "permissions" => "nullable|array",
                "permissions.*.allow_show" => "nullable|boolean",
                "permissions.*.allow_create" => "nullable|boolean",
                "permissions.*.allow_update" => "nullable|boolean",
                "permissions.*.allow_destroy" => "nullable|boolean"
            ]);

            $role = Role::find($role_id);

            // Jika `permissions` tidak ada atau kosong, hapus semua permissions terkait
            if (!isset($validateData['permissions']) || empty($validateData['permissions'])) {
                $role->menus()->detach();
                return redirect()->route("roles.index", ['menu_id' => $menuId])
                    ->with('success', 'All menu permissions have been removed.');
            }

            $permissionsArray = [];
            foreach ($validateData['permissions'] as $menu_id => $permission) {
                $permissionsArray[$menu_id] = [
                    "allow_show" => (int) ($permission["allow_show"] ?? 0),
                    "allow_create" => (int) ($permission["allow_create"] ?? 0),
                    "allow_update" => (int) ($permission["allow_update"] ?? 0),
                    "allow_destroy" => (int) ($permission["allow_destroy"] ?? 0),
                    "updated_at" => now(),
                ];
            }

            // Sync will update existing records and insert new ones
            $role->menus()->sync($permissionsArray);

            return redirect()->route("roles.index", ['menu_id' => $menuId])->with('success','Menu Permission updated successfully');
        }catch(\Exception $e){
            Log::error("Error updating role_menu:", ["error" => $e->getMessage()]);
            return redirect()->route("role-menu.permission", $role_id)->with("error", $e->getMessage());
        }
    }
}
