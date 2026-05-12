<?php

/**
 * Feature flags — gate work-in-progress functionality behind env toggles.
 * Read from .env via env('FLAG_NAME', default).
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Vector Tile Polygon Layer (Satellite Monitoring)
    |--------------------------------------------------------------------------
    |
    | When enabled, the satellite monitoring dashboard renders building
    | footprint polygons via vector tiles (PostGIS + martin) when zoomed in
    | to level 14+. When disabled, the dashboard retains the current
    | dot-clustering-only behavior.
    |
    | Rollout: keep false until Phase 19 (staging deploy). Production
    | rollout is gated per role inside the controller, not by this flag
    | alone.
    */
    'vector_tiles_enabled' => env('VECTOR_TILES_ENABLED', false),

    /*
    | Minimum Leaflet zoom at which polygon tiles are requested. Below this,
    | the existing dot+cluster layer is shown. Tune after performance tests.
    */
    'vector_tiles_min_zoom' => (int) env('VECTOR_TILES_MIN_ZOOM', 14),
];
