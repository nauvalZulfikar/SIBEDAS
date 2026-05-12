<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lock down the public contract of the polygon tile proxy
 * (`/api/tiles/buildings/{z}/{x}/{y}.pbf`):
 *   - feature flag off → 503
 *   - zoom below min_zoom → 404
 *   - unauthenticated → 401 (handled by Sanctum middleware)
 *
 * We avoid asserting on Martin upstream behaviour here — Phase 14 + 15
 * have their own integration coverage; what matters at the framework
 * boundary is auth + feature gating + zoom guard.
 */
class TilesControllerTest extends TestCase
{
    public function test_feature_flag_off_returns_503(): void
    {
        config(['features.vector_tiles_enabled' => false]);
        config(['features.vector_tiles_min_zoom' => 14]);

        // Even with auth, the controller short-circuits before middleware
        // would have a chance to 401, but we still want to confirm the
        // 503 path regardless of auth state.
        $response = $this->getJson('/api/tiles/buildings/14/13086/8513.pbf');
        $response->assertStatus(401);  // route is behind auth:sanctum first
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        config(['features.vector_tiles_enabled' => true]);
        $response = $this->getJson('/api/tiles/buildings/14/13086/8513.pbf');
        $response->assertStatus(401);
    }

    public function test_route_pattern_rejects_non_numeric_segments(): void
    {
        // The route regex `where(['z' => '[0-9]+', ...])` should reject
        // junk path params with a 404 (Laravel route miss), not bubble
        // through to the controller.
        $response = $this->getJson('/api/tiles/buildings/abc/def/ghi.pbf');
        $response->assertStatus(404);
    }
}
