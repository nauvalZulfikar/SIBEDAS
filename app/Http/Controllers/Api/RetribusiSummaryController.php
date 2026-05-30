<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KecamatanStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ringkasan retribusi per kecamatan: potensi (tanpa-izin) vs realisasi (tertagih).
 * Sumber: snapshot kecamatan_stats (di-refresh lewat `kecamatan-stats:refresh`).
 * Read-only, agregat, tanpa PII → aman publik (sejajar /satelit-pbg-pbb/summary).
 */
class RetribusiSummaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // bucket 0 = semua luas; bisa difilter via ?min_area_bucket=50/100/...
        $bucket = (int) $request->get('min_area_bucket', 0);

        $rows = KecamatanStat::query()
            ->where('min_area_bucket', $bucket)
            ->orderByDesc('total_potensi_combined')
            ->get();

        $perKec = $rows->map(function (KecamatanStat $s) {
            $potensi  = (float) $s->without_permit_retribution;
            $enriched = (float) $s->without_permit_enriched_retribution;
            $paid     = (float) $s->actual_retribution_paid;
            $pending  = (float) $s->actual_retribution_pending;
            $combined = (float) $s->total_potensi_combined;
            // gap = porsi potensi+pending yang BELUM terealisasi (paid).
            $gapPct = $combined > 0 ? round(($combined - $paid) / $combined * 100, 1) : null;

            return [
                'kecamatan'        => $s->kecamatan,
                'potensi_total'    => round($potensi, 2),
                'potensi_enriched' => round($enriched, 2),
                'paid'             => round($paid, 2),
                'pending'          => round($pending, 2),
                'gap_pct'          => $gapPct,
            ];
        })->values();

        $sum = fn (string $k) => round((float) $perKec->sum($k), 2);
        $totPotensi = $sum('potensi_total');
        $totPaid    = $sum('paid');
        $totPending = $sum('pending');
        $totCombined = round($totPotensi + $totPending, 2);

        return response()->json([
            'min_area_bucket' => $bucket,
            'refreshed_at'    => optional($rows->max('refreshed_at'))?->toIso8601String(),
            'totals' => [
                'potensi_total'    => $totPotensi,
                'potensi_enriched' => $sum('potensi_enriched'),
                'paid'             => $totPaid,
                'pending'          => $totPending,
                'total_potensi_combined' => $totCombined,
                'gap_pct'          => $totCombined > 0 ? round(($totCombined - $totPaid) / $totCombined * 100, 1) : null,
            ],
            'per_kecamatan' => $perKec,
        ]);
    }
}
