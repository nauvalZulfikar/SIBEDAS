<?php

namespace App\Console\Commands;

use App\Exports\ReconciliationExport;
use App\Services\PbbReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SnapshotReconciliationCommand extends Command
{
    protected $signature = 'pbb:snapshot-reconciliation
        {--retain=24 : Retention bulan (default 24)}';

    protected $description = 'Save monthly reconciliation snapshot xlsx ke storage/app/exports/reconciliation/';

    public function handle(PbbReconciliationService $service): int
    {
        $month = now()->format('Y-m');
        $filename = "reconciliation-{$month}.xlsx";
        $path = "exports/reconciliation/{$filename}";

        $this->info("Generating snapshot {$filename}…");
        Excel::store(new ReconciliationExport($service, includePii: true), $path, 'local');

        $size = Storage::disk('local')->size($path);
        $this->line("  Saved: storage/app/{$path} ({$this->fmtBytes($size)})");

        // Retention cleanup
        $retain = (int) $this->option('retain');
        $threshold = now()->subMonthsNoOverflow($retain)->format('Y-m');
        $deleted = 0;
        foreach (Storage::disk('local')->files('exports/reconciliation') as $f) {
            if (preg_match('/reconciliation-(\d{4}-\d{2})\.xlsx$/', $f, $m)) {
                if ($m[1] < $threshold) {
                    Storage::disk('local')->delete($f);
                    $deleted++;
                }
            }
        }
        if ($deleted > 0) {
            $this->line("  Pruned {$deleted} file lebih lama dari {$retain} bulan");
        }

        return self::SUCCESS;
    }

    private function fmtBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}
