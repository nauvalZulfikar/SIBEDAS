<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mirror MySQL detected_buildings → PostGIS buildings.
 *
 *   php artisan buildings:sync-postgis [--limit=N] [--chunk=5000] [--from-id=0] [--via=auto|pdo|stdout]
 *
 * Modes:
 *   pdo    — uses Laravel DB::connection('postgis'). Requires pdo_pgsql.
 *            Canonical production path (inside the app Docker image).
 *   stdout — prints batched SQL to STDOUT. Pipe through psql for environments
 *            without pdo_pgsql:
 *
 *     php artisan buildings:sync-postgis --limit=100 --via=stdout 2>$null \
 *       | docker exec -i sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial
 *
 *   auto   — picks pdo if extension loaded, else stdout.
 *
 * Idempotent: rows are upserted on conflict (id). Re-runs only refresh
 * geometry + status_color + updated_at; no duplicates.
 */
class SyncBuildingsToPostgis extends Command
{
    protected $signature = 'buildings:sync-postgis
                            {--limit= : Cap total rows processed (testing)}
                            {--chunk=5000 : MySQL read batch size}
                            {--insert-batch=500 : PostGIS upsert batch size}
                            {--from-id=0 : Resume from this primary key (exclusive)}
                            {--via=auto : auto|pdo|stdout — output backend}';

    protected $description = 'Mirror detected_buildings (MySQL) into the PostGIS buildings table';

    private string $mode;

    public function handle(): int
    {
        $this->mode = $this->resolveMode();
        $chunk = (int) $this->option('chunk');
        $insertBatch = (int) $this->option('insert-batch');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $fromId = (int) $this->option('from-id');

        if ($this->mode === 'pdo') {
            $this->info("Mode: pdo (writing via DB::connection('postgis'))");
        } else {
            // For stdout mode, normal info/warn lines go to STDERR so the
            // SQL on STDOUT stays pipe-clean.
            fwrite(STDERR, "Mode: stdout (printing SQL to STDOUT — pipe to psql)\n");
        }

        $mysql = DB::connection('mysql');

        $countQuery = $mysql->table('detected_buildings')->where('id', '>', $fromId);
        $available = $countQuery->count();
        $total = $limit ? min($limit, $available) : $available;
        $this->emit("Total to sync: {$total} (chunk={$chunk}, insert_batch={$insertBatch})");

        $bar = $this->mode === 'pdo' ? $this->output->createProgressBar($total) : null;
        if ($bar) $bar->start();

        $processed = 0;
        $synced = 0;
        $skipped = 0;
        $start = microtime(true);
        $batch = [];

        // Manual chunked pagination keyed by id (chunk() ignores ->limit()
        // and re-issues queries with offset, so we step by last-seen id and
        // break when we hit --limit).
        $lastId = $fromId;
        while ($processed < $total) {
            $remaining = $total - $processed;
            $take = min($chunk, $remaining);
            // LEFT JOIN pbg_task so statusColor() can emit the 4-state
            // colour palette (terbit / proses / ditolak / tanpa_izin) that
            // matches the cluster-mode dot legend, instead of the previous
            // binary red/green orphan-vs-matched.
            $rows = $mysql->table('detected_buildings as db')
                ->leftJoin('pbg_task as pt', 'pt.id', '=', 'db.matched_pbg_task_id')
                ->select(
                    'db.id', 'db.latitude', 'db.longitude', 'db.estimated_area_m2',
                    'db.detection_source', 'db.geometry_geojson', 'db.matched_pbg_task_id',
                    'db.verification_status', 'db.building_district_name', 'db.building_ward_name',
                    'pt.status as pbg_status'
                )
                ->where('db.id', '>', $lastId)
                ->orderBy('db.id')
                ->limit($take)
                ->get();
            if ($rows->isEmpty()) break;

            foreach ($rows as $row) {
                $processed++;
                $lastId = (int) $row->id;
                $entry = $this->buildEntry($row);
                if (!$entry) {
                    $skipped++;
                    if ($bar) $bar->advance();
                    continue;
                }
                $batch[] = $entry;
                if (count($batch) >= $insertBatch) {
                    $this->flush($batch);
                    $synced += count($batch);
                    $batch = [];
                }
                if ($bar) $bar->advance();
            }
        }

        if (!empty($batch)) {
            $this->flush($batch);
            $synced += count($batch);
        }

        if ($bar) {
            $bar->finish();
            $this->newLine();
        }
        $elapsed = round(microtime(true) - $start, 2);
        $this->emit("Done. processed={$processed}, synced={$synced}, skipped={$skipped}, elapsed={$elapsed}s");

        return self::SUCCESS;
    }

