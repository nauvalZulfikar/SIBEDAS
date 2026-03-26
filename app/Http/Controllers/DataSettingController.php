<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataSettingRequest;
use App\Models\DataSetting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request as IndexRequest;

class DataSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        $permissions = $this->permissions[$menuId]?? []; // Avoid undefined index error
        $creator = $permissions['allow_create'] ?? 0;
        $updater = $permissions['allow_update'] ?? 0;
        $destroyer = $permissions['allow_destroy'] ?? 0;
        return view("data-settings.index", compact('creator', 'updater', 'destroyer','menuId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(IndexRequest $request)
    {
        $menuId = $request->query('menu_id') ?? $request->input('menu_id');
        return view("data-settings.create", compact('menuId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DataSettingRequest $request)
    {
        try{
            DB::beginTransaction();
            DataSetting::create($request->validated());
            DB::commit();
            return response()->json(['message' => 'Successfully created'],201);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create data setting',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DataSetting $dataSetting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IndexRequest $request,string $id)
    {
        try{
            $data = DataSetting::findOrFail($id);
            $menuId = $request->query('menu_id') ?? $request->input('menu_id');
            if(empty($data)){
                return redirect()->route('data-settings.index')->with('error', 'Invalid id');
            }
            return view("data-settings.edit", compact("data", 'menuId'));
        }catch(Exception $ex){
            return redirect()->route("data-settings.index")->with("error", "Invalid id");
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DataSettingRequest $request,string $id)
    {
        try{
            DB::beginTransaction();
            $data = DataSetting::findOrFail($id);
            $data->update($request->validated());
            DB::commit();
            return response()->json(['message' => 'Successfully updated'], 200);
        }catch(Exception $ex){
            DB::rollBack();
            return response()->json(['message' => $ex->getMessage()],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            DB::beginTransaction();
            DataSetting::findOrFail($id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Item deleted successfully.'], 200);
        }catch(Exception $e){
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete item.'], 500);
        }
    }

    public function getValueSetting(Request $request){
        try{
            $data = DataSetting::where('key', $request->key_name)->first();
            return response()->json([
                'success' => true,
                'message' => "Successfully retrieved data",
                "data"=> $data
            ]);
        }catch(Exception $e){
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
