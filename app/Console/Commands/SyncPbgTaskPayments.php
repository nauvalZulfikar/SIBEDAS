<?php

namespace App\Console\Commands;

use App\Services\ServiceGoogleSheet;
use Illuminate\Console\Command;

class SyncPbgTaskPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:pbg-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync PBG task payments from Google Sheets Sheet Data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting PBG Task Payments sync...');
        $this->newLine();

        try {
            $service = new ServiceGoogleSheet();
            
            // Show progress bar
            $this->info('📊 Fetching data from Google Sheets...');
            $result = $service->sync_pbg_task_payments();

            // Display results
            $this->newLine();
            $this->info('✅ Sync completed successfully!');
            $this->newLine();
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Inserted rows', $result['inserted'] ?? 0],
                    ['Success', ($result['success'] ?? false) ? 'Yes' : 'No'],
                ]
            );

            $this->newLine();
            $this->info('📝 Check Laravel logs for detailed information.');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Sync failed!');
            $this->error('Error: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
