<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Combined dashboard endpoint: Satelit ↔ PBG ↔ PBB.
 *
 * Per-kecamatan breakdown of:
 *   - sat_count       : detected_buildings (satellite)
 *   - pbb_terbangun   : pbb_kecamatan_lookup.terbangun_count (NOP yg punya bangunan)
 *   - pbg_terbit      : pbg_task.status=20 yang ke-match satelit (SK Terbit)
 *   - tidak_berizin   : sat_count - pbg_terbit (bangunan tanpa SK PBG)
 *
 * Scope: 31 kec Bandung Selatan (consistent dgn dashboard satelit-pbg).
 */
class SatelitPbgPbbController extends Controller
{
    private const BANDUNG_SELATAN_DISTRICTS = [
        'Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung',
        'Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot',
        'Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu',
        'Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali',
        'Rancaekek','Soreang','Solokanjeruk',
    ];

    private const CACHE_TTL = 600; // 10 min
    private const CACHE_KEY = 'satelit_pbg_pbb_summary_v1';

    public function __construct()
    {
        // Skip parent if it does role/permission lookup — this endpoint is read-only.
    }

    public function summary(Request $request): JsonResponse
    {
        $minArea = max(0, (int) $request->get('min_area', 0));
        $key = self::CACHE_KEY . ':' . $minArea;

        $payload = Cache::remember($key, self::CACHE_TTL, function () use ($minArea) {
            return $this->compute($minArea);
        });

        return response()->json($payload);
    }

    private function compute(int $minArea): array
    {
        // 1. Satelit per kec (Bandung Selatan only, with optional area filter)
        $satQ = DB::table('detected_buildings')
            ->selectRaw('kecamatan AS kec, COUNT(*) AS c')
            ->whereIn('kecamatan', self::BANDUNG_SELATAN_DISTRICTS);
        if ($minArea > 0) $satQ->where('estimated_area_m2', '>=', $minArea);
        $sat = $satQ->groupBy('kecamatan')->pluck('c', 'kec')->all();

        // 2. PBG terbit (SK Terbit) per kec - via pbg_task linked to detected_buildings
        $pbgQ = DB::table('detected_buildings as db')
            ->join('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
            ->selectRaw('db.kecamatan AS kec, COUNT(*) AS c')
            ->whereIn('db.kecamatan', self::BANDUNG_SELATAN_DISTRICTS)
            ->where('pt.status', 20);
        if ($minArea > 0) $pbgQ->where('db.estimated_area_m2', '>=', $minArea);
        $pbgTerbit = $pbgQ->groupBy('db.kecamatan')->pluck('c', 'kec')->all();

        // 3. PBG proses + ditolak per kec
        $pbgProsesQ = DB::table('detected_buildings as db')
            ->join('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
            ->selectRaw('db.kecamatan AS kec, COUNT(*) AS c')
            ->whereIn('db.kecamatan', self::BANDUNG_SELATAN_DISTRICTS)
            ->whereNotIn('pt.status', [3, 9, 20, 22]);
        if ($minArea > 0) $pbgProsesQ->where('db.estimated_area_m2', '>=', $minArea);
        $pbgProses = $pbgProsesQ->groupBy('db.kecamatan')->pluck('c', 'kec')->all();

        // 4. PBB terbangun per kec (pre-aggregated lookup)
        $pbb = DB::table('pbb_kecamatan_lookup')
            ->selectRaw('UPPER(kecamatan_name) AS kec, terbangun_count AS c, nop_count AS total')
            ->get()
            ->keyBy(fn ($r) => $r->kec);

        // 5. Merge per-kec rows
        $rows = [];
        $totals = [
            'sat_count' => 0,
            'pbb_terbangun' => 0,
            'pbb_total_nop' => 0,
            'pbg_terbit' => 0,
            'pbg_proses' => 0,
            'tidak_berizin' => 0,
        ];

        foreach (self::BANDUNG_SELATAN_DISTRICTS as $kec) {
            $kecUpper = strtoupper($kec);
            $satC     = (int) ($sat[$kec] ?? 0);
            $pbgC     = (int) ($pbgTerbit[$kec] ?? 0);
            $prosesC  = (int) ($pbgProses[$kec] ?? 0);
            $pbbRow   = $pbb[$kecUpper] ?? null;
            $pbbTb    = $pbbRow ? (int) $pbbRow->c : 0;
            $pbbTotal = $pbbRow ? (int) $pbbRow->total : 0;
            $tidak    = max(0, $satC - $pbgC); // satelit tanpa SK PBG terbit

            $rows[] = [
                'kecamatan'       => $kec,
                'sat_count'       => $satC,
                'pbb_terbangun'   => $pbbTb,
                'pbb_total_nop'   => $pbbTotal,
                'pbg_terbit'      => $pbgC,
                'pbg_proses'      => $prosesC,
                'tidak_berizin'   => $tidak,
                'rasio_berizin'   => $satC > 0 ? round($pbgC / $satC * 100, 2) : 0,
            ];

            $totals['sat_count']     += $satC;
            $totals['pbb_terbangun'] += $pbbTb;
            $totals['pbb_total_nop'] += $pbbTotal;
            $totals['pbg_terbit']    += $pbgC;
            $totals['pbg_proses']    += $prosesC;
            $totals['tidak_berizin'] += $tidak;
        }

        usort($rows, fn ($a, $b) => $b['tidak_berizin'] <=> $a['tidak_berizin']);

        $totals['rasio_berizin'] = $totals['sat_count'] > 0
            ? round($totals['pbg_terbit'] / $totals['sat_count'] * 100, 2)
            : 0;
        $totals['rasio_terdaftar_pbb'] = $totals['sat_count'] > 0
            ? round(min($totals['pbb_terbangun'], $totals['sat_count']) / $totals['sat_count'] * 100, 2)
            : 0;

        return [
            'scope' => 'bandung_selatan_31_kec',
            'min_area' => $minArea,
            'totals' => $totals,
            'per_kec' => $rows,
            'computed_at' => now()->toIso8601String(),
        ];
    }
}
