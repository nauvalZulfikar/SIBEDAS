<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReconciliationExport;
use App\Http\Controllers\Controller;
use App\Services\PbbReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReconciliationController extends Controller
{
    public function __construct(private PbbReconciliationService $service)
    {
        parent::__construct();
    }

    public function summary(): JsonResponse
    {
        $data = $this->service->getKabSummary();
        return response()->json(['data' => $data]);
    }

    public function perKec(): JsonResponse
    {
        $rows = $this->service->getPerKec();
        return response()->json([
            'data' => $rows,
            'meta' => ['count' => count($rows)],
        ]);
    }

    public function perKelurahan(string $kecName): JsonResponse
    {
        $rows = $this->service->getPerKelurahan($kecName);
        if (empty($rows)) {
            return response()->json([
                'data' => [],
                'meta' => ['count' => 0],
                'message' => "Kecamatan '{$kecName}' tidak ditemukan atau belum punya data PBB.",
            ], 200);
        }
        return response()->json([
            'data' => $rows,
            'meta' => [
                'count' => count($rows),
                'kecamatan' => $rows[0]['kecamatan'] ?? $kecName,
            ],
        ]);
    }

    public function noSatelliteNop(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->get('limit', 50), 1), 500);
        $offset = max((int) $request->get('offset', 0), 0);
        $result = $this->service->getNopWithoutSatellite($limit, $offset);

        $isL3 = $this->callerHasLevel3($request);
        $rows = array_map(fn ($r) => $isL3 ? $r : $this->maskPii($r, ['nama_wp', 'alamat']), $result['data']);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($rows),
                'total_terbangun' => DB::table('pbb_records')->where('luas_bangunan', '>', 0)->count(),
                'pii_masked' => !$isL3,
            ],
            'note' => $result['note'] ?? null,
        ]);
    }

    public function noNopSatellite(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->get('limit', 50), 1), 500);
        $offset = max((int) $request->get('offset', 0), 0);
        $result = $this->service->getSatelliteWithoutNop($limit, $offset);
        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned' => count($result['data']),
                'total_unmapped' => $result['count_estimate'] ?? null,
            ],
            'note' => $result['note'] ?? null,
        ]);
    }

    public function recompute(Request $request): JsonResponse
    {
        // pbb.clearance:level_3 middleware handles authorization upstream.
        $result = $this->service->recompute();
        return response()->json([
            'message' => 'Reconciliation summary berhasil di-recompute.',
            'data' => $result,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $isL3 = $this->callerHasLevel3($request);
        $filename = 'rekonsiliasi-pbb-' . now()->format('Ymd-His') . ($isL3 ? '' : '-masked') . '.xlsx';
        return Excel::download(new ReconciliationExport($this->service, $isL3), $filename);
    }

    public function exportPdf(Request $request)
    {
        $kab = $this->service->getKabSummary();
        $kec = $this->service->getPerKec();
        usort($kec, fn ($a, $b) => abs($b['gap']) <=> abs($a['gap']));
        $topKec = array_slice($kec, 0, 10);

        $lastComputed = DB::table('reconciliation_summary')->max('computed_at') ?? now();

        $pdf = Pdf::loadView('exports.reconciliation_report', [
            'kab' => $kab,
            'topKec' => $topKec,
            'lastComputed' => $lastComputed,
            'generatedBy' => $request->user()?->email,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rekonsiliasi-pbb-' . now()->format('Ymd-His') . '.pdf');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $scope = $request->get('scope', 'kec');
        if (!in_array($scope, ['kab', 'kec', 'kelurahan'])) {
            $scope = 'kec';
        }
        $filename = "reconciliation-{$scope}-" . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($scope) {
            $out = fopen('php://output', 'w');
            // BOM untuk Excel UTF-8
            fwrite($out, "\xEF\xBB\xBF");

            if ($scope === 'kab') {
                fputcsv($out, ['metric', 'value']);
                foreach ($this->service->getKabSummary() as $k => $v) {
                    fputcsv($out, [$k, $v]);
                }
            } elseif ($scope === 'kec') {
                fputcsv($out, ['kecamatan', 'djp_code', 'pbb_total', 'pbb_terbangun',
                    'pbb_lahan_kosong', 'sat_count', 'gap', 'gap_pct', 'pbb_lb_m2', 'sat_area_m2']);
                foreach ($this->service->getPerKec() as $r) {
                    fputcsv($out, [
                        $r['kecamatan'], $r['djp_code'], $r['pbb_total'], $r['pbb_terbangun'],
                        $r['pbb_lahan_kosong'], $r['sat_count'], $r['gap'], $r['gap_pct'],
                        $r['pbb_lb_m2'], $r['sat_area_m2'],
                    ]);
                }
            } else {
                fputcsv($out, ['kecamatan', 'kelurahan', 'pbb_total', 'pbb_terbangun',
                    'sat_count', 'gap', 'gap_pct', 'coverage_status']);
                $rows = DB::table('reconciliation_summary')
                    ->where('scope', 'kelurahan')
                    ->orderBy('kecamatan_name')->orderBy('kelurahan_name')
                    ->cursor();
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->kecamatan_name, $r->kelurahan_name, $r->pbb_total, $r->pbb_terbangun,
                        $r->sat_count, $r->gap_sat_minus_terbangun, $r->gap_pct,
                        $r->sat_count > 0 ? 'covered' : 'pending_polygon',
                    ]);
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function callerHasLevel3(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;
        return $user->roles()->where('pbb_clearance', 'level_3')->exists();
    }

    /**
     * Mask PII fields for level_2 callers. Pattern: keep first 2 + last 1 chars,
     * replace middle with asterisks. e.g. "BUDI HARTONO" -> "BU*** O".
     */
    private function maskPii(array $row, array $fields): array
    {
        foreach ($fields as $f) {
            if (!isset($row[$f]) || !is_string($row[$f])) continue;
            $row[$f] = $this->maskString($row[$f]);
        }
        return $row;
    }

    private function maskString(string $s): string
    {
        $s = trim($s);
        $len = mb_strlen($s);
        if ($len <= 4) return str_repeat('*', $len);
        return mb_substr($s, 0, 2) . str_repeat('*', max(3, $len - 3)) . mb_substr($s, -1);
    }
}
