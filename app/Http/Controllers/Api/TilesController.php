<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
 *
 * It does NOT do server-side tile caching — that's Phase 14 (Redis layer).
 */
class TilesController extends Controller
{
    /** Filter keys forwarded to the PostGIS function. Anything else is dropped. */
    private const ALLOWED_FILTERS = ['district', 'status', 'source', 'min_area'];

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

        // Deterministic ETag — same tile + same filter = same ETag → 304 path.
        $etag = '"' . substr(sha1("{$z}/{$x}/{$y}?" . http_build_query($params)), 0, 16) . '"';
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=3600');
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
                ->header('Content-Type', 'application/x-protobuf');
        }

        if ($upstream->status() === 204) {
            // Martin returns 204 when no features intersect the tile. Surface
            // it as an empty-but-valid 200 so Leaflet doesn't treat it as an
            // error and retry.
            return response('', 200)
                ->header('Content-Type', 'application/x-protobuf')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', $etag);
        }

        if (!$upstream->successful()) {
            Log::warning("TilesController: martin returned {$upstream->status()} for {$url}");
            return response('', 502)
                ->header('Content-Type', 'application/x-protobuf');
        }

        return response($upstream->body(), 200)
            ->header('Content-Type', 'application/x-protobuf')
            ->header('Content-Length', (string) strlen($upstream->body()))
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('ETag', $etag);
    }
}
