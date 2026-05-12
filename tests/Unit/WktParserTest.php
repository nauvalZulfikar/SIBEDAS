<?php

namespace Tests\Unit;

use App\Support\WktParser;
use PHPUnit\Framework\TestCase;

/**
 * Pin down the WKT → GeoJSON conversion that gates real-polygon
 * ingestion from Google Open Buildings (Phase 5). If this regresses,
 * fresh BigQuery exports would silently fall back to the square
 * envelope heuristic without anyone noticing.
 */
class WktParserTest extends TestCase
{
    public function test_null_and_empty_return_null(): void
    {
        $this->assertNull(WktParser::toGeoJson(null));
        $this->assertNull(WktParser::toGeoJson(''));
    }

    public function test_garbage_returns_null(): void
    {
        $this->assertNull(WktParser::toGeoJson('not a wkt'));
        $this->assertNull(WktParser::toGeoJson('FOO((0 0, 1 1))'));
    }

    public function test_unsupported_geometry_returns_null(): void
    {
        // We don't render points / lines / 3D variants as polygons.
        $this->assertNull(WktParser::toGeoJson('POINT(107.55 -7.05)'));
        $this->assertNull(WktParser::toGeoJson('LINESTRING(0 0, 1 1, 2 2)'));
    }

    public function test_simple_polygon(): void
    {
        $wkt = 'POLYGON((107.5 -7.05, 107.51 -7.05, 107.51 -7.04, 107.5 -7.04, 107.5 -7.05))';
        $g = WktParser::toGeoJson($wkt);
        $this->assertIsArray($g);
        $this->assertSame('Polygon', $g['type']);
        $this->assertCount(1, $g['coordinates']);                  // 1 ring (no holes)
        $this->assertCount(5, $g['coordinates'][0]);               // 5 points (closed)
        $this->assertSame([107.5, -7.05], $g['coordinates'][0][0]);
    }

    public function test_polygon_with_hole(): void
    {
        // Outer ring + one inner ring (a doughnut footprint).
        $wkt = 'POLYGON((0 0, 10 0, 10 10, 0 10, 0 0), (2 2, 8 2, 8 8, 2 8, 2 2))';
        $g = WktParser::toGeoJson($wkt);
        $this->assertSame('Polygon', $g['type']);
        $this->assertCount(2, $g['coordinates']);                  // outer + hole
        $this->assertCount(5, $g['coordinates'][0]);
        $this->assertCount(5, $g['coordinates'][1]);
    }

    public function test_multipolygon(): void
    {
        $wkt = 'MULTIPOLYGON('
             . '((107.5 -7.05, 107.51 -7.05, 107.51 -7.04, 107.5 -7.04, 107.5 -7.05)),'
             . '((107.6 -7.1, 107.61 -7.1, 107.61 -7.09, 107.6 -7.09, 107.6 -7.1))'
             . ')';
        $g = WktParser::toGeoJson($wkt);
        $this->assertSame('MultiPolygon', $g['type']);
        $this->assertCount(2, $g['coordinates']);                  // 2 polygons
        $this->assertCount(1, $g['coordinates'][0]);               // each w/ 1 ring
        $this->assertCount(5, $g['coordinates'][0][0]);
    }

    public function test_whitespace_and_case_tolerance(): void
    {
        $wkt = "  polygon  ((1 2, 3 4, 5 6, 1 2))  ";
        $g = WktParser::toGeoJson($wkt);
        $this->assertNotNull($g);
        $this->assertSame('Polygon', $g['type']);
        $this->assertSame([1.0, 2.0], $g['coordinates'][0][0]);
    }
}
