<?php

namespace App\Jobs;

use App\Models\BigdataResume;
use App\Models\ImportDatasource;
use App\Models\PbgTask;
use App\Services\ServiceGoogleSheet;
use App\Services\ServicePbgTask;
use App\Services\ServiceTabPbgTask;
use App\Services\ServiceTokenSIMBG;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapingDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Use dedicated scraping queue
        $this->queue = 'scraping';
    }

    /**
     * Execute the job with optimized schema:
     * 1. Scrape Google Sheet first
     * 2. Scrape PBG Task to get parent data
     * 3. Loop through parent tasks to scrape details via ServiceTabPbgTask
     */
    public function handle()
    {
        $import_datasource = null;
        $failed_uuid = null;
        $processedTasks = 0;
        $totalTasks = 0;

        try {
            Log::info("=== SCRAPING DATA JOB STARTED ===");

            // Initialize services
            $service_google_sheet = app(ServiceGoogleSheet::class);
            $service_pbg_task = app(ServicePbgTask::class);
            $service_tab_pbg_task = app(ServiceTabPbgTask::class);

            // Create ImportDatasource record
            $import_datasource = ImportDatasource::create([
                'message' => 'Starting optimized scraping process...',
                'response_body' => null,
                'status' => 'processing',
                'start_time' => now(),
                'failed_uuid' => null
            ]);
            $import_datasource->update(['message' => 'Scraping PBG Task parent data...']);
            
            $service_pbg_task->run_service();

            // STEP 3: Get all PBG tasks for detail scraping
            $totalTasks = PbgTask::count();

            $import_datasource->update([
                'message' => "Scraping details for {$totalTasks} PBG tasks..."
            ]);

            // Process tasks in chunks for memory efficiency
            $chunkSize = 100;
            $processedTasks = 0;

            PbgTask::orderBy('id')->chunk($chunkSize, function ($pbg_tasks) use (
                $service_tab_pbg_task, 
                &$processedTasks, 
                $totalTasks, 
                $import_datasource,
                &$failed_uuid
            ) {
                foreach ($pbg_tasks as $pbg_task) {
                    try {
                        // Scrape all details for this task
                        $this->processTaskDetails($service_tab_pbg_task, $pbg_task->uuid);
                        
                        $processedTasks++;
                        
                        // Update progress every 10 tasks
                        if ($processedTasks % 10 === 0) {
                            $progress = round(($processedTasks / $totalTasks) * 100, 2);
                            Log::info("Progress update", [
                                'processed' => $processedTasks,
                                'total' => $totalTasks,
                                'progress' => "{$progress}%"
                            ]);
                            
                            $import_datasource->update([
                                'message' => "Processing details: {$processedTasks}/{$totalTasks} ({$progress}%)"
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        Log::warning("Failed to process task details", [
                            'uuid' => $pbg_task->uuid,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Store failed UUID but continue processing
                        $failed_uuid = $pbg_task->uuid;
                        
                        // Only stop if it's a critical error
                        if ($this->isCriticalError($e)) {
                            throw $e;
                        }
                    }
                }
            });

            $import_datasource->update(['message' => 'Scraping Google Sheet data...']);
            
            $service_google_sheet->run_service();

            $import_datasource->update(['message' => 'Generating BigData resume...']);
            
            BigdataResume::generateResumeData($import_datasource->id, date('Y'), "simbg");
            
            Log::info("BigData resume generated successfully");

            // Update final status
            $import_datasource->update([
                'status' => 'success',
                'message' => "Scraping completed successfully. Processed {$processedTasks}/{$totalTasks} tasks.",
                'finish_time' => now(),
                'failed_uuid' => $failed_uuid // Store last failed UUID if any
            ]);

            Log::info("=== SCRAPING DATA JOB COMPLETED SUCCESSFULLY ===", [
                'import_datasource_id' => $import_datasource->id,
                'processed_tasks' => $processedTasks,
                'total_tasks' => $totalTasks,
                'has_failures' => !is_null($failed_uuid)
            ]);

        } catch (\Exception $e) {
            Log::error('=== SCRAPING DATA JOB FAILED ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'processed_tasks' => $processedTasks,
                'total_tasks' => $totalTasks,
                'failed_uuid' => $failed_uuid,
                'trace' => $e->getTraceAsString()
            ]);

            // Update ImportDatasource with failure info
            if ($import_datasource) {
                $import_datasource->update([
                    'status' => 'failed',
                    'message' => "Scraping failed: {$e->getMessage()}. Processed {$processedTasks}/{$totalTasks} tasks.",
                    'response_body' => 'Scraping process interrupted due to error',
                    'finish_time' => now(),
                    'failed_uuid' => $failed_uuid,
                ]);
            }

            // Don't retry this job
            $this->fail($e);
        }
    }

    /**
     * Process all detail endpoints for a single PBG task
     */
    private function processTaskDetails(ServiceTabPbgTask $service, string $uuid): void
    {
        // Call all detail scraping methods for this task
        $service->scraping_task_details($uuid);
        $service->scraping_pbg_data_list($uuid);
        $service->scraping_task_retributions($uuid);
        $service->scraping_task_integrations($uuid);
        $service->scraping_task_detail_status($uuid);
    }

    /**
     * Determine if an error is critical enough to stop the entire process
     */
    private function isCriticalError(\Exception $e): bool
    {
        $criticalMessages = [
            'authentication failed',
            'token expired',
            'database connection',
            'memory exhausted',
            'maximum execution time'
        ];

        $errorMessage = strtolower($e->getMessage());
        
        foreach ($criticalMessages as $critical) {
            if (strpos($errorMessage, $critical) !== false) {
                return true;
            }
        }

        return false;
    }
}
