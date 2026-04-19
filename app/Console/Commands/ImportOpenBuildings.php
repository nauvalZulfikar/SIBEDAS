<?php

namespace App\Console\Commands;

use App\Models\DetectedBuilding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOpenBuildings extends Command
{
    protected $signature = 'buildings:import-open-buildings
                            {--source=google : Source: google or microsoft}
                            {--chunk=1000 : Insert chunk size}
                            {--min-confidence=0.65 : Minimum confidence score}';

    protected $description = 'Import building footprints from Google Open Buildings or Microsoft for Kab. Bandung area';

    private const LAT_MIN = -7.20;
    private const LAT_MAX = -6.80;
    private const LNG_MIN = 107.30;
    private const LNG_MAX = 107.80;

    public function handle(): int
    {
        $source = $this->option('source');
        $chunkSize = (int) $this->option('chunk');
        $minConfidence = (float) $this->option('min-confidence');

        $sourceName = $source === 'microsoft' ? 'microsoft_footprints' : 'google_open_buildings';
        $dataDir = storage_path('app/open-buildings');
        $localFile = $dataDir . ($source === 'microsoft' ? '/bandung_clean.csv' : '/bandung_buildings.csv');

        $this->info("=== Import {$source} Building Footprints ===");
        $this->info(sprintf('Bounding box: [%.2f, %.2f] to [%.2f, %.2f]', self::LAT_MIN, self::LNG_MIN, self::LAT_MAX, self::LNG_MAX));

        if (!file_exists($localFile)) {
            $this->error("File not found: {$localFile}");
            $this->info('');
            if ($source === 'google') {
                $this->info('Download via BigQuery (free tier):');
                $this->info('  SELECT latitude, longitude, area_in_meters, confidence, geometry');
                $this->info('  FROM `bigquery-public-data.open_buildings_v3.buildings`');
                $this->info(sprintf('  WHERE latitude BETWEEN %.2f AND %.2f', self::LAT_MIN, self::LAT_MAX));
                $this->info(sprintf('  AND longitude BETWEEN %.2f AND %.2f', self::LNG_MIN, self::LNG_MAX));
            } else {
                $this->info('Run: python3 scripts/filter_buildings.py');
                $this->info('Then: python3 scripts/import_buildings_direct.py');
            }
            return self::FAILURE;
        }

        $handle = fopen($localFile, 'r');
        $header = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $this->info('Columns: ' . implode(', ', $header));

        $latCol = array_search('latitude', $header);
        $lngCol = array_search('longitude', $header);
        $areaCol = array_search('area_in_meters', $header) ?: array_search('estimated_area_m2', $header);
        $confCol = array_search('confidence', $header) ?: array_search('confidence_score', $header);

        if ($latCol === false || $lngCol === false) {
            $this->error('CSV must have latitude and longitude columns');
            return self::FAILURE;
        }

        $existing = DetectedBuilding::where('detection_source', $sourceName)->count();
        if ($existing > 0 && $this->confirm("Delete {$existing} existing {$sourceName} records?", true)) {
            DetectedBuilding::where('detection_source', $sourceName)->delete();
        }

        $batch = [];
        $imported = 0;
        $now = now()->format('Y-m-d H:i:s');
        $today = now()->toDateString();

        while (($row = fgetcsv($handle)) !== false) {
            $lat = (float) ($row[$latCol] ?? 0);
            $lng = (float) ($row[$lngCol] ?? 0);

            if ($lat < self::LAT_MIN || $lat > self::LAT_MAX || $lng < self::LNG_MIN || $lng > self::LNG_MAX) continue;

            $confidence = $confCol !== false ? (float) ($row[$confCol] ?? 0) : null;
            if ($minConfidence > 0 && $confidence !== null && $confidence < $minConfidence) continue;

            $batch[] = [
                'latitude' => $lat,
                'longitude' => $lng,
                'estimated_area_m2' => $areaCol !== false ? (float) ($row[$areaCol] ?? 0) : null,
                'confidence_score' => $confidence,
                'detection_source' => $sourceName,
                'detection_date' => $today,
                'verification_status' => 'unverified',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $chunkSize) {
                DB::table('detected_buildings')->insert($batch);
                $imported += count($batch);
                $batch = [];
                if ($imported % 50000 === 0) $this->info("  ... {$imported} imported");
            }
        }

        if (!empty($batch)) {
            DB::table('detected_buildings')->insert($batch);
            $imported += count($batch);
        }

        fclose($handle);
        $this->info("Import complete: " . number_format($imported) . " rows");
        return self::SUCCESS;
    }
}
