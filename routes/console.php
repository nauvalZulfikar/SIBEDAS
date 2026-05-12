<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command("app:start-scraping-data --confirm")->dailyAt("00:00");
Schedule::command("app:monitor-scraping")->everyThirtyMinutes();
Schedule::command("pbb:recompute-reconciliation")->dailyAt("02:00");
Schedule::command("pbb:snapshot-reconciliation")->monthlyOn(1, "03:00");

// Vector-tiles polygon layer: refresh PostGIS mirror of detected_buildings.
// Runs after the daily PBB recompute (02:00) so any newly-matched
// buildings inherit the up-to-date status_color. withoutOverlapping
// guards against a slow run colliding with the next day's tick (the
// initial backfill takes ~6 minutes; incremental upserts are faster).
// --via=pdo forces the production path; if pdo_pgsql is missing the job
// errors out cleanly instead of dumping multi-GB of raw SQL to the log.
Schedule::command("buildings:sync-postgis --via=pdo")
    ->dailyAt("03:00")
    ->withoutOverlapping(120)            // lock TTL in minutes
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/buildings-sync.log'))
    ->onFailure(function () {
        \Log::error('Scheduled buildings:sync-postgis failed — see storage/logs/buildings-sync.log');
        if (app()->bound('sentry')) {
            app('sentry')->captureMessage('buildings:sync-postgis scheduled run failed');
        }
    });

