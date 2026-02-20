<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataSettingRequest;
use App\Http\Resources\DataSettingResource;
use App\Models\DataSetting;
use App\Traits\GlobalApiResponse;
use Exception;
use Illuminate\Http\Request;

class DataSettingController extends Controller
{
    use GlobalApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = DataSetting::query()->orderBy('id', 'desc');
            if ($request->has("search") && !empty($request->get("search"))) {
                $query = $query->where("key", $request->get("search"));
            }

            return DataSettingResource::collection($query->paginate());
        } catch (Exception $e) {
            return $this->resError($e->getMessage(), $e->getTrace());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DataSettingRequest $request)
    {
        try {
            $data = DataSetting::create($request->validated());
            $result = [
                "success" => true,
                "message" => "Data Setting created successfully",
                "data" => new DataSettingResource($data)
            ];
            return $this->resSuccess($result);
        } catch (Exception $e) {
            return $this->resError($e->getMessage(), $e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $setting = DataSetting::findOrFail($id);
            $result = [
                "setting" => true,
                "message" => "Data setting successfully",
                "data" => new DataSettingResource($setting)
            ];
            return $this->resSuccess($result);
        } catch (Exception $e) {
            return $this->resError($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DataSettingRequest $request, string $id)
    {
        try {
            $data = DataSetting::findOrFail($id);
            $data->update($request->validated());
            $result = [
                "success" => true,
                "message" => "Data Setting updated successfully"
            ];
            return $this->resSuccess($result);
        } catch (Exception $e) {
            return $this->resError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $setting = DataSetting::findOrFail($id);
            $setting->delete();
            $result = [
                "success" => true,
                "message" => "Data Setting deleted successfully"
            ];
            return $this->resSuccess($result);
        } catch (Exception $e) {
            return $this->resError($e->getMessage());
        }
    }
}
