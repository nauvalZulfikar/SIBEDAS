<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportDatasourceStatus;
use App\Http\Controllers\Controller;
use App\Jobs\RetrySyncronizeJob;
use App\Jobs\ScrapingDataJob;
use App\Jobs\SyncronizeSIMBG;
use App\Models\ImportDatasource;
use App\Traits\GlobalApiResponse;
use App\Services\ServiceTokenSIMBG;
use GuzzleHttp\Client;
use App\Services\ServiceGoogleSheet;
use App\Services\ServicePbgTask;
use App\Services\ServiceTabPbgTask;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class ScrapingController extends Controller
{
    use GlobalApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $check_datasource = ImportDatasource::where("status", ImportDatasourceStatus::Processing->value)->count();
        if($check_datasource > 0){
            return $this->resError("Failed to execute while processing another scraping");
        }

        // use ole schema synchronization
        // dispatch(new SyncronizeSIMBG());

        // use new schema synchronization
        dispatch(new ScrapingDataJob());
        return $this->resSuccess(["message" => "Success execute scraping service on background, check status for more"]);
    }

    public function retry_syncjob(string $import_datasource_id){
        try{

            $import_datasource = ImportDatasource::find($import_datasource_id);
            if(!$import_datasource){
                return $this->resError("Invalid import datasource id", null, 404);
            }

            dispatch(new RetrySyncronizeJob($import_datasource->id));
            return response()->json([
                "success" => true,
                "message" => "Retrying scrape job on background, check status for more"
            ]);
        }catch(\Exception $e){
            return response()->json([
                "success" => false,
                "message" => "Failed to retry sync job",
                "error" => $e->getMessage()
            ]);
        }
    }
}