    private function resolveMode(): string
    {
        $via = $this->option('via');
        if ($via === 'pdo') return 'pdo';
        if ($via === 'stdout') return 'stdout';
        // auto
        return extension_loaded('pdo_pgsql') ? 'pdo' : 'stdout';
    }

    private function emit(string $msg): void
    {
        if ($this->mode === 'pdo') {
            $this->info($msg);
        } else {
            fwrite(STDERR, $msg . "\n");
        }
    }

    private function buildEntry(object $row): ?array
    {
        $lat = (float) $row->latitude;
        $lng = (float) $row->longitude;
        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) return null;

        if (!empty($row->geometry_geojson) && $this->looksLikePolygon($row->geometry_geojson)) {
            // Trust the stored GeoJSON (OSM ingest path writes valid polygons here)
            $geomSql = 'ST_GeomFromGeoJSON(' . $this->literal($row->geometry_geojson) . ')';
        } else {
            $area = max((float) ($row->estimated_area_m2 ?? 100), 1);
            $halfM = sqrt($area) / 2;
            $dLat = $halfM / 110574;
            $dLng = $halfM / (111320 * max(cos(deg2rad($lat)), 0.0001));
            $geomSql = sprintf(
                'ST_MakeEnvelope(%.7F, %.7F, %.7F, %.7F, 4326)',
                $lng - $dLng, $lat - $dLat, $lng + $dLng, $lat + $dLat
            );
        }

        $centroidSql = sprintf('ST_SetSRID(ST_MakePoint(%.7F, %.7F), 4326)', $lng, $lat);
        $statusColor = $this->statusColor($row);

        return [
            'tuple' => sprintf(
                '(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())',
                (int) $row->id,
                $geomSql,
                $centroidSql,
                $this->literal($row->detection_source),
                $this->literal($row->verification_status),
                $this->literal($row->building_district_name),
                $this->literal($row->building_ward_name),
                $row->matched_pbg_task_id !== null ? (int) $row->matched_pbg_task_id : 'NULL',
                $row->estimated_area_m2 !== null ? sprintf('%.2F', (float) $row->estimated_area_m2) : 'NULL',
                $this->literal($statusColor),
            ),
        ];
    }

    private function looksLikePolygon(string $json): bool
    {
        return stripos($json, '"polygon"') !== false || stripos($json, '"multipolygon"') !== false;
    }

    /**
     * 4-state colour palette aligned with the cluster-mode dot legend in
     * resources/views/dashboards/satellite-monitoring.blade.php (STATE_COLOR).
     * PBG status codes come from the join on pbg_task.status:
     *   - 20            → terbit (SK Terbit, green)
     *   - 3, 9, 22      → ditolak (red-grey)
     *   - other non-null → proses (yellow)
     *   - NULL match     → tanpa_izin (red)
     */
    private function statusColor(object $row): string
    {
        if (!$row->matched_pbg_task_id) return '#ef4444';     // tanpa_izin
        $s = $row->pbg_status ?? null;
        if ($s === null)                  return '#ef4444';   // orphan FK → treat as tanpa_izin
        $s = (int) $s;
        if ($s === 20)                    return '#22c55e';   // terbit
        if (in_array($s, [3, 9, 22], true)) return '#6b7280'; // ditolak
        return '#f59e0b';                                      // proses
    }

    /**
     * SQL literal escaping for stdout mode. Single-quote standard, ' doubled.
     * Returns 'NULL' for null. Postgres-safe for the trusted column values we
     * read out of MySQL (no untrusted user input goes through this path).
     */
    private function literal($value): string
    {
        if ($value === null) return 'NULL';
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function flush(array $batch): void
    {
        $tuples = array_map(fn ($e) => $e['tuple'], $batch);
        $sql = 'INSERT INTO buildings (id, geom, centroid, source, verification_status, district, ward, matched_pbg_task_id, area_m2, status_color, updated_at) VALUES '
             . implode(",\n", $tuples)
             . " ON CONFLICT (id) DO UPDATE SET "
             . "geom = EXCLUDED.geom, "
             . "centroid = EXCLUDED.centroid, "
             . "source = EXCLUDED.source, "
             . "verification_status = EXCLUDED.verification_status, "
             . "district = EXCLUDED.district, "
             . "ward = EXCLUDED.ward, "
             . "matched_pbg_task_id = EXCLUDED.matched_pbg_task_id, "
             . "area_m2 = EXCLUDED.area_m2, "
             . "status_color = EXCLUDED.status_color, "
             . "updated_at = NOW()";

        if ($this->mode === 'pdo') {
            DB::connection('postgis')->unprepared($sql . ';');
        } else {
            fwrite(STDOUT, $sql . ";\n");
        }
    }
}
