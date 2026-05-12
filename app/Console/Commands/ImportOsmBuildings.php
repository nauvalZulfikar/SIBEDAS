<?php

namespace App\Console\Commands;

use App\Models\DetectedBuilding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Supplemental ingestion from OpenStreetMap Overpass API to backfill
 * kecamatan that have zero/sparse satellite coverage. Queries OSM building=*
 * polygons within an explicit bbox, computes centroid + estimated area,
 * inserts as detection_source = 'osm_buildings'.
 */
class ImportOsmBuildings extends Command
{
    protected $signature = 'buildings:import-osm
                            {--bbox= : "south,west,north,east" — defaults to Cicalengka/Nagreg/Cikancung corridor}
                            {--chunk=2000 : Bulk insert chunk size}
                            {--district= : Tag detected_district_name on inserted rows (optional)}';

    protected $description = 'Backfill missing satellite coverage from OpenStreetMap Overpass API';

    private const DEFAULT_BBOX = '-7.10,107.79,-6.95,107.95'; // covers Cicalengka, Nagreg, Cikancung

    public function handle(): int
    {
        $bbox = $this->option('bbox') ?: self::DEFAULT_BBOX;
        $chunk = (int) $this->option('chunk');
        [$south, $west, $north, $east] = array_map('floatval', explode(',', $bbox));

        $query = "[out:json][timeout:180];(way[\"building\"]({$south},{$west},{$north},{$east});relation[\"building\"]({$south},{$west},{$north},{$east}););out geom;";

        $this->info("Fetching from Overpass API for bbox [{$south},{$west}] - [{$north},{$east}]...");
        $resp = Http::timeout(300)
            ->withHeaders(['User-Agent' => 'Sibedas-PBG/1.0'])
            ->asForm()
            ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);

        if (!$resp->successful()) {
            $this->error('Overpass error: ' . $resp->status() . ' ' . $resp->body());
            return self::FAILURE;
        }

        $payload = $resp->json();
        $elements = $payload['elements'] ?? [];
        $this->info('Received ' . count($elements) . ' elements');

        if (empty($elements)) {
            $this->warn('No buildings in bbox.');
            return self::SUCCESS;
        }

        $now = now()->format('Y-m-d H:i:s');
        $today = now()->toDateString();
        $district = $this->option('district');
        $batch = [];
        $imported = 0;

        foreach ($elements as $el) {
            $geom = $el['geometry'] ?? null;
            if (!$geom || !is_array($geom) || count($geom) < 3) {
                continue;
            }
            [$lat, $lng, $area] = $this->centroidAndArea($geom);
            if ($lat === null) continue;

            $batch[] = [
                'latitude' => $lat,
                'longitude' => $lng,
                'estimated_area_m2' => round($area, 2),
                'confidence_score' => null,
                'detection_source' => 'osm_buildings',
                'detection_date' => $today,
                'geometry_geojson' => json_encode([
                    'type' => 'Polygon',
                    'coordinates' => [array_map(fn ($p) => [$p['lon'], $p['lat']], $geom)],
                ]),
                'verification_status' => 'unverified',
                'building_district_name' => $district,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $chunk) {
                DB::table('detected_buildings')->insert($batch);
                $imported += count($batch);
                $batch = [];
                $this->line("  ... {$imported} imported");
            }
        }

        if (!empty($batch)) {
            DB::table('detected_buildings')->insert($batch);
            $imported += count($batch);
        }

        $this->info("OSM import complete: {$imported} buildings");
        return self::SUCCESS;
    }

    /**
     * Compute polygon centroid + planar area approximation in m².
     * Uses simple shoelace + lat-degree to meter conversion.
     */
    private function centroidAndArea(array $geom): array
    {
        $n = count($geom);
        if ($n < 3) return [null, null, 0.0];
        $cx = $cy = $area2 = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $cross = $geom[$i]['lon'] * $geom[$j]['lat'] - $geom[$j]['lon'] * $geom[$i]['lat'];
            $area2 += $cross;
            $cx += ($geom[$i]['lon'] + $geom[$j]['lon']) * $cross;
            $cy += ($geom[$i]['lat'] + $geom[$j]['lat']) * $cross;
        }
        if (abs($area2) < 1e-12) {
            // Fallback: simple average
            $sumLat = array_sum(array_column($geom, 'lat'));
            $sumLng = array_sum(array_column($geom, 'lon'));
            return [$sumLat / $n, $sumLng / $n, 0.0];
        }
        $cx /= 3 * $area2;
        $cy /= 3 * $area2;
        // Convert degrees² → m² (lat 1° ≈ 111320 m, lng 1° ≈ 111320 * cos(lat))
        $latRad = deg2rad($cy);
        $mPerDegLat = 111320.0;
        $mPerDegLng = 111320.0 * cos($latRad);
        $areaM2 = abs($area2) / 2 * $mPerDegLat * $mPerDegLng;
        return [$cy, $cx, $areaM2];
    }
}
