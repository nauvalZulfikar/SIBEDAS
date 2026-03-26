<?php

namespace App\Jobs;

use App\Enums\ImportDatasourceStatus;
use App\Models\BigdataResume;
use App\Models\ImportDatasource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ServiceTabPbgTask;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetrySyncronizeJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;
    private $import_datasource_id;
    public function __construct(int $import_datasource_id)
    {
        $this->import_datasource_id = $import_datasource_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            $service_tab_pbg_task = app(ServiceTabPbgTask::class);
    
            $failed_import = ImportDatasource::find($this->import_datasource_id);
    
            $failed_import->update([
                'message' => "Retrying from UUID: ". $failed_import->failed_uuid, 
                'status' => ImportDatasourceStatus::Processing->value,
                'start_time' => now()
            ]);
    
            $current_failed_uuid = null;
            try{
                $service_tab_pbg_task->run_service($failed_import->failed_uuid);
            }catch(\Exception $e){
                $current_failed_uuid = $service_tab_pbg_task->getFailedUUID();
                throw $e;
            }
    
            BigdataResume::generateResumeData($failed_import->id, date('Y'), "simbg");
    
            $failed_import->update([
                'status' => ImportDatasourceStatus::Success->value,
                'message' => "Retry completed successfully from UUID: ". $failed_import->failed_uuid,
                'finish_time' => now(),
                'failed_uuid' => null
            ]);
        }catch(\Exception $e){
            Log::error("RetrySyncronizeJob Failed: ". $e->getMessage(), [
                'exception' => $e,
            ]);
            if(isset($failed_import)){
                $failed_import->update([
                    'status' => ImportDatasourceStatus::Failed->value,
                    'message' => "Retry failed from UUID: ". $failed_import->failed_uuid,
                    'finish_time' => now(),
                    'failed_uuid' => $current_failed_uuid
                ]);
            }

            $this->fail($e);
        }
    }
}
