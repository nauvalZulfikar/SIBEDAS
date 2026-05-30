<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Isi detected_buildings.actual_area_m2.
 *
 * KOREKSI 2026-05-30 — faktor fill 0,62 DIBATALKAN.
 * Asumsi awal "estimated_area_m2 = bounding box yang kegedean 30-60%" TERBUKTI
 * SALAH. Verifikasi (cocok 400 bangunan ke footprint Microsoft asli dari sumber
 * GlobalMLBuildingFootprints): estimated_area_m2 SUDAH = luas polygon asli —
 *   rasio tersimpan / luas_polygon_asli = 1,007  (cocok)
 *   rasio tersimpan / luas_bbox_kotak   = 0,637  (TIDAK cocok)
 * → khusus pada bangunan kompleks (>5 titik) tersimpan ikut polygon, bukan kotak.
 * Karena itu Microsoft TIDAK dikoreksi (factor default = 1.0). Mengalikan 0,62
 * justru bikin luas 38% terlalu kecil.
 *
 *   - Microsoft footprint  → estimated_area_m2 × factor (default 1.0 = apa adanya).
 *   - OSM polygon nyata (>5 titik) → ST_Area(UTM48S) presisi dari PostGIS
 *     (≈ identik dgn estimated; tetap dihitung utk akurasi maksimal bentuk kompleks).
 *   - OSM kotak (≤5 titik) → NULL; konsumen pakai COALESCE(actual, estimated).
 *
 * area_suspect = footprint raksasa (>threshold) yg perlu review manual — mis.
 * id 1588 = 51.949 m². Di sumber Microsoft pun segitu (bukan artefak bbox),
 * jadi ini anomali DATA SUMBER (ML merge/struktur besar), bukan kesalahan ukur.
 */
class RecomputeBuildingArea extends Command
{
    protected $signature = 'buildings:recompute-area
        {--chunk=2000 : Jumlah id per batch update OSM}
        {--suspect-threshold=10000 : Ambang luas (m²) utk flag area_suspect (review manual)}
        {--factor=1.0 : Pengali luas Microsoft (default 1.0 = apa adanya; faktor 0,62 lama TERBUKTI salah)}';

    protected $description = 'Hitung detected_buildings.actual_area_m2 (Microsoft apa adanya + ST_Area polygon OSM nyata + flag suspect).';

    public function handle(): int
    {
        $t0 = microtime(true);
        $factor = (float) $this->option('factor');
        $threshold = (float) $this->option('suspect-threshold');
        $chunk = max(200, (int) $this->option('chunk'));

        // Sanity: PostGIS reachable?
        try {
            DB::connection('postgis')->select('SELECT 1');
        } catch (\Throwable $e) {
            $this->error('PostGIS (connection "postgis") tak terjangkau: '.$e->getMessage());
            $this->error('Set POSTGIS_* di .env & pastikan container sibedas_postgis_local up.');
            return self::FAILURE;
        }

        // ── 1) Microsoft footprints: aritmetika murni, satu bulk UPDATE ──────
        $this->info("Fase 1/2 — Microsoft footprints × factor {$factor} …");
        $ms = DB::table('detected_buildings')
            ->where('detection_source', 'microsoft_footprints')
            ->update([
                'actual_area_m2' => DB::raw("ROUND(estimated_area_m2 * {$factor}, 2)"),
                'area_suspect'   => DB::raw("CASE WHEN estimated_area_m2 > {$threshold} THEN 1 ELSE 0 END"),
            ]);
        $this->line("  ✓ {$ms} baris Microsoft di-set (suspect bila > {$threshold} m²).");

        // ── 2) OSM polygon nyata (>5 titik): ST_Area asli dari PostGIS ───────
        // area_m2 di PostGIS sudah = luas polygon sebenarnya; kita recompute
        // ulang via UTM48S biar konsisten dgn penurunan faktor.
        $this->info('Fase 2/2 — OSM polygon nyata (>5 titik) via PostGIS ST_Area(UTM48S) …');
        $pg = DB::connection('postgis');
        $totalOsm = (int) $pg->scalar(
            "SELECT count(*) FROM public.buildings WHERE source='osm_buildings' AND ST_NPoints(geom) > 5"
        );
        $this->line("  {$totalOsm} polygon OSM nyata akan di-recompute.");

        $bar = $this->output->createProgressBar($totalOsm);
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s%/%estimated:-6s% | %message%");
        $bar->setMessage('mulai…');
        $bar->start();

        $lastId = 0;
        $done = 0;
        $updated = 0;
        while (true) {
            $rows = $pg->select(
                "SELECT id, ROUND(ST_Area(ST_Transform(geom,32748))::numeric, 2) AS area
                 FROM public.buildings
                 WHERE source='osm_buildings' AND ST_NPoints(geom) > 5 AND id > ?
                 ORDER BY id LIMIT ?",
                [$lastId, $chunk]
            );
            if (empty($rows)) break;

            // Bulk UPDATE per chunk via CASE — hindari 231rb update satuan.
            $ids = [];
            $cases = [];
            foreach ($rows as $r) {
                $id = (int) $r->id;
                $ids[] = $id;
                $cases[] = "WHEN {$id} THEN ".(float) $r->area;
                $lastId = $id;
            }
            $idList = implode(',', $ids);
            $caseSql = implode(' ', $cases);
            $updated += DB::statement(
                "UPDATE detected_buildings SET actual_area_m2 = CASE id {$caseSql} END WHERE id IN ({$idList})"
            ) ? count($ids) : 0;

            $done += count($rows);
            $bar->setProgress(min($done, $totalOsm));
            if ($done % ($chunk * 10) < $chunk) {
                $rate = $done / max(0.001, microtime(true) - $t0);
                $bar->setMessage(sprintf('%.0f baris/s', $rate));
            }
        }
        $bar->setMessage('selesai');
        $bar->finish();
        $this->newLine(2);

        $suspect = DB::table('detected_buildings')->where('area_suspect', true)->count();
        $this->info(sprintf(
            'Done %.1fs · Microsoft=%d · OSM-recompute=%d · area_suspect=%d (OSM box dibiarkan NULL → fallback estimated).',
            microtime(true) - $t0, $ms, $done, $suspect
        ));
        return self::SUCCESS;
    }
}
