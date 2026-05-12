# Vector Tiles — Architecture

End-to-end view of the polygon-footprint layer that lights up on
`/dashboards/satellite-monitoring` from zoom 14 upward. Read this
when onboarding to the codebase or diagnosing a regression that
spans more than one phase.

## 1. Data flow

```
┌──────────────────────────────────────────────────────────────────────┐
│  SOURCES (ingest)                                                    │
│                                                                      │
│  Google Open Buildings   Microsoft Footprints   OSM Overpass   GEE   │
│  (CSV w/ WKT geometry)   (CSV centroid+area)    (OverpassQL)  CSV    │
│              │                   │                   │          │    │
│              └──────────┬────────┴───────────────────┘          │    │
│                         │                                       │    │
│                buildings:import-* artisan commands              │    │
│                         │                                       │    │
└─────────────────────────┼───────────────────────────────────────┼────┘
                          ▼                                       │
              ┌───────────────────────────┐                       │
              │  MySQL detected_buildings │◀──────────────────────┘
              │  (1.18 M rows; lat/lng +  │
              │   geometry_geojson?)      │
              └─────────────┬─────────────┘
                            │ daily 03:00 (Phase 4)
                            │ buildings:sync-postgis
                            ▼
              ┌───────────────────────────┐
              │ PostGIS buildings table   │
              │   id, geom Polygon/4326,  │
              │   centroid Point/4326,    │
              │   status_color, district, │
              │   verification_status…    │   (Phase 2)
              │  GIST index on geom       │
              └─────────────┬─────────────┘
                            │ PL/pgSQL function
                            │ building_tile(z,x,y, query_params)
                            ▼
              ┌───────────────────────────┐
              │ Martin tile-server        │   (Phase 6)
              │ ghcr.io/maplibre/martin   │
              │ auto-publishes the func   │
              │ as /building_tile/{z}/…   │
              │ loopback only             │
              └─────────────┬─────────────┘
                            │ HTTP (in-network)
                            ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Laravel TilesController                                             │   (Phase 8)
│    auth:sanctum + pbb.clearance:level_2 + throttle:tiles             │
│    ETag → 304                                                        │
│  ┌─────────────────────────┐    miss     ┌──────────────────────┐    │
│  │ Cache::store('redis')   │◀───────────▶│  forward to Martin   │    │   (Phase 14)
│  │ key tile:{z}:{x}:{y}:f  │             │                      │    │
│  │ ttl 3600 s              │             └──────────────────────┘    │
│  └─────────────────────────┘                                         │
│                                                                      │
│  DetectedBuildingObserver → InvalidateBuildingTiles job              │   (Phase 15)
│    DEL keys matching tile:{z..18}:{x}:{y}:* on verify                │
└───────────────────────────┬──────────────────────────────────────────┘
                            │ HTTPS  application/x-protobuf
                            │ Cache-Control: public, max-age=3600
                            ▼
              ┌───────────────────────────┐
              │ Browser (Leaflet)         │   (Phase 9–12)
              │  L.vectorGrid.protobuf    │
              │  minZoom 14, click→panel, │
              │  filter dropdown → refresh│
              └───────────────────────────┘
```

## 2. Containers

| Container | Image | Port (loopback) | Memory cap | Owner phase |
|---|---|---|---|---|
| `sibedas_postgis` | `postgis/postgis:16-3.4-alpine` | 5432 | 1 GB | Phase 1 |
| `sibedas_martin`  | `ghcr.io/maplibre/martin:v0.14.2` | 3000 | 256 MB | Phase 6 |
| `sibedas_redis`   | `redis:7-alpine` | 6379 | 320 MB | Phase 14 |

All three bind to `127.0.0.1` only; the only public path into the
stack is via Laravel + nginx.

## 3. Feature flag

`config/features.php` reads:

```env
VECTOR_TILES_ENABLED=false      # master switch — proxy returns 503 when off
VECTOR_TILES_MIN_ZOOM=14        # server-side floor on tile requests
```

When `false` the polygon layer is completely inert: the JavaScript
never reaches `L.vectorGrid.protobuf`, the proxy 503s any direct
hits, and the supporting containers can stay idle without affecting
the rest of the app.

## 4. Auth gating (PII)

A building polygon reveals the exact footprint of a household —
sensitive under UU PDP. Two gates, in order:

1. **Backend**: `pbb.clearance:level_2` on the `/api/tiles/...` route.
   Every request lands in `pbb_access_log` for audit (Phase 8).
2. **Frontend**: `<meta name="user-clearance">` set at login lets the
   blade skip building the layer at all for `level_1` users — no 403
   noise in the console, no misleading mode pill (Phase 16).

A future tile function could mask coordinates by jittering for
clearance < 2, but right now the policy is hard binary: see-everything
or see-nothing.

## 5. Schema reference

```sql
CREATE TABLE buildings (
    id                    BIGINT       PRIMARY KEY,
    geom                  GEOMETRY(Polygon, 4326)  NOT NULL,
    centroid              GEOMETRY(Point,   4326)  NOT NULL,
    source                VARCHAR(50),       -- google_open_buildings, microsoft_footprints, osm_buildings, sentinel_cv
    verification_status   VARCHAR(30),       -- mirrors MySQL detected_buildings.verification_status enum
    district              VARCHAR(100),      -- kecamatan
    ward                  VARCHAR(100),      -- kelurahan
    matched_pbg_task_id   BIGINT,            -- nullable; if NULL → orphan polygon (red)
    area_m2               NUMERIC(10, 2),
    status_color          VARCHAR(7),        -- precomputed hex; refined in Phase 7
    updated_at            TIMESTAMPTZ        -- last sync wall-clock
);

CREATE INDEX idx_buildings_geom     ON buildings USING GIST (geom);
CREATE INDEX idx_buildings_centroid ON buildings USING GIST (centroid);
CREATE INDEX idx_buildings_district ON buildings (district);
CREATE INDEX idx_buildings_verif    ON buildings (verification_status);
CREATE INDEX idx_buildings_pbg_task ON buildings (matched_pbg_task_id);
CREATE INDEX idx_buildings_source   ON buildings (source);
```

## 6. The SRID dance (gotcha)

`buildings.geom` is stored in **WGS84 (4326)** because that's what
Leaflet and the GeoJSON ingest path use.
`ST_TileEnvelope(z, x, y)` returns **Web Mercator (3857)** because
that's the canonical XYZ tile CRS.

`building_tile()` (Phase 7) therefore:

- Projects the *envelope* back to 4326 for the `geom &&` index probe
  so the GIST index on `buildings(geom)` is used. Without this,
  every query falls back to a sequential scan over 1.18 M rows.
- Projects *geom* into 3857 inside `ST_AsMVTGeom`, because that
  function requires source and envelope to share an SRID.

If a future migration moves `buildings.geom` to 3857 to skip the
transform, drop the index-probe transform too — otherwise the
planner falls back to seq-scan again.

## 7. Phase map

For the running narrative — what each commit did, why, and what
verification was done — read `docs/vector-tiles/BASELINE.md`. The
runbook (`RUNBOOK.md`) covers ops: how to start/stop, what to do
when something looks off, where to point ops dashboards.
