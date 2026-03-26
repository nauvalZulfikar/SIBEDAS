<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportDatasourceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImportDatasourceResource;
use App\Models\ImportDatasource;
use App\Traits\GlobalApiResponse;
use Exception;
use Illuminate\Http\Request;

class ImportDatasourceController extends Controller
{
    use GlobalApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ImportDatasource::query()->orderBy('id', 'desc');

        if($request->has("search") && !empty($request->get("search"))){
            $search = $request->get("search");
            $query->where('status', 'like', "%".$search."%");
        }
        return ImportDatasourceResource::collection($query->paginate(config('app.paginate_per_page', 50)));
    }

    public function checkImportDatasource(){
        try{
            $active = ImportDatasource::whereIn("status", [
                ImportDatasourceStatus::Processing->value,
                ImportDatasourceStatus::Paused->value,
            ])->latest('id')->first();

            $result = [
                "can_execute" => is_null($active),
                "total_processing" => $active ? 1 : 0,
                "active_job" => $active ? [
                    "id" => $active->id,
                    "status" => $active->status,
                    "message" => $active->message,
                ] : null,
            ];
            return response()->json( $result , 200);
        }catch(Exception $ex){
            return response()->json(["message" => $ex->getMessage(), 500]);
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

