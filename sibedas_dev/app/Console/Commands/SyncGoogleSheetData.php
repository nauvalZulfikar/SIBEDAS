<?php

namespace App\Console\Commands;

use App\Services\ServiceGoogleSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncGoogleSheetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:google-sheet {--type=all : Specify sync type (all, google-sheet, big-data, leader)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from Google Sheets to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('Starting Google Sheet data synchronization...');
        $this->info("Sync type: {$type}");
        
        try {
            $service = new ServiceGoogleSheet();
            
            switch ($type) {
                case 'google-sheet':
                    $this->info('Syncing Google Sheet data...');
                    $service->sync_google_sheet_data();
                    $this->info('✅ Google Sheet data synchronized successfully!');
                    break;
                    
                case 'big-data':
                    $this->info('Syncing Big Data...');
                    $service->sync_big_data();
                    $this->info('✅ Big Data synchronized successfully!');
                    break;
                    
                case 'leader':
                    $this->info('Syncing Leader data...');
                    $result = $service->sync_leader_data();
                    $this->info('✅ Leader data synchronized successfully!');
                    $this->table(['Section', 'Total', 'Nominal'], collect($result)->map(function($item, $key) {
                        // Convert nominal to numeric before formatting
                        $nominal = $item['nominal'] ?? 0;
                        if (is_string($nominal)) {
                            // Remove dots and convert to float
                            $nominal = (float) str_replace('.', '', $nominal);
                        }
                        
                        return [
                            $key,
                            $item['total'] ?? 'N/A',
                            number_format((float) $nominal, 0, ',', '.')
                        ];
                    })->toArray());
                    break;
                    
                case 'all':
                default:
                    $this->info('Syncing all data (Google Sheet + Big Data)...');
                    $service->run_service();
                    $this->info('✅ All data synchronized successfully!');
                    break;
            }
            
            $this->newLine();
            $this->info('🚀 Synchronization completed successfully!');
            
        } catch (Exception $e) {
            $this->error('❌ Synchronization failed!');
            $this->error("Error: {$e->getMessage()}");
            
            Log::error('Google Sheet sync command failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
} 