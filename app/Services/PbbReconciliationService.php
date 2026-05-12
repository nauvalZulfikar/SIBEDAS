<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PbbReconciliationService
{
    private const CACHE_TTL_SEC = 3600;
    private const CACHE_KEY_PREFIX = 'pbb_recon_v1:';

    public function getKabSummary(): array
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . 'kab', self::CACHE_TTL_SEC, function () {
            return $this->fetchKabSummary();
        });
    }

    public function getPerKec(): array
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . 'per_kec', self::CACHE_TTL_SEC, function () {
            return $this->fetchPerKec();
        });
    }

    public function getPerKelurahan(string $kecName): array
    {
        $kec = strtoupper(trim($kecName));
        return Cache::remember(
            self::CACHE_KEY_PREFIX . "per_kel:{$kec}",
            self::CACHE_TTL_SEC,
            fn () => $this->fetchPerKelurahan($kec)
        );
    }

    public function getNopWithoutSatellite(int $limit = 100, int $offset = 0): array
    {
        // PBB record terbangun yang tidak ada satellite di dalam kec yang sama
        // (proxy: pbb_record_id is null on detected_buildings AND PBB->kec sat count is 0)
        // Definisi pasti hanya bisa setelah Phase 7 spatial-join. For now: count
        // PBB terbangun di kec yang ratio sat<<pbb (suspicious demolished/over-registered).
        $rows = DB::table('pbb_records')
            ->select(['nop', 'nama_wp', 'alamat', 'kecamatan_name', 'kelurahan_name', 'luas_bangunan'])
            ->where('luas_bangunan', '>', 0)
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return [
            'data' => $rows,
            'note' => 'Per-record matching depends on Phase 7 (spatial join). Currently lists all terbangun NOPs.',
        ];
    }

    public function getSatelliteWithoutNop(int $limit = 100, int $offset = 0): array
    {
        $rows = DB::table('detected_buildings')
            ->select(['id', 'latitude', 'longitude', 'estimated_area_m2', 'building_district_name'])
            ->whereNull('pbb_record_id')
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return [
            'data' => $rows,
            'count_estimate' => DB::table('detected_buildings')->whereNull('pbb_record_id')->count(),
            'note' => 'Sebelum Phase 7 (spatial join), semua satellite buildings unmapped (1.18jt). After Phase 7, ini = kandidat ilegal.',
        ];
    }

    public function recompute(): array
    {
        $started = microtime(true);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('reconciliation_summary')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Kab-level row
        $kab = $this->fetchKabSummary();
        DB::table('reconciliation_summary')->insert([
            'scope' => 'kab',
            'pbb_total' => $kab['pbb_total'],
            'pbb_terbangun' => $kab['pbb_terbangun'],
            'pbb_lahan_kosong' => $kab['pbb_lahan_kosong'],
            'sat_count' => $kab['sat_count'],
            'pbg_terbit_count' => $kab['pbg_terbit_count'],
            'gap_sat_minus_terbangun' => $kab['gap_sat_minus_terbangun'],
            'gap_pct' => $kab['gap_pct'],
            'pbb_lb_m2' => $kab['pbb_lb_m2'],
            'sat_area_m2' => $kab['sat_area_m2'],
            'computed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kec-level rows
        foreach ($this->fetchPerKec() as $r) {
            DB::table('reconciliation_summary')->insert([
                'scope' => 'kec',
                'kecamatan_name' => $r['kecamatan'],
                'pbb_total' => $r['pbb_total'],
                'pbb_terbangun' => $r['pbb_terbangun'],
                'pbb_lahan_kosong' => $r['pbb_lahan_kosong'],
                'sat_count' => $r['sat_count'],
                'pbg_terbit_count' => $r['pbg_terbit_count'] ?? 0,
                'gap_sat_minus_terbangun' => $r['gap'],
                'gap_pct' => $r['gap_pct'],
                'pbb_lb_m2' => $r['pbb_lb_m2'],
                'sat_area_m2' => $r['sat_area_m2'],
                'computed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Kelurahan-level rows (per kec, batched). Phase 7 wires real satellite
        // counts where polygon coverage exists; otherwise falls back to 0.
        foreach ($this->fetchAllKelurahanRows() as $r) {
            DB::table('reconciliation_summary')->insert([
                'scope' => 'kelurahan',
                'kecamatan_name' => $r['kecamatan'],
                'kelurahan_name' => $r['kelurahan'],
                'pbb_total' => $r['pbb_total'],
                'pbb_terbangun' => $r['pbb_terbangun'],
                'pbb_lahan_kosong' => $r['pbb_lahan_kosong'],
                'sat_count' => $r['sat_count'],
                'pbg_terbit_count' => 0,
                'gap_sat_minus_terbangun' => $r['gap'],
                'gap_pct' => $r['gap_pct'],
                'pbb_lb_m2' => $r['pbb_lb_m2'],
                'sat_area_m2' => $r['sat_area_m2'],
                'computed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Bust caches — per_kel:* dibust juga supaya UI lihat hasil baru segera
        Cache::forget(self::CACHE_KEY_PREFIX . 'kab');
        Cache::forget(self::CACHE_KEY_PREFIX . 'per_kec');
        $kecNames = DB::table('pbb_kecamatan_lookup')->pluck('kecamatan_name');
        foreach ($kecNames as $kn) {
            Cache::forget(self::CACHE_KEY_PREFIX . 'per_kel:' . strtoupper(trim($kn)));
        }

        $rows = DB::table('reconciliation_summary')->count();
        $elapsed = round((microtime(true) - $started) * 1000);

        return [
            'rows_inserted' => $rows,
            'elapsed_ms' => $elapsed,
            'computed_at' => now()->toIso8601String(),
        ];
    }

    // ===== Private fetchers =====

    private function fetchKabSummary(): array
    {
        $pbb = DB::table('pbb_kecamatan_lookup')
            ->selectRaw('
                COALESCE(SUM(nop_count), 0) AS total,
                COALESCE(SUM(terbangun_count), 0) AS terbangun,
                COALESCE(SUM(sum_luas_bangunan_m2), 0) AS lb_m2
            ')
            ->first();

        $sat = DB::table('detected_buildings')
            ->selectRaw('COUNT(*) c, COALESCE(SUM(estimated_area_m2),0) area')
            ->first();

        $pbgTerbit = DB::table('spatial_plannings')->where('is_terbit', 1)->count();

        $terbangun = (int) $pbb->terbangun;
        $satCount = (int) $sat->c;
        $gap = $satCount - $terbangun;
        $gapPct = $satCount > 0 ? round(($gap / $satCount) * 100, 2) : null;

        return [
            'pbb_total' => (int) $pbb->total,
            'pbb_terbangun' => $terbangun,
            'pbb_lahan_kosong' => (int) $pbb->total - $terbangun,
            'sat_count' => $satCount,
            'pbg_terbit_count' => $pbgTerbit,
            'gap_sat_minus_terbangun' => $gap,
            'gap_pct' => $gapPct,
            'pbb_lb_m2' => (int) $pbb->lb_m2,
            'sat_area_m2' => (int) $sat->area,
        ];
    }

    private function fetchPerKec(): array
    {
        $pbb = DB::table('pbb_kecamatan_lookup')
            ->select([
                'djp_code',
                'kecamatan_name',
                'nop_count',
                'terbangun_count',
                'sum_luas_bangunan_m2',
            ])
            ->orderBy('kecamatan_name')
            ->get()
            ->keyBy(fn ($r) => strtoupper($r->kecamatan_name));

        $satRaw = DB::table('detected_buildings')
            ->selectRaw('UPPER(building_district_name) name, COUNT(*) c, COALESCE(SUM(estimated_area_m2),0) area')
            ->whereNotNull('building_district_name')
            ->groupBy(DB::raw('UPPER(building_district_name)'))
            ->get();
        $sat = $satRaw->keyBy('name');

        $rows = [];
        foreach ($pbb as $kec => $r) {
            $satEntry = $sat->get($kec);
            $satCount = $satEntry ? (int) $satEntry->c : 0;
            $satArea = $satEntry ? (int) $satEntry->area : 0;
            $terbangun = (int) $r->terbangun_count;
            $total = (int) $r->nop_count;
            $gap = $satCount - $terbangun;
            $rows[] = [
                'kecamatan' => $r->kecamatan_name,
                'djp_code' => $r->djp_code,
                'pbb_total' => $total,
                'pbb_terbangun' => $terbangun,
                'pbb_lahan_kosong' => $total - $terbangun,
                'sat_count' => $satCount,
                'sat_area_m2' => $satArea,
                'gap' => $gap,
                'gap_pct' => $satCount > 0 ? round(($gap / $satCount) * 100, 2) : null,
                'pbb_lb_m2' => (int) $r->sum_luas_bangunan_m2,
            ];
        }
        return $rows;
    }

    private function fetchPerKelurahan(string $kec): array
    {
        $satMap = $this->satCountByKelurahan($kec);

        $rows = DB::table('pbb_kelurahan_lookup as kl')
            ->join('pbb_kecamatan_lookup as k', 'k.djp_code', '=', 'kl.djp_kec_code')
            ->where('k.kecamatan_name', $kec)
            ->select([
                'kl.djp_kec_code',
                'kl.djp_desa_code',
                'kl.kelurahan_name',
                'kl.nop_count',
                'kl.terbangun_count',
                'kl.sum_luas_bangunan_m2',
                'k.kecamatan_name',
            ])
            ->orderBy('kl.kelurahan_name')
            ->get()
            ->map(function ($r) use ($satMap) {
                $kelKey = strtoupper(trim($r->kelurahan_name));
                $satEntry = $satMap[$kelKey] ?? null;
                $satCount = $satEntry['count'] ?? 0;
                $satArea = $satEntry['area'] ?? 0;
                $hasPolygon = $satEntry !== null;
                $terbangun = (int) $r->terbangun_count;
                $gap = $hasPolygon ? ($satCount - $terbangun) : null;

                return [
                    'kecamatan' => $r->kecamatan_name,
                    'kelurahan' => $r->kelurahan_name,
                    'djp_kec_code' => $r->djp_kec_code,
                    'djp_desa_code' => $r->djp_desa_code,
                    'pbb_total' => (int) $r->nop_count,
                    'pbb_terbangun' => $terbangun,
                    'pbb_lahan_kosong' => (int) $r->nop_count - $terbangun,
                    'pbb_lb_m2' => (int) $r->sum_luas_bangunan_m2,
                    'sat_count' => $satCount,
                    'sat_area_m2' => $satArea,
                    'gap' => $gap,
                    'gap_pct' => ($hasPolygon && $satCount > 0) ? round(($gap / $satCount) * 100, 2) : null,
                    'coverage_status' => $hasPolygon ? 'covered' : 'pending_polygon',
                ];
            })
            ->all();

        return $rows;
    }

    /**
     * Per-kelurahan satellite aggregation. Buildings are tagged via point-in-polygon
     * (scripts/populate_kelurahan.py) using available OSM kelurahan boundaries.
     * Coverage is partial — kelurahan without an OSM polygon return null.
     * Key = UPPER(kelurahan_name).
     */
    private function satCountByKelurahan(string $kec): array
    {
        $kecTitle = ucwords(strtolower(trim($kec)));
        $rows = DB::table('detected_buildings')
            ->selectRaw('UPPER(building_ward_name) as kel, COUNT(*) as c, COALESCE(SUM(estimated_area_m2),0) as a')
            ->where('kecamatan', $kecTitle)
            ->whereNotNull('building_ward_name')
            ->where('building_ward_name', '!=', '')
            ->groupBy(DB::raw('UPPER(building_ward_name)'))
            ->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->kel] = ['count' => (int) $r->c, 'area' => (int) $r->a];
        }
        return $map;
    }

    private function fetchAllKelurahanRows(): \Generator
    {
        // Pre-aggregate satellite per (UPPER(kec), UPPER(kel)) once
        $satRows = DB::table('detected_buildings')
            ->selectRaw('UPPER(kecamatan) as kec, UPPER(building_ward_name) as kel, COUNT(*) as c, COALESCE(SUM(estimated_area_m2),0) as a')
            ->whereNotNull('building_ward_name')
            ->where('building_ward_name', '!=', '')
            ->groupBy(DB::raw('UPPER(kecamatan)'), DB::raw('UPPER(building_ward_name)'))
            ->get();
        $satMap = [];
        foreach ($satRows as $r) {
            $satMap[$r->kec][$r->kel] = ['count' => (int) $r->c, 'area' => (int) $r->a];
        }

        $rows = DB::table('pbb_kelurahan_lookup as kl')
            ->join('pbb_kecamatan_lookup as k', 'k.djp_code', '=', 'kl.djp_kec_code')
            ->select([
                'kl.djp_kec_code',
                'kl.djp_desa_code',
                'kl.kelurahan_name',
                'kl.nop_count',
                'kl.terbangun_count',
                'kl.sum_luas_bangunan_m2',
                'k.kecamatan_name',
            ])
            ->cursor();

        foreach ($rows as $r) {
            $kecKey = strtoupper($r->kecamatan_name);
            $kelKey = strtoupper($r->kelurahan_name);
            $sat = $satMap[$kecKey][$kelKey] ?? null;
            $satCount = $sat['count'] ?? 0;
            $satArea = $sat['area'] ?? 0;
            $terbangun = (int) $r->terbangun_count;
            $gap = $sat !== null ? ($satCount - $terbangun) : 0;
            yield [
                'kecamatan' => $r->kecamatan_name,
                'kelurahan' => $r->kelurahan_name,
                'pbb_total' => (int) $r->nop_count,
                'pbb_terbangun' => $terbangun,
                'pbb_lahan_kosong' => (int) $r->nop_count - $terbangun,
                'pbb_lb_m2' => (int) $r->sum_luas_bangunan_m2,
                'sat_count' => $satCount,
                'sat_area_m2' => $satArea,
                'gap' => $gap,
                'gap_pct' => ($sat !== null && $satCount > 0) ? round(($gap / $satCount) * 100, 2) : null,
                'has_polygon' => $sat !== null,
            ];
        }
    }
}
