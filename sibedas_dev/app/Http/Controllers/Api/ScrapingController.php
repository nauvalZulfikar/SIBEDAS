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
        $check_datasource = ImportDatasource::whereIn("status", [
            ImportDatasourceStatus::Processing->value,
            ImportDatasourceStatus::Paused->value,
        ])->count();
        if($check_datasource > 0){
            return $this->resError("Failed to execute while processing another scraping");
        }

        // use ole schema synchronization
        // dispatch(new SyncronizeSIMBG());

        // use new schema synchronization
        dispatch(new ScrapingDataJob());
        return $this->resSuccess(["message" => "Success execute scraping service on background, check status for more"]);
    }

    public function pause(string $id)
    {
        $import = ImportDatasource::find($id);
        if (!$import) {
            return $this->resError("Import datasource not found", null, 404);
        }
        if ($import->status !== ImportDatasourceStatus::Processing->value) {
            return $this->resError("Can only pause a processing job");
        }
        $import->update(['status' => ImportDatasourceStatus::Paused->value, 'message' => 'Paused by user']);
        return $this->resSuccess(["message" => "Scraping job paused"]);
    }

    public function resume(string $id)
    {
        $import = ImportDatasource::find($id);
        if (!$import) {
            return $this->resError("Import datasource not found", null, 404);
        }
        if ($import->status !== ImportDatasourceStatus::Paused->value) {
            return $this->resError("Can only resume a paused job");
        }
        $import->update(['status' => ImportDatasourceStatus::Processing->value, 'message' => 'Resumed by user']);
        dispatch(new ScrapingDataJob($import->id, $import->failed_uuid));
        return $this->resSuccess(["message" => "Scraping job resumed"]);
    }

    public function cancel(string $id)
    {
        $import = ImportDatasource::find($id);
        if (!$import) {
            return $this->resError("Import datasource not found", null, 404);
        }
        if (!in_array($import->status, [ImportDatasourceStatus::Processing->value, ImportDatasourceStatus::Paused->value])) {
            return $this->resError("Can only cancel a processing or paused job");
        }
        $import->update([
            'status' => ImportDatasourceStatus::Cancelled->value,
            'message' => 'Cancelled by user',
            'finish_time' => now(),
        ]);
        return $this->resSuccess(["message" => "Scraping job cancelled"]);
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
