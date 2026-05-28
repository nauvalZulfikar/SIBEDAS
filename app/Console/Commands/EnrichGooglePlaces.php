<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EnrichGooglePlaces extends Command
{
    protected $signature = 'enrich:google-places
        {--batch=10 : Number of properties per batch}
        {--total=0 : Total properties to process (0 = unlimited)}
        {--order=retribution : Order by retribution or area}
        {--kecamatan= : Filter by kecamatan name}
        {--dry-run : Show what would be scraped without calling API}';

    protected $description = 'Enrich unenriched properties via Google Places API (New), batch by batch with failure detection';

    private const MONTHLY_CAP_PER_KEY = 5000;

    private array $apiKeys = [
        'AIzaSyAzGOWpzMBDExrT8FWR1RXbpDvir1IVQQ8',
        'AIzaSyAIBnXUc-g0BpTfgnQFO6cPT1R2145hh-A',
    ];

    private string $apiKey;
    private int $currentKeyIndex = 0;
    private int $successCount = 0;
    private int $emptyCount = 0;
    private int $failCount = 0;
    private int $totalProcessed = 0;

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $totalLimit = (int) $this->option('total');
        $order = $this->option('order');
        $kecamatan = $this->option('kecamatan');
        $dryRun = $this->option('dry-run');

        $orderCol = $order === 'area' ? 'best_area' : 'potensi_retribusi_rp';

        $this->ensureTrackingTable();
        $this->selectAvailableKey();

        $totalCap = $this->getTotalMonthlyRemaining();

        $this->info("=== Google Places Enrichment ===");
        $this->info("API keys: " . count($this->apiKeys));
        foreach ($this->apiKeys as $i => $key) {
            $used = $this->getMonthlyUsageForKey($key);
            $remaining = self::MONTHLY_CAP_PER_KEY - $used;
            $active = $i === $this->currentKeyIndex ? ' <-- ACTIVE' : '';
            $this->info("  Key #" . ($i + 1) . ": {$used}/" . self::MONTHLY_CAP_PER_KEY . " used | {$remaining} remaining{$active}");
        }

        if ($totalCap <= 0) {
            $this->error("ALL KEYS EXHAUSTED — 0 calls remaining this month. Wait for quota reset.");
            return 1;
        }

        if ($totalLimit <= 0 || $totalLimit > $totalCap) {
            $totalLimit = $totalCap;
            $this->warn("Total limit capped to {$totalLimit} (all keys combined remaining)");
        }

        $this->info("Batch size: {$batchSize} | Total limit: {$totalLimit} | Order: {$orderCol}");
        if ($kecamatan) $this->info("Kecamatan filter: {$kecamatan}");
        if ($dryRun) $this->warn("DRY RUN — no API calls will be made");

        $existingCount = DB::table('property_enrichment')->count();
        $this->info("Already enriched: {$existingCount}");

        $batchNum = 0;

        while (true) {
            $batchNum++;
            $query = DB::table('v_property_master')
                ->whereNotIn('id', DB::table('property_enrichment')->select('detected_building_id'))
                ->where('status_izin', 'Tidak Berizin')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            if ($kecamatan) {
                $query->where('kecamatan', 'LIKE', "%{$kecamatan}%");
            }

            $properties = $query->orderByDesc($orderCol)
                ->select('id', 'latitude', 'longitude', 'estimated_area_m2', 'kecamatan', 'potensi_retribusi_rp')
                ->limit($batchSize)
                ->get();

            if ($properties->isEmpty()) {
                $this->info("\nNo more properties to enrich.");
                break;
            }

            $this->newLine();
            $this->info("--- Batch #{$batchNum} ({$properties->count()} properties) ---");

            if ($dryRun) {
                foreach ($properties as $p) {
                    $this->line("  Would scrape: #{$p->id} | {$p->latitude},{$p->longitude} | {$p->kecamatan} | Rp " . number_format($p->potensi_retribusi_rp));
                }
                $this->totalProcessed += $properties->count();
            } else {
                $batchFails = 0;
                foreach ($properties as $p) {
                    if ($this->getMonthlyUsageForKey($this->apiKey) >= self::MONTHLY_CAP_PER_KEY) {
                        if (!$this->rotateKey()) {
                            $this->error("ALL KEYS EXHAUSTED mid-batch — STOPPING.");
                            $this->printSummary();
                            return 1;
                        }
                    }

                    $result = $this->scrapeOne($p);
                    $this->totalProcessed++;

                    if ($result === 'fail') {
                        $batchFails++;
                        if ($batchFails >= 3) {
                            $this->error("3+ failures in this batch — STOPPING to preserve credits.");
                            $this->printSummary();
                            return 1;
                        }
                    }
                }

                $this->info("  Batch result: success={$this->successCount} empty={$this->emptyCount} fail={$this->failCount}");
            }

            if ($totalLimit > 0 && $this->totalProcessed >= $totalLimit) {
                $this->info("\nReached total limit of {$totalLimit}.");
                break;
            }

            if (!$dryRun) {
                usleep(200000); // 200ms between batches
            }
        }

        $this->printSummary();
        return 0;
    }

    private function scrapeOne(object $property): string
    {
        $url = 'https://places.googleapis.com/v1/places:searchNearby';
        $body = [
            'maxResultCount' => 1,
            'locationRestriction' => [
                'circle' => [
                    'center' => [
                        'latitude' => (float) $property->latitude,
                        'longitude' => (float) $property->longitude,
                    ],
                    'radius' => 25.0,
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.displayName,places.types,places.formattedAddress,places.businessStatus,places.rating',
            ])->timeout(10)->post($url, $body);

            $this->trackApiCall($response->status());

            if (!$response->successful()) {
                $status = $response->status();
                $this->error("  #{$property->id} — HTTP {$status}");
                $this->failCount++;
                return 'fail';
            }

            $data = $response->json();
            $places = $data['places'] ?? [];

            if (empty($places)) {
                DB::table('property_enrichment')->insert([
                    'detected_building_id' => $property->id,
                    'place_name' => null,
                    'place_type' => null,
                    'place_address' => null,
                    'business_status' => null,
                    'rating' => null,
                    'enrichment_source' => 'google_places',
                    'enriched_at' => now(),
                ]);
                $this->emptyCount++;
                $this->line("  #{$property->id} — empty (no places nearby)");
                return 'empty';
            }

            $place = $places[0];
            $name = $place['displayName']['text'] ?? null;
            $types = $place['types'] ?? [];
            $primaryType = $types[0] ?? null;
            $address = $place['formattedAddress'] ?? null;
            $status = $place['businessStatus'] ?? null;
            $rating = $place['rating'] ?? null;

            DB::table('property_enrichment')->insert([
                'detected_building_id' => $property->id,
                'place_name' => $name,
                'place_type' => $primaryType,
                'place_address' => $address,
                'business_status' => $status,
                'rating' => $rating,
                'enrichment_source' => 'google_places',
                'enriched_at' => now(),
            ]);

            $this->successCount++;
            $this->line("  #{$property->id} — {$name} ({$primaryType})");
            return 'success';

        } catch (\Exception $e) {
            $this->error("  #{$property->id} — Exception: " . $e->getMessage());
            $this->failCount++;
            return 'fail';
        }
    }

    private function ensureTrackingTable(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS google_places_api_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            called_at DATETIME NOT NULL,
            http_status INT NOT NULL,
            month_key CHAR(7) NOT NULL,
            api_key VARCHAR(50) NULL
        )");
        try { DB::statement("CREATE INDEX idx_api_log_month ON google_places_api_log (month_key)"); } catch (\Exception $e) {}
        try { DB::statement("CREATE INDEX idx_api_log_key_month ON google_places_api_log (api_key, month_key)"); } catch (\Exception $e) {}
    }

    private function getMonthlyUsageForKey(string $key): int
    {
        $monthKey = now()->format('Y-m');
        $keySuffix = substr($key, -8);
        return DB::table('google_places_api_log')
            ->where('month_key', $monthKey)
            ->where('api_key', $keySuffix)
            ->count();
    }

    private function getTotalMonthlyRemaining(): int
    {
        $total = 0;
        foreach ($this->apiKeys as $key) {
            $remaining = self::MONTHLY_CAP_PER_KEY - $this->getMonthlyUsageForKey($key);
            if ($remaining > 0) $total += $remaining;
        }
        return $total;
    }

    private function selectAvailableKey(): void
    {
        foreach ($this->apiKeys as $i => $key) {
            if ($this->getMonthlyUsageForKey($key) < self::MONTHLY_CAP_PER_KEY) {
                $this->currentKeyIndex = $i;
                $this->apiKey = $key;
                return;
            }
        }
        $this->currentKeyIndex = 0;
        $this->apiKey = $this->apiKeys[0];
    }

    private function rotateKey(): bool
    {
        for ($i = $this->currentKeyIndex + 1; $i < count($this->apiKeys); $i++) {
            if ($this->getMonthlyUsageForKey($this->apiKeys[$i]) < self::MONTHLY_CAP_PER_KEY) {
                $this->currentKeyIndex = $i;
                $this->apiKey = $this->apiKeys[$i];
                $this->warn("  Rotated to Key #" . ($i + 1));
                return true;
            }
        }
        return false;
    }

    private function trackApiCall(int $httpStatus): void
    {
        DB::table('google_places_api_log')->insert([
            'called_at' => now(),
            'http_status' => $httpStatus,
            'month_key' => now()->format('Y-m'),
            'api_key' => substr($this->apiKey, -8),
        ]);
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info("=== SUMMARY ===");
        $this->info("Processed: {$this->totalProcessed}");
        $this->info("Success (with data): {$this->successCount}");
        $this->info("Empty (no places): {$this->emptyCount}");
        $this->info("Failed: {$this->failCount}");
        $total = DB::table('property_enrichment')->count();
        $this->info("Total enriched in DB: {$total}");
        $remaining = DB::table('v_property_master')
            ->whereNotIn('id', DB::table('property_enrichment')->select('detected_building_id'))
            ->where('status_izin', 'Tidak Berizin')
            ->count();
        $this->info("Remaining unenriched: {$remaining}");
        $this->info("Estimated API credits used this session: " . ($this->successCount + $this->emptyCount + $this->failCount));
        foreach ($this->apiKeys as $i => $key) {
            $used = $this->getMonthlyUsageForKey($key);
            $remaining = self::MONTHLY_CAP_PER_KEY - $used;
            $this->info("Key #" . ($i + 1) . ": {$used}/" . self::MONTHLY_CAP_PER_KEY . " | Remaining: {$remaining}");
        }
        $this->info("Total remaining (all keys): " . $this->getTotalMonthlyRemaining());
    }
}
