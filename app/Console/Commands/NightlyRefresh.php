<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Tier-1 nightly maintenance — runs after the existing PBB reconcile +
 * postgis sync jobs so it always sees fresh data.
 *
 *   php artisan sibedas:nightly
 *
 * Wraps the existing single-purpose commands in the right order so we
 * have ONE entrypoint to register in the scheduler (and ONE log file to
 * tail when something goes wrong).
 *
 * Order matters:
 *   1. buildings:sync-postgis  (postgis polygon mirror catches up)
 *   2. kecamatan-stats:refresh (aggregates pull fresh polygon counts)
 *
 * Step 1 is already scheduled at 03:00 elsewhere; this command can be
 * called on-demand (admin tooling) or via scheduler at 03:30 to run
 * step 2 only. We keep the call to step 1 here for the on-demand case
 * — if the operator manually runs `sibedas:nightly`, both run.
 */
class NightlyRefresh extends Command
{
    protected $signature = 'sibedas:nightly {--skip-sync : Skip the postgis polygon mirror sync}';
    protected $description = 'Tier-1 nightly: refresh polygon mirror + kecamatan_stats aggregates';

    public function handle(): int
    {
        $t0 = microtime(true);
        $this->info("[nightly] Starting Tier-1 refresh @ " . now()->toIso8601String());

        if (!$this->option('skip-sync')) {
            $this->info('[1/2] buildings:sync-postgis');
            $rc = Artisan::call('buildings:sync-postgis', ['--via' => 'pdo'], $this->output);
            if ($rc !== 0) {
                $this->error("[nightly] postgis sync exited $rc — aborting");
                return $rc;
            }
        } else {
            $this->info('[1/2] (skipped per --skip-sync)');
        }

        $this->info('[2/2] kecamatan-stats:refresh');
        $rc = Artisan::call('kecamatan-stats:refresh', [], $this->output);
        if ($rc !== 0) {
            $this->error("[nightly] stats refresh exited $rc");
            return $rc;
        }

        $secs = round(microtime(true) - $t0, 1);
        $this->info("[nightly] Done in {$secs}s");
        return 0;
    }
}
