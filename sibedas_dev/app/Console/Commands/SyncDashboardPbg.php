<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ServiceGoogleSheet;
use App\Models\BigdataResume;
use App\Models\ImportDatasource;
use Illuminate\Support\Facades\Log;

class SyncDashboardPbg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-dashboard-pbg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $import_datasource = ImportDatasource::create([
            'message' => 'Initiating sync dashboard pbg...',
            'response_body' => null,
            'status' => 'processing',
            'start_time' => now(),
            'failed_uuid' => null
        ]);

        try {
            BigdataResume::generateResumeData($import_datasource->id, date('Y'), "simbg");
    
            $import_datasource->update([
                'status' => 'success',
                'message' => 'Sync dashboard pbg completed successfully.',
                'finish_time' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Sync dashboard pbg failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Update status to failed
            if (isset($import_datasource)) {
                $import_datasource->update([
                    'status' => 'failed',
                    'message' => 'Sync dashboard pbg failed.',
                    'finish_time' => now()
                ]);
            }
        }

    }
}
