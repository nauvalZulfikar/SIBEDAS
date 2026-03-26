<?php

namespace App\Console\Commands;

use App\Enums\ImportDatasourceStatus;
use App\Jobs\ScrapingDataJob;
use App\Models\ImportDatasource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorScrapingJob extends Command
{
    protected $signature = 'app:monitor-scraping';
    protected $description = 'Monitor scraping job heartbeat — auto-recover stale jobs';

    // If no progress update in this many minutes, consider the job dead
    private const STALE_MINUTES = 30;

    public function handle()
    {
        $active = ImportDatasource::where('status', ImportDatasourceStatus::Processing->value)
            ->latest()
            ->first();

        if (!$active) {
            $this->line('No active scraping job.');
            return 0;
        }

        $minutesSinceUpdate = $active->updated_at->diffInMinutes(now());

        if ($minutesSinceUpdate < self::STALE_MINUTES) {
            $this->info("Job #{$active->id} is alive — last update {$minutesSinceUpdate}m ago: {$active->message}");
            return 0;
        }

        // Job is stale — mark as failed
        Log::warning("MonitorScrapingJob: Job #{$active->id} stale for {$minutesSinceUpdate}m, marking as failed", [
            'last_message' => $active->message,
            'last_updated' => $active->updated_at,
        ]);

        $active->update([
            'status' => ImportDatasourceStatus::Failed->value,
            'message' => "Auto-recovered: no heartbeat for {$minutesSinceUpdate}m. Last: {$active->message}",
            'finish_time' => now(),
        ]);

        $this->warn("Job #{$active->id} marked as failed (stale {$minutesSinceUpdate}m).");

        // Auto-restart
        $this->info('Dispatching new scraping job...');
        dispatch(new ScrapingDataJob());

        Log::info('MonitorScrapingJob: Auto-dispatched new ScrapingDataJob after stale recovery');
        $this->info('New scraping job dispatched.');

        return 0;
    }
}
