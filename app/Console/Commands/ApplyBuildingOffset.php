<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Apply a uniform geo-offset to the building polygons stored in postgis.
 *
 *   php artisan buildings:apply-offset --east=-7 --north=-3
 *       (negative east  = shift the polygons westward;
 *        negative north = shift the polygons southward)
 *
 * Microsoft Building Footprints were derived from Bing imagery; when the
 * dashboard renders them over Google / Esri tiles, there's typically a
 * 5-15m offset because the basemaps don't share a reference grid. This
 * command translates the geometry column (geom + centroid) by the
 * supplied delta so the polygons visually snap onto Google tiles.
 *
 * State tracking: a single row in `building_offset_state` records the
 * total offset that has been applied so far. Subsequent runs apply only
 * the DELTA needed to reach the new target offset — so calling
 *   --east=-7 --north=-3
 * twice in a row is a no-op, not a 14m shift.
 *
 * Idempotent & reversible: pass --east=0 --north=0 to undo.
 */
class ApplyBuildingOffset extends Command
{
    protected $signature = 'buildings:apply-offset
                            {--east=0   : Eastward shift in metres (negative = west)}
                            {--north=0  : Northward shift in metres (negative = south)}
                            {--source=microsoft_footprints : Limit to one polygon source}
                            {--dry-run  : Show what would change without writing}';

    protected $description = 'Shift building polygons by a uniform (east, north) metre offset to fix basemap-vs-source misalignment';

    public function handle(): int
    {
        $target = [
            'east_m'  => (float) $this->option('east'),
            'north_m' => (float) $this->option('north'),
        ];
        $source = (string) $this->option('source');
        $dry = (bool) $this->option('dry-run');

        $this->ensureStateTable();

        $current = DB::connection('postgis')->selectOne(
            "SELECT east_m, north_m FROM building_offset_state WHERE source = ?",
            [$source]
        );
        $currentEast  = $current ? (float) $current->east_m  : 0.0;
        $currentNorth = $current ? (float) $current->north_m : 0.0;

        $delta = [
            'east_m'  => $target['east_m']  - $currentEast,
            'north_m' => $target['north_m'] - $currentNorth,
        ];

        $this->info("Source       : {$source}");
        $this->info("Current shift: east={$currentEast}m, north={$currentNorth}m");
        $this->info("Target shift : east={$target['east_m']}m, north={$target['north_m']}m");
        $this->info("Delta to app : east={$delta['east_m']}m, north={$delta['north_m']}m");

        if (abs($delta['east_m']) < 0.001 && abs($delta['north_m']) < 0.001) {
            $this->info('Already at target offset — no work to do.');
            return 0;
        }
        if ($dry) {
            $this->warn('--dry-run set; skipping the UPDATE.');
            return 0;
        }

        // Convert metres → degrees. Approximate but accurate enough for
        // <30 km shifts at Kab. Bandung latitude (~-7°).
        $lngDeg = $delta['east_m']  / (111_320 * cos(deg2rad(-7.05)));
        $latDeg = $delta['north_m'] / 111_320;

        $this->info('Translating geom + centroid…');
        $start = microtime(true);
        $rows = DB::connection('postgis')->update("
            UPDATE buildings
               SET geom = ST_Translate(geom, ?, ?),
                   centroid = ST_Translate(centroid, ?, ?),
                   updated_at = NOW()
             WHERE source = ?
        ", [$lngDeg, $latDeg, $lngDeg, $latDeg, $source]);
        $secs = round(microtime(true) - $start, 1);
        $this->info("  → {$rows} rows updated in {$secs}s");

        DB::connection('postgis')->statement("
            INSERT INTO building_offset_state (source, east_m, north_m, last_applied_at)
            VALUES (?, ?, ?, NOW())
            ON CONFLICT (source) DO UPDATE
            SET east_m = EXCLUDED.east_m,
                north_m = EXCLUDED.north_m,
                last_applied_at = EXCLUDED.last_applied_at
        ", [$source, $target['east_m'], $target['north_m']]);

        $this->info('Done. Hard-reload the dashboard tile cache to see the new alignment:');
        $this->line('  → docker exec sibedas_redis redis-cli FLUSHDB    # if redis tile cache enabled');
        return 0;
    }

    private function ensureStateTable(): void
    {
        DB::connection('postgis')->statement("
            CREATE TABLE IF NOT EXISTS building_offset_state (
                source           VARCHAR(64) PRIMARY KEY,
                east_m           DOUBLE PRECISION NOT NULL DEFAULT 0,
                north_m          DOUBLE PRECISION NOT NULL DEFAULT 0,
                last_applied_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ");
    }
}
