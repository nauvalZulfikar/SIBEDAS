<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalSettingRequest;
use App\Http\Resources\GlobalSettingResource;
use App\Models\GlobalSetting;
use App\Traits\GlobalApiResponse;
use Exception;
use Illuminate\Http\Request;

class GlobalSettingsController extends Controller
{
    use GlobalApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = GlobalSetting::query()->orderBy('id','desc');
        if($request->has('search') && !empty($request->get("search"))){
            $query->where('key', 'LIKE', '%'.$request->get('search').'%');
        }

        return GlobalSettingResource::collection($query->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GlobalSettingRequest $request)
    {
        try {
            $data = GlobalSetting::create($request->validated());
            return new GlobalSettingResource($data);
        } catch (Exception $e) {
            return $this->resError($e->getMessage(), null, $e->getCode());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $data = GlobalSetting::find($id);
            if(!$data){
                return $this->resError("Invalid id", null, 404);
            }
            return new GlobalSettingResource($data);
        }catch(Exception $e){
            return $this->resError($e->getMessage(), null, $e->getCode());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GlobalSettingRequest $request, string $id)
    {
        try{

            $data = GlobalSetting::find($id);
            if(!$data){
                return $this->resError("Invalid id", null, 400);
            }

            $data->update($request->validated());

            return new GlobalSettingResource($data);

        }catch(Exception $e){
            return $this->resError($e->getMessage(), null, $e->getCode());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $data = GlobalSetting::find($id);
            if(!$data){
                return $this->resError("Invalid id", null, 404);
            }

            $data->delete();

            return new GlobalSettingResource($data);
        }catch(Exception $e){
            return $this->resError($e->getMessage(), null, $e->getCode());
        }
    }
}
