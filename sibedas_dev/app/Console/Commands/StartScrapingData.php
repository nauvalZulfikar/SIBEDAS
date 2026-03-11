<?php

namespace App\Console\Commands;

use App\Enums\ImportDatasourceStatus;
use App\Jobs\ScrapingDataJob;
use App\Models\ImportDatasource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartScrapingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-scraping-data 
                           {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the optimized scraping data job (Google Sheet -> PBG Task -> Details)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Optimized Scraping Data Job');
        $this->info('=====================================');
        
        if (!$this->option('confirm')) {
            $this->warn('⚠️  This will start a comprehensive data scraping process:');
            $this->line('   1. Google Sheet data scraping');
            $this->line('   2. PBG Task parent data scraping');  
            $this->line('   3. Detailed task information scraping');
            $this->line('   4. BigData resume generation');
            $this->newLine();
            
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $active = ImportDatasource::whereIn('status', [
            ImportDatasourceStatus::Processing->value,
            ImportDatasourceStatus::Paused->value,
        ])->count();

        if ($active > 0) {
            $this->warn('Scraping already running or paused. Skipping dispatch.');
            Log::info('StartScrapingData skipped — another job is active');
            return 0;
        }

        try {
            // Dispatch the optimized job
            $job = new ScrapingDataJob();
            dispatch($job);
            
            Log::info('ScrapingDataJob dispatched via command', [
                'command' => $this->signature,
                'user' => $this->option('confirm') ? 'auto' : 'manual'
            ]);
            
            $this->info('✅ Scraping Data Job has been dispatched to the scraping queue!');
            $this->newLine();
            $this->info('📊 Monitor the job with:');
            $this->line('   php artisan queue:monitor scraping');
            $this->newLine();
            $this->info('📜 View detailed logs with:');
            $this->line('   tail -f /var/log/supervisor/sibedas-queue-scraping.log | grep "SCRAPING DATA JOB"');
            $this->newLine();
            $this->info('🔍 Check ImportDatasource status:');
            $this->line('   docker-compose -f docker-compose.local.yml exec app php artisan tinker --execute="App\\Models\\ImportDatasource::latest()->first();"');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to dispatch ScrapingDataJob: ' . $e->getMessage());
            Log::error('Failed to dispatch ScrapingDataJob via command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}
