<?php

namespace Tests\Unit;

use App\Jobs\InvalidateBuildingTiles;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The slippy-map tile-coord conversion gates the cache invalidation in
 * Phase 15. If lonLatToTile() drifts, the wrong tiles get evicted on
 * verify and stale colours stick in the cache for an hour.
 *
 * Reference values cross-checked against Wikipedia's Slippy_map_tilenames
 * formulas (the standard XYZ scheme used by OSM, Mapbox, MapLibre, etc).
 */
class TileCoordMathTest extends TestCase
{
    private function tileOf(float $lng, float $lat, int $z): array
    {
        $job = new InvalidateBuildingTiles($lat, $lng);
        $m = new ReflectionMethod($job, 'lonLatToTile');
        $m->setAccessible(true);
        return $m->invoke($job, $lng, $lat, $z);
    }

    public function test_zero_zoom_is_a_single_tile(): void
    {
        $this->assertSame([0, 0], $this->tileOf(0.0, 0.0, 0));
        $this->assertSame([0, 0], $this->tileOf(179.0, 1.0, 0));
        $this->assertSame([0, 0], $this->tileOf(-179.0, -1.0, 0));
    }

    public function test_z14_bandung_center(): void
    {
        // The Bandung-centre tile is the reference used throughout the
        // Phase 6+ docs; freezing it here means a math regression would
        // break the recorded baseline immediately.
        $this->assertSame([13086, 8513], $this->tileOf(107.55, -7.05, 14));
    }

    public function test_z16_bandung_center(): void
    {
        $this->assertSame([52346, 34054], $this->tileOf(107.55, -7.05, 16));
    }

    public function test_z18_single_building_scale(): void
    {
        // Same point as z14 above, just zoomed in — coords scale by 16×
        // each four zoom levels.
        $this->assertSame([209387, 136218], $this->tileOf(107.55, -7.05, 18));
    }

    public function test_lng_within_a_tile_does_not_change_x(): void
    {
        // Two points inside the same z14 tile stay on the same (x, y).
        // Tile 14/13086/8513 covers roughly 107.534..107.556 lng.
        $a = $this->tileOf(107.540, -7.045, 14);
        $b = $this->tileOf(107.552, -7.045, 14);
        $this->assertSame($a, $b);
    }

    public function test_crossing_tile_boundary_increments_x(): void
    {
        // Step across the eastern boundary of tile 14/13086/...
        $inside  = $this->tileOf(107.555, -7.045, 14);
        $outside = $this->tileOf(107.557, -7.045, 14);
        $this->assertNotSame($inside, $outside);
        $this->assertSame($inside[0] + 1, $outside[0]);
        $this->assertSame($inside[1], $outside[1]);
    }
}
