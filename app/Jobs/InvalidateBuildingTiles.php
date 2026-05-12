<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Phase 15 — invalidate Redis tile cache entries that contain the point
 * (lat, lng) at zoom levels VECTOR_TILES_MIN_ZOOM..18. Each (z, x, y)
 * may be cached under several filter-hash suffixes, so we SCAN for
 * keys matching the prefix and unlink them in bulk.
 *
 * Runs on the default queue worker. Queueing keeps the verify endpoint
 * fast — the controller returns immediately, cache busting happens
 * in the background.
 */
class InvalidateBuildingTiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public float $lat,
        public float $lng,
    ) {}

    public function handle(): void
    {
        $minZoom = (int) config('features.vector_tiles_min_zoom', 14);
        $maxZoom = 18;
        // SCAN returns *prefixed* keys, so MATCH must include the Redis
        // connection-level prefix. Laravel composes that from
        // database.redis.options.prefix; cache.prefix is empty by default.
        $prefix = (string) config('database.redis.options.prefix', '');

        $totalDeleted = 0;
        try {
            $connection = Redis::connection('cache');
            for ($z = $minZoom; $z <= $maxZoom; $z++) {
                [$x, $y] = $this->lonLatToTile($this->lng, $this->lat, $z);
                $pattern = $prefix . "tile:{$z}:{$x}:{$y}:*";

                // SCAN avoids blocking the Redis main thread the way KEYS does.
                $cursor = '0';
                $batch  = [];
                do {
                    $result = $connection->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 200]);
                    // predis returns [cursor, [keys]]; phpredis returns same shape.
                    $cursor = is_array($result) ? (string) ($result[0] ?? '0') : '0';
                    $keys = is_array($result) ? ($result[1] ?? []) : [];
                    foreach ($keys as $k) $batch[] = $k;
                } while ($cursor !== '0');

                if (!empty($batch)) {
                    // Strip the connection prefix back off — Laravel's
                    // wrapper re-adds it on DEL, so passing the already-
                    // prefixed key would double-prefix.
                    $stripped = array_map(
                        fn ($k) => ($prefix !== '' && str_starts_with($k, $prefix))
                            ? substr($k, strlen($prefix))
                            : $k,
                        $batch
                    );
                    // DEL is fine; predis 3 doesn't register UNLINK by default.
                    $count = $connection->del($stripped);
                    $totalDeleted += (int) $count;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('InvalidateBuildingTiles: ' . $e->getMessage(), [
                'lat' => $this->lat, 'lng' => $this->lng,
            ]);
            throw $e;
        }

        if ($totalDeleted > 0) {
            Log::info("Tile cache invalidated", [
                'lat' => $this->lat, 'lng' => $this->lng, 'keys_deleted' => $totalDeleted,
            ]);
        }
    }

    /**
     * Standard slippy-map tile math. Returns [x, y] indices at zoom $z.
     */
    private function lonLatToTile(float $lng, float $lat, int $z): array
    {
        $n = 2 ** $z;
        $x = (int) floor((($lng + 180) / 360) * $n);
        $rad = deg2rad($lat);
        $y = (int) floor((1 - log(tan($rad) + 1 / cos($rad)) / M_PI) / 2 * $n);
        return [$x, $y];
    }
}
