<?php

namespace App\Console\Commands;

use App\Models\DetectedBuilding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportGeeResults extends Command
{
    protected $signature = 'buildings:import-gee-results
                            {--file=storage/app/open-buildings/sentinel_changes.csv : Path to GEE export CSV}
                            {--chunk=500 : Insert chunk size}';

    protected $description = 'Import Google Earth Engine change detection results into detected_buildings';

    public function handle(): int
    {
        $filePath = $this->option('file');
        if (!str_starts_with($filePath, '/')) $filePath = base_path($filePath);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->info('Run: python3 scripts/gee_change_detection.py');
            return self::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        $header = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $latCol = array_search('latitude', $header);
        $lngCol = array_search('longitude', $header);
        $areaCol = array_search('estimated_area_m2', $header);
        $confCol = array_search('confidence', $header);

        $existing = DetectedBuilding::where('detection_source', 'sentinel_cv')->count();
        if ($existing > 0 && $this->confirm("Delete {$existing} existing sentinel_cv records?", true)) {
            DetectedBuilding::where('detection_source', 'sentinel_cv')->delete();
        }

        $batch = [];
        $imported = 0;
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            $lat = (float) ($row[$latCol] ?? 0);
            $lng = (float) ($row[$lngCol] ?? 0);
            if ($lat == 0 || $lng == 0) continue;

            $batch[] = [
                'latitude' => $lat, 'longitude' => $lng,
                'estimated_area_m2' => $areaCol !== false ? (float) ($row[$areaCol] ?? 0) : null,
                'confidence_score' => $confCol !== false ? (float) ($row[$confCol] ?? 0.5) : 0.5,
                'detection_source' => 'sentinel_cv', 'detection_date' => $now->toDateString(),
                'verification_status' => 'unverified', 'created_at' => $now, 'updated_at' => $now,
            ];

            if (count($batch) >= (int) $this->option('chunk')) {
                DB::table('detected_buildings')->insert($batch);
                $imported += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) { DB::table('detected_buildings')->insert($batch); $imported += count($batch); }
        fclose($handle);

        $this->info("Imported {$imported} change detection candidates.");
        return self::SUCCESS;
    }
}
