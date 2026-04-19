<?php

namespace App\Console\Commands;

use App\Models\DetectedBuilding;
use App\Models\PbgTaskDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MatchDetectedBuildings extends Command
{
    protected $signature = 'buildings:match
                            {--radius=50 : Match radius in meters}
                            {--source= : Only match from specific source}
                            {--rematch : Re-match already matched buildings}';

    protected $description = 'Cross-reference detected buildings with PBG task permits using spatial proximity';

    public function handle(): int
    {
        $radius = (int) $this->option('radius');
        $this->info("=== Building Permit Cross-Reference (radius: {$radius}m) ===");

        $pbgTasks = PbgTaskDetail::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)->where('longitude', '!=', 0)
            ->select('id', 'pbg_task_uid', 'latitude', 'longitude', 'building_district_name', 'building_ward_name')
            ->get();

        $this->info("PBG tasks with coordinates: {$pbgTasks->count()}");

        $cellSize = 0.01;
        $grid = [];
        foreach ($pbgTasks as $t) {
            $key = floor((float)$t->latitude / $cellSize) . ':' . floor((float)$t->longitude / $cellSize);
            $grid[$key][] = ['id' => $t->id, 'lat' => (float)$t->latitude, 'lng' => (float)$t->longitude,
                'district' => $t->building_district_name, 'ward' => $t->building_ward_name];
        }

        $query = DetectedBuilding::query();
        if ($this->option('source')) $query->where('detection_source', $this->option('source'));
        if (!$this->option('rematch')) $query->whereNull('matched_pbg_task_id');

        $total = $query->count();
        $this->info("Buildings to process: " . number_format($total));
        $matched = 0;

        $query->chunkById(500, function ($buildings) use ($grid, $radius, $cellSize, &$matched) {
            foreach ($buildings as $b) {
                $lat = (float) $b->latitude;
                $lng = (float) $b->longitude;
                $center = [floor($lat / $cellSize), floor($lng / $cellSize)];
                $best = null;
                $bestDist = PHP_FLOAT_MAX;

                for ($dLat = -2; $dLat <= 2; $dLat++) {
                    for ($dLng = -2; $dLng <= 2; $dLng++) {
                        $key = ($center[0] + $dLat) . ':' . ($center[1] + $dLng);
                        if (!isset($grid[$key])) continue;
                        foreach ($grid[$key] as $p) {
                            $d = DetectedBuilding::haversineDistance($lat, $lng, $p['lat'], $p['lng']);
                            if ($d <= $radius && $d < $bestDist) { $bestDist = $d; $best = $p; }
                        }
                    }
                }

                if ($best) {
                    $b->update([
                        'matched_pbg_task_id' => $best['id'],
                        'match_distance_m' => round($bestDist, 2),
                        'building_district_name' => $best['district'] ?? $b->building_district_name,
                        'building_ward_name' => $best['ward'] ?? $b->building_ward_name,
                    ]);
                    $matched++;
                }
            }
        });

        $this->info("Matched: " . number_format($matched) . " / " . number_format($total));
        return self::SUCCESS;
    }
}
