<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $menuId = (int) $request->query('menu_id', 0);
        $permissions = $this->permissions[$menuId] ?? []; // Avoid undefined index error

        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;

        return view('menus.index', compact('creator', 'updater', 'destroyer', 'menuId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $menuId = $request->query('menu_id'); // Get menu_id from request
        $menu = Menu::with('children')->find($menuId); // Find the menu

        // Get IDs of all child menus to exclude
        $excludedIds = $menu ? $this->getChildMenuIds($menu) : [$menuId];

        // Fetch only menus that have children and are not in the excluded list
        $parent_menus = Menu::whereHas('children')
            ->whereNotIn('id', $excludedIds)
            ->get();

        return view("menus.create", compact('parent_menus', 'menuId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MenuRequest $request)
    {
        try{
            $validated_menu = $request->validated();
            DB::beginTransaction();
            Menu::create($validated_menu);
            DB::commit();
            return response()->json(['message' => 'Successfully created'], 200);
        }catch(\Exception $e){
            DB::rollBack();
            \Log::error('Menu creation failed: ' . $e->getMessage()); // Log the error for debugging
            return response()->json(['message'=> $e->getMessage()],500);
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
        $menu = Menu::with('children')->find($id);
        $excludedIds = $menu ? $this->getChildMenuIds($menu) : [$id]; 

        $parent_menus = Menu::whereHas('children')
            ->whereNotIn('id', $excludedIds)
            ->get();
        return view("menus.edit", compact('menu','parent_menus', 'menuId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MenuRequest $request, string $id)
    {
        try{
            $validate_menu = $request->validated();
            $menu = Menu::findOrFail($id);
            DB::beginTransaction();
            $menu->update($validate_menu);
            DB::commit();
            return response()->json(['message' => 'Successfully updated'], 200);
        }catch(\Exception $e){
            DB::rollBack();
            \Log::error('Menu update failed: '. $e->getMessage()); // Log the error for debugging
            return response()->json(['message' => $e->getMessage()],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            DB::beginTransaction();
                $menu = Menu::findOrFail($id);
                $this->deleteChildren($menu);
                $menu->roles()->detach();
                $menu->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Successfully deleted']);
        }catch(\Exception $e){
            DB::rollBack();
            \Log::error('failed delete menu'. $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Something went wrong! Please try again.']);
        }
    }

    private function deleteChildren($menu)
    {
        foreach ($menu->children as $child) {
            $this->deleteChildren($child); // Recursively delete its children
            $child->roles()->detach(); // Detach roles before deleting
            $child->delete();
        }
    }

    private function getChildMenuIds($menu)
    {
        $ids = [$menu->id]; // Start with current menu ID

        foreach ($menu->children as $child) {
            $ids = array_merge($ids, $this->getChildMenuIds($child)); // Recursively fetch children
        }

        return $ids;
    }
}
