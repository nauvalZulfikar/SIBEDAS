<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalSettingRequest;
use App\Http\Requests\UpdateGlobalSettingRequest;
use App\Models\GlobalSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
       /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('settings.general.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('settings.general.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GlobalSettingRequest $request)
    {
        try{
            DB::beginTransaction();
            GlobalSetting::create($request->validated());
            DB::commit();
            return redirect()->route('general.index')->with('success', 'Data saved successfully.');
        }catch(Exception $e){
            DB::rollBack();
            return redirect()->back()
            ->withInput()
            ->with('error', 'Something went wrong while saving data. ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = GlobalSetting::find($id);
        if(!$data){
            return redirect()->route('general.index')->with('error', 'Invalid id');
        }
        return view('settings.general.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = GlobalSetting::find($id);
        if(!$data){
            return redirect()->route('general.index')->with('error', 'Invalid id');
        }
        return view('settings.general.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGlobalSettingRequest $request, string $id)
    {
        try{
            DB::beginTransaction();
            $data = GlobalSetting::findOrFail($id);

            $data->update($request->validated());
            DB::commit();
            return redirect()->route('general.index')->with('success', 'Data updated successfully.');
        }catch(Exception $e){
            DB::rollBack();
            return redirect()->back()
            ->withInput()
            ->with('error', 'Something went wrong while updating data. '. $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            DB::beginTransaction();
            $data = GlobalSetting::findOrFail($id);

            $data->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Item deleted successfully.']);
        }catch(Exception $e){
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete item.'], 500);
        }
    }
}
