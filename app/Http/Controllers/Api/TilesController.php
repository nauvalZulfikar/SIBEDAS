<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public-facing proxy for Martin vector tiles (Phase 8).
 *
 * Martin itself binds to 127.0.0.1 inside the docker network; this is the
 * only path the browser can reach it through. The proxy adds:
 *   - auth (Sanctum) + role clearance (pbb.clearance:level_2 — admins)
 *   - feature-flag gate (config('features.vector_tiles_enabled'))
 *   - min-zoom guard (server side, in addition to the frontend gate)
 *   - whitelisted querystring filters forwarded to building_tile()
 *   - ETag + Cache-Control so the browser caches each tile for an hour
 *   - rate limiter applied at the route level
 *   - Phase 14: Redis tile cache. Upstream tiles are stored 1 h by
 *     (z, x, y, filter-hash). Cache hits skip Martin entirely; the
 *     X-Cache response header reports HIT vs MISS.
 */
class TilesController extends Controller
{
    /** Filter keys forwarded to the PostGIS function. Anything else is dropped. */
    private const ALLOWED_FILTERS = ['district', 'status', 'source', 'exclude_source', 'min_area'];

    /** Tile cache TTL in seconds. 1 h matches the browser Cache-Control. */
    private const CACHE_TTL = 3600;

    public function buildings(Request $request, int $z, int $x, int $y): Response
    {
        if (!config('features.vector_tiles_enabled')) {
            return response()->json(['message' => 'Vector tiles feature is disabled.'], 503);
        }

        $minZoom = (int) config('features.vector_tiles_min_zoom', 14);
        if ($z < $minZoom) {
            // Defence in depth — the JS layer already hides below z14, but
            // a hand-crafted request must not pull a multi-MB low-zoom tile.
            return response('', 404)
                ->header('Content-Type', 'application/x-protobuf');
        }

        // Forward the whitelisted filters; ignore anything else the caller sends.
        $params = array_intersect_key(
            $request->query(),
            array_flip(self::ALLOWED_FILTERS)
        );

        $filterHash = substr(sha1(http_build_query($params)), 0, 12);
        $cacheKey = "tile:{$z}:{$x}:{$y}:{$filterHash}";
        $etag = '"' . substr(sha1("{$z}/{$x}/{$y}?" . http_build_query($params)), 0, 16) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=3600');
        }

        // Cache lookup — Redis store is configured in config/cache.php; falls
        // back to whatever CACHE_STORE is set to if Redis is unreachable.
        // Cached entries store [status, body] so empty-tile 200s survive.
        try {
            $cached = Cache::store('redis')->get($cacheKey);
        } catch (\Throwable $e) {
            Log::warning("TilesController: redis lookup failed, bypassing cache: " . $e->getMessage());
            $cached = null;
        }

        if (is_array($cached) && isset($cached['status'], $cached['body'])) {
            return response($cached['body'], $cached['status'])
                ->header('Content-Type', 'application/x-protobuf')
                ->header('Content-Length', (string) strlen($cached['body']))
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', $etag)
                ->header('X-Cache', 'HIT');
        }

        $martinHost = config('services.martin.host', 'http://127.0.0.1:3000');
        $url = rtrim($martinHost, '/') . "/building_tile/{$z}/{$x}/{$y}";

        try {
            $upstream = Http::timeout(10)
                ->withQueryParameters($params)
                ->retry(2, 200)
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning("TilesController: martin upstream failed: " . $e->getMessage());
            return response('', 502)
                ->header('Content-Type', 'application/x-protobuf')
                ->header('X-Cache', 'MISS');
        }

        if ($upstream->status() === 204) {
            // Martin returns 204 when no features intersect the tile. Surface
            // it as an empty-but-valid 200 so Leaflet doesn't treat it as an
            // error and retry — and cache the empty payload too.
            $this->cacheStore($cacheKey, 200, '');
            return response('', 200)
                ->header('Content-Type', 'application/x-protobuf')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', $etag)
                ->header('X-Cache', 'MISS');
        }

        if (!$upstream->successful()) {
            Log::warning("TilesController: martin returned {$upstream->status()} for {$url}");
            return response('', 502)
                ->header('Content-Type', 'application/x-protobuf')
                ->header('X-Cache', 'MISS');
        }

        $body = $upstream->body();
        $this->cacheStore($cacheKey, 200, $body);

        return response($body, 200)
            ->header('Content-Type', 'application/x-protobuf')
            ->header('Content-Length', (string) strlen($body))
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('ETag', $etag)
            ->header('X-Cache', 'MISS');
    }

    private function cacheStore(string $key, int $status, string $body): void
    {
        try {
            Cache::store('redis')->put($key, ['status' => $status, 'body' => $body], self::CACHE_TTL);
        } catch (\Throwable $e) {
            Log::warning("TilesController: redis store failed: " . $e->getMessage());
        }
    }
}
