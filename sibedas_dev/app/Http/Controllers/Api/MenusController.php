<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MenusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Menu::query()->orderBy('id', 'desc');

        if($request->has("search") && !empty($request->get("search"))){
            $query = $query->where("name", "like", "%".$request->get("search")."%");
        }

        // return response()->json($query->paginate(config('app.paginate_per_page', 50)));
        return MenuResource::collection($query->paginate(config('app.paginate_per_page',50)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MenuRequest $request)
    {
        try{
            $menu = Menu::create($request->validated());
            return response()->json(['message' => 'Menu created successfully', 'data' => new MenuResource($menu)]);
        }catch(\Exception $e){
            Log::error($e);
            return response()->json(['message' => 'Error when creating menu'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $menu = Menu::find($id);
            if($menu){
                return response()->json(['message' => 'Menu found', 'data' => new MenuResource($menu)]);
            } else {
                return response()->json(['message' => 'Menu not found'], 404);
            }
        }catch(\Exception $e){
            Log::error($e);
            Log::error($e->getMessage());
            return response()->json(['message' => 'Error when finding menu'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MenuRequest $request, string $id)
    {
        try{
            $menu = Menu::findOrFail($id);
            if($menu){
                $menu->update($request->validated());
                return response()->json(['message' => 'Menu updated successfully', 'data' => new MenuResource($menu)]);
            } else {
                return response()->json(['message' => 'Menu not found'], 404);
            }
        }catch(\Exception $e){
            Log::error($e);
            return response()->json(['message' => 'Error when updating menu'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $menu = Menu::findOrFail($id);
            if($menu){
                $this->deleteChildren($menu);
                $menu->roles()->detach();
                $menu->delete();
                return response()->json(['message' => 'Menu deleted successfully']);
            } else {
                return response()->json(['message' => 'Menu not found'], 404);
            }
        }catch(\Exception $e){
            Log::error($e);
            return response()->json(['message' => 'Error when deleting menu'], 500);
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
}
