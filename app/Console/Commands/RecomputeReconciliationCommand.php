<?php

namespace App\Console\Commands;

use App\Services\PbbReconciliationService;
use Illuminate\Console\Command;

class RecomputeReconciliationCommand extends Command
{
    protected $signature = 'pbb:recompute-reconciliation';
    protected $description = 'Recompute reconciliation_summary table from pbb_*, detected_buildings, spatial_plannings';

    public function handle(PbbReconciliationService $service): int
    {
        $this->info('Recomputing reconciliation_summary...');
        $result = $service->recompute();
        $this->info("Inserted {$result['rows_inserted']} rows in {$result['elapsed_ms']} ms");
        $this->line("Computed at: {$result['computed_at']}");
        return self::SUCCESS;
    }
}
