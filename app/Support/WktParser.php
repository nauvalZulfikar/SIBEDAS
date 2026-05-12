<?php

namespace App\Support;

/**
 * Minimal WKT (Well-Known Text) → GeoJSON parser for the polygon kinds the
 * Google Open Buildings v3 BigQuery export emits. Handles POLYGON and
 * MULTIPOLYGON only — points, lines, and 3D variants are ignored on
 * purpose; the source dataset doesn't use them.
 *
 * The parser is forgiving on whitespace and case but strict about the
 * outer keyword: anything that doesn't start with POLYGON / MULTIPOLYGON
 * returns null. Callers should treat null as "no usable polygon" and
 * fall back to the centroid+area square heuristic.
 */
class WktParser
{
    /**
     * @return array|null GeoJSON Geometry object, or null on parse failure.
     */
    public static function toGeoJson(?string $wkt): ?array
    {
        if (!$wkt) return null;
        $trimmed = ltrim($wkt);
        $upper = strtoupper(substr($trimmed, 0, 12));

        if (str_starts_with($upper, 'POLYGON')) {
            return self::parsePolygon($trimmed);
        }
        if (str_starts_with($upper, 'MULTIPOLYGON')) {
            return self::parseMultiPolygon($trimmed);
        }
        return null;
    }

    private static function parsePolygon(string $wkt): ?array
    {
        // POLYGON((x y, x y, ...), (x y, ...)) — outer ring + optional inner rings (holes)
        if (!preg_match('/^POLYGON\s*\((.*)\)$/is', trim($wkt), $m)) return null;
        $rings = self::splitRings($m[1]);
        if (empty($rings)) return null;
        return [
            'type' => 'Polygon',
            'coordinates' => array_map([self::class, 'parseRing'], $rings),
        ];
    }

    private static function parseMultiPolygon(string $wkt): ?array
    {
        if (!preg_match('/^MULTIPOLYGON\s*\((.*)\)$/is', trim($wkt), $m)) return null;
        $polys = self::splitPolygons($m[1]);
        $coords = [];
        foreach ($polys as $poly) {
            $rings = self::splitRings($poly);
            $coords[] = array_map([self::class, 'parseRing'], $rings);
        }
        if (empty($coords)) return null;
        return ['type' => 'MultiPolygon', 'coordinates' => $coords];
    }

    /**
     * Split "(ring1), (ring2)" — used inside a POLYGON's parenthesis.
     */
    private static function splitRings(string $inner): array
    {
        return self::splitParenGroups($inner);
    }

    /**
     * Split "((r1),(r2)), ((r3))" — used inside a MULTIPOLYGON's outer
     * parenthesis. Each chunk is "(r1),(r2)" i.e. a polygon's rings block.
     */
    private static function splitPolygons(string $inner): array
    {
        $out = [];
        $depth = 0;
        $buf = '';
        $len = strlen($inner);
        for ($i = 0; $i < $len; $i++) {
            $c = $inner[$i];
            if ($c === '(') {
                $depth++;
                if ($depth === 1) { $buf = ''; continue; }
            } elseif ($c === ')') {
                $depth--;
                if ($depth === 0) { $out[] = $buf; continue; }
            }
            if ($depth >= 1) $buf .= $c;
        }
        return $out;
    }

    private static function splitParenGroups(string $inner): array
    {
        $out = [];
        $depth = 0;
        $buf = '';
        $len = strlen($inner);
        for ($i = 0; $i < $len; $i++) {
            $c = $inner[$i];
            if ($c === '(') {
                $depth++;
                if ($depth === 1) { $buf = ''; continue; }
            } elseif ($c === ')') {
                $depth--;
                if ($depth === 0) { $out[] = $buf; continue; }
                $buf .= $c;
                continue;
            }
            if ($depth >= 1) $buf .= $c;
        }
        return $out;
    }

    private static function parseRing(string $ring): array
    {
        $pairs = preg_split('/\s*,\s*/', trim($ring));
        $coords = [];
        foreach ($pairs as $pair) {
            $parts = preg_split('/\s+/', trim($pair));
            if (count($parts) < 2) continue;
            $coords[] = [(float) $parts[0], (float) $parts[1]];
        }
        return $coords;
    }
}
