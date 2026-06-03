#!/bin/zsh
# Daily local regenerate of BigdataResume snapshots (leader + simbg)
# for the Dashboard Pimpinan + Dashboard PBG counters.
# Stubs ServiceGoogleSheet because the GCP service-account JSON only
# exists on prod; spatial_* fields end up zero on local (counts are intact).

set -euo pipefail
cd "$(dirname "$0")/.."
export PATH=/opt/homebrew/opt/php@8.2/bin:/opt/homebrew/bin:$PATH

php <<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\BigdataResume;
use App\Models\ImportDatasource;
use App\Services\ServiceGoogleSheet;
use Illuminate\Support\Facades\Cache;

$app->bind(ServiceGoogleSheet::class, fn () => new class {
    public function __call($name, $args) { return 0; }
});

$ds = ImportDatasource::create([
    'message'    => 'Local cron refresh (Google stub)',
    'status'     => 'processing',
    'start_time' => now(),
]);
$t0 = microtime(true);
foreach (['leader','simbg'] as $type) {
    BigdataResume::generateResumeData($ds->id, (int) date('Y'), $type);
}
$ds->update(['status'=>'success','finish_time'=>now(),'message'=>'OK']);

// Invalidate API response cache so dashboards immediately reflect the new snapshot.
// BigDataResumeController caches at key bigdata_resume_{filterDate|latest}_{type}, TTL 24h.
foreach (['leader','simbg'] as $type) {
    Cache::forget("bigdata_resume_latest_{$type}");
}
Cache::forget('latest_import_created');

printf("[%s] refreshed leader+simbg in %.1fs (cache flushed)\n", date('c'), microtime(true)-$t0);
PHP
