<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Isi detected_buildings.actual_area_m2 — estimasi luas yang lebih realistis
 * daripada estimated_area_m2 (bounding box).
 *
 * CATATAN PENTING (kenapa BUKAN recompute-from-polygon seperti brief awal):
 * footprint Microsoft TIDAK pernah di-ingest sebagai polygon — baik
 * detected_buildings.geometry_geojson (NULL semua) maupun PostGIS
 * public.buildings.geom (5-titik = bounding box). ST_Area atas kotak = luas
 * kotak (rasio recompute/estimated = 1,0014 → no-op). Jadi luas asli tak bisa
 * "dipulihkan". Sebagai gantinya:
 *
 *   - Microsoft footprint  → estimated_area_m2 × FILL_FACTOR (faktor isian
 *     bbox→footprint, diturunkan empiris dari 231.047 polygon OSM nyata:
 *     median ST_Area(poly)/ST_Area(bbox) = 0,6158 → dibulatkan 0,62).
 *   - OSM dgn polygon nyata (>5 titik) → ST_Area(UTM48S) sebenarnya (PostGIS).
 *   - OSM kotak (≤5 titik) → dibiarkan NULL; konsumen pakai
 *     COALESCE(actual_area_m2, estimated_area_m2) sebagai fallback legacy.
 *
 * area_suspect = bbox raksasa yang mustahil utk satu footprint (kemungkinan
 * blob ke-merge, mis. id 1588 = 51.949 m²) → masuk antrean review manual;
 * fill-factor seragam pun tak bisa membenarkannya.
 */
class RecomputeBuildingArea extends Command
{
    protected $signature = 'buildings:recompute-area
        {--chunk=2000 : Jumlah id per batch update OSM}
        {--suspect-threshold=10000 : Ambang luas bbox (m²) utk flag area_suspect}
        {--factor=0.62 : Fill-factor bbox→footprint utk Microsoft}';

    protected $description = 'Hitung detected_buildings.actual_area_m2 (fill-factor Microsoft + ST_Area polygon OSM nyata).';

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
