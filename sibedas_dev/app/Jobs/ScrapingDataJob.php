<?php

namespace App\Jobs;

use App\Enums\ImportDatasourceStatus;
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

    public int $tries = 1;

    protected ?int $resumeImportId;
    protected ?string $resumeFromUuid;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $resumeImportId = null, ?string $resumeFromUuid = null)
    {
        $this->queue = 'scraping';
        $this->resumeImportId = $resumeImportId;
        $this->resumeFromUuid = $resumeFromUuid;
    }

    /**
     * Check if the job has been paused or cancelled by the user.
     * Returns the current status, or null if the record doesn't exist.
     */
    private function checkStatus(ImportDatasource $import): string
    {
        // Refresh from DB to get latest status (user may have changed it)
        $import->refresh();
        return $import->status;
    }

    /**
     * Execute the job with optimized schema:
     * 1. Scrape PBG Task to get parent data
     * 2. Loop through parent tasks to scrape details via ServiceTabPbgTask
     * 3. Scrape Google Sheet
     * 4. Generate BigData resume
     */
    public function handle()
    {
        $import_datasource = null;
        $failed_uuid = null;
        $processedTasks = 0;
        $totalTasks = 0;

        try {
            Log::info("=== SCRAPING DATA JOB STARTED ===");

            // Prevent duplicate runs — abort if another job is already processing
            if (!$this->resumeImportId) {
                $active = ImportDatasource::whereIn('status', [
                    ImportDatasourceStatus::Processing->value,
                    ImportDatasourceStatus::Paused->value,
                ])->count();
                if ($active > 0) {
                    Log::warning("ScrapingDataJob aborted — another job is already active");
                    return;
                }
            }

            // Initialize services
            $service_google_sheet = app(ServiceGoogleSheet::class);
            $service_pbg_task = app(ServicePbgTask::class);
            $service_tab_pbg_task = app(ServiceTabPbgTask::class);

            // Resume existing or create new ImportDatasource record
            if ($this->resumeImportId) {
                $import_datasource = ImportDatasource::find($this->resumeImportId);
                if (!$import_datasource) {
                    Log::error("Resume failed: ImportDatasource #{$this->resumeImportId} not found");
                    return;
                }
                $import_datasource->update([
                    'status' => ImportDatasourceStatus::Processing->value,
                    'message' => 'Resuming scraping process...',
                ]);
                Log::info("Resuming scraping from ImportDatasource #{$this->resumeImportId}");
            } else {
                $import_datasource = ImportDatasource::create([
                    'message' => 'Starting scraping process...',
                    'response_body' => null,
                    'status' => ImportDatasourceStatus::Processing->value,
                    'start_time' => now(),
                    'failed_uuid' => null
                ]);

                $import_datasource->update(['message' => 'Scraping PBG Task parent data...']);
                $service_pbg_task->run_service();
            }

            // Get all PBG tasks for detail scraping
            $totalTasks = PbgTask::count();

            $import_datasource->update([
                'message' => "Scraping details for {$totalTasks} PBG tasks..."
            ]);

            // Process tasks in chunks for memory efficiency
            $chunkSize = 100;
            $processedTasks = 0;
            $shouldSkip = !is_null($this->resumeFromUuid);
            $stopped = false;

            PbgTask::orderBy('id')->chunk($chunkSize, function ($pbg_tasks) use (
                $service_tab_pbg_task,
                &$processedTasks,
                $totalTasks,
                $import_datasource,
                &$failed_uuid,
                &$shouldSkip,
                &$stopped
            ) {
                foreach ($pbg_tasks as $pbg_task) {
                    // If resuming, skip tasks until we reach the resume point
                    if ($shouldSkip) {
                        if ($pbg_task->uuid === $this->resumeFromUuid) {
                            $shouldSkip = false;
                        }
                        $processedTasks++;
                        continue;
                    }

                    // Check for pause/cancel every 5 tasks
                    if ($processedTasks % 5 === 0) {
                        $status = $this->checkStatus($import_datasource);
                        if ($status === ImportDatasourceStatus::Paused->value) {
                            Log::info("Scraping paused by user at task {$processedTasks}/{$totalTasks}");
                            $import_datasource->update([
                                'message' => "Paused at {$processedTasks}/{$totalTasks} tasks",
                                'failed_uuid' => $pbg_task->uuid,
                            ]);
                            $stopped = true;
                            return false; // Stop chunk processing
                        }
                        if ($status === ImportDatasourceStatus::Cancelled->value) {
                            Log::info("Scraping cancelled by user at task {$processedTasks}/{$totalTasks}");
                            $stopped = true;
                            return false; // Stop chunk processing
                        }
                    }

                    try {
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

                        $failed_uuid = $pbg_task->uuid;

                        if ($this->isCriticalError($e)) {
                            throw $e;
                        }
                    }
                }
            });

            // If paused or cancelled, stop here
            if ($stopped) {
                return;
            }

            $import_datasource->update(['message' => 'Scraping Google Sheet data...']);

            $service_google_sheet->run_service();

            // Check again before final step
            $status = $this->checkStatus($import_datasource);
            if (in_array($status, [ImportDatasourceStatus::Paused->value, ImportDatasourceStatus::Cancelled->value])) {
                return;
            }

            $import_datasource->update(['message' => 'Generating BigData resume...']);

            BigdataResume::generateResumeData($import_datasource->id, date('Y'), "simbg");
            BigdataResume::generateResumeData($import_datasource->id, date('Y'), "leader");

            Log::info("BigData resume generated successfully");

            // Update final status
            $import_datasource->update([
                'status' => ImportDatasourceStatus::Success->value,
                'message' => "Scraping completed successfully. Processed {$processedTasks}/{$totalTasks} tasks.",
                'finish_time' => now(),
                'failed_uuid' => $failed_uuid
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

            if ($import_datasource) {
                $import_datasource->update([
                    'status' => ImportDatasourceStatus::Failed->value,
                    'message' => "Scraping failed: {$e->getMessage()}. Processed {$processedTasks}/{$totalTasks} tasks.",
                    'response_body' => 'Scraping process interrupted due to error',
                    'finish_time' => now(),
                    'failed_uuid' => $failed_uuid,
                ]);
            }

            $this->fail($e);
        }
    }

    /**
     * Process all detail endpoints for a single PBG task
     */
    private function processTaskDetails(ServiceTabPbgTask $service, string $uuid): void
    {
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
