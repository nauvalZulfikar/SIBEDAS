<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Public health check for the PBB module — uptime probes, deploy verification.
 *
 * Returns 200 with a per-component breakdown. Probes don't need auth so
 * monitoring (UptimeRobot, kuma, prometheus blackbox) can hit it without
 * managing tokens. No PII surface.
 */
class PbbHealthController extends Controller
{
    public function __construct()
    {
        // Skip parent constructor (which tries to load user permissions)
        // because health endpoint is unauthenticated.
    }

    public function pbb(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'pbb_records' => $this->checkPbbRecords(),
            'reconciliation_summary' => $this->checkReconciliationSummary(),
            'detected_buildings' => $this->checkDetectedBuildings(),
            'snapshot_dir' => $this->checkSnapshotDir(),
            'last_recompute' => $this->checkLastRecompute(),
        ];

        $allOk = collect($checks)->every(fn ($c) => ($c['status'] ?? 'fail') === 'ok');
        $code = $allOk ? 200 : 503;

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'app' => [
                'env' => config('app.env'),
                'version' => $this->resolveVersion(),
                'time' => now()->toIso8601String(),
            ],
            'components' => $checks,
        ], $code);
    }

    private function checkDatabase(): array
    {
        try {
            $row = DB::selectOne('SELECT VERSION() as v');
            return ['status' => 'ok', 'version' => $row->v];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkPbbRecords(): array
    {
        try {
            $cnt = DB::table('pbb_records')->count();
            $expected_min = 1_000_000; // staging+prod should have full 1.15M
            return [
                'status' => $cnt >= $expected_min ? 'ok' : 'warn',
                'count' => $cnt,
                'expected_min' => $expected_min,
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkReconciliationSummary(): array
    {
        try {
            $cnt = DB::table('reconciliation_summary')->count();
            return [
                'status' => $cnt === 307 ? 'ok' : 'warn',
                'count' => $cnt,
                'expected' => 307,  // 1 kab + 31 kec + 275 kelurahan
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkDetectedBuildings(): array
    {
        try {
            $cnt = DB::table('detected_buildings')->count();
            return [
                'status' => $cnt > 100_000 ? 'ok' : 'warn',
                'count' => $cnt,
                'expected_min' => 100_000,
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkSnapshotDir(): array
    {
        try {
            $files = Storage::disk('local')->files('exports/reconciliation');
            $latest = collect($files)->sort()->last();
            return [
                'status' => 'ok',
                'count' => count($files),
                'latest' => $latest ? basename($latest) : null,
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function checkLastRecompute(): array
    {
        try {
            $latest = DB::table('reconciliation_summary')->max('computed_at');
            if (!$latest) {
                return ['status' => 'warn', 'message' => 'reconciliation_summary is empty'];
            }
            $age = now()->diffInHours($latest);
            return [
                'status' => $age < 26 ? 'ok' : 'warn',  // daily 02:00 + 2h grace
                'computed_at' => $latest,
                'hours_ago' => $age,
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'error' => $e->getMessage()];
        }
    }

    private function resolveVersion(): string
    {
        $f = base_path('VERSION');
        if (file_exists($f)) return trim(file_get_contents($f));
        // Fallback: short git sha if .git is present
        $head = base_path('.git/HEAD');
        if (file_exists($head)) {
            $ref = trim(file_get_contents($head));
            if (str_starts_with($ref, 'ref: ')) {
                $refFile = base_path('.git/' . substr($ref, 5));
                if (file_exists($refFile)) return substr(trim(file_get_contents($refFile)), 0, 7);
            }
            return substr($ref, 0, 7);
        }
        return 'unknown';
    }
}
