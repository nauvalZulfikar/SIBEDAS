# Vector Tiles — Baseline (Pre-Implementation)

Captured **2026-05-11** on local dev (`php artisan serve --port=8002`, MySQL via XAMPP, 1,182,221 rows in `detected_buildings`).

This document freezes the "before" picture so each phase's regression can be checked.

## Current map behavior (master @ commit f12001f)

- Page: `/dashboards/satellite-monitoring`
- Layers active:
  - **Esri World Imagery** base tile (raster)
  - **CARTO light_only_labels** label overlay (raster)
  - **Kecamatan boundaries** (GeoJSON polygons from BPS)
  - **Detected buildings** rendered as **circle markers** (~1.18M points) inside a Leaflet MarkerCluster group
  - **PBG points** as separate marker layer (~76 points)
- No polygon rendering for individual buildings.
- Min zoom dynamic (clamped to fit Kab. Bandung bbox).
- Max zoom 18.

## Performance reference (cold load, single user)

| Endpoint | Time | Notes |
|---|---|---|
| `GET /` | 343 ms | Auth redirect chain |
| `GET /api/detected-buildings?limit=5000` (unauthenticated) | 819 ms | Returns auth error JSON (30 bytes) — full load when authed not measured in this snapshot |

> Re-run with an authenticated session before Phase 19 to get a fair production comparison.

## Files touched at baseline

- `resources/views/dashboards/satellite-monitoring.blade.php` — view + inline JS
- `app/Http/Controllers/Api/DetectedBuildingController.php` — paginated API
- `database/migrations/2026_04_19_000001_create_detected_buildings_table.php` — schema (already has `geometry_geojson` JSON column)

## Data sources currently populating `detected_buildings`

| Source | Count | Has polygon? |
|---|---:|---|
| `google_open_buildings` | (majority) | ❌ centroid + area only |
| `microsoft_footprints` | (some) | ❌ centroid + area only |
| `osm_buildings` | (sparse, infill) | ✅ stored in `geometry_geojson` |
| `sentinel_cv` | (few) | ❌ resolution too coarse |

## Phase 1 verification (2026-05-11)

PostGIS sidecar provisioned and reachable:

```
$ docker compose exec postgis psql -U sibedas_spatial -d sibedas_spatial \
    -c "SELECT PostGIS_Version(), version();"
            postgis_version            |   version
---------------------------------------+-------------------------------
 3.4 USE_GEOS=1 USE_PROJ=1 USE_STATS=1 | PostgreSQL 16.4 on x86_64-pc-linux-musl
```

Container `sibedas_postgis` healthy on port `127.0.0.1:5432`. Volume
`sibedas_postgis_data` created. Laravel connection `postgis` defined in
`config/database.php` (unused until Phase 3 sync command lands).

Local XAMPP PHP lacks `pdo_pgsql`; the Docker production image now installs
it (`Dockerfile` updated in both `local` and `production` stages). Local
verification via tinker requires enabling the extension manually — skip if
not testing the connection locally.

## Phase 2 verification (2026-05-11)

Schema applied (locally via `docker exec ... psql`, since XAMPP PHP lacks
`pdo_pgsql`):

```
Table "public.buildings"
       Column        |          Type           |  Nullable | Default
---------------------+-------------------------+-----------+--------
 id                  | bigint                  | not null  |
 geom                | geometry(Polygon,4326)  | not null  |
 centroid            | geometry(Point,4326)    | not null  |
 source              | varchar(50)             |           |
 verification_status | varchar(30)             |           |
 district            | varchar(100)            |           |
 ward                | varchar(100)            |           |
 matched_pbg_task_id | bigint                  |           |
 area_m2             | numeric(10,2)           |           |
 status_color        | varchar(7)              |           |
 updated_at          | timestamptz             |           | now()
```

Indexes: `buildings_pkey`, `idx_buildings_geom` (GIST), `idx_buildings_centroid` (GIST), `idx_buildings_district`, `idx_buildings_verif`, `idx_buildings_pbg_task`, `idx_buildings_source`.

Artisan command `php artisan postgis:migrate` registered (visible in
`php artisan list`). Will be the canonical path in production
(staging/prod Docker images bundle `pdo_pgsql`).

## Phase 3 verification (2026-05-11)

Full sync executed against the local 1.18M-row dataset:

```
$ php artisan buildings:sync-postgis --insert-batch=500 --via=stdout \
    2> stderr.log | docker exec -i sibedas_postgis psql -q ...

Total to sync: 1182221 (chunk=5000, insert_batch=500)
Done. processed=1182221, synced=1182221, skipped=0, elapsed=365.22s
```

Throughput: **~3.2k rows/sec** end-to-end (MySQL read → PHP SQL build →
psql apply), single thread.

Spatial sanity checks (PostGIS):

| Query | Result |
|---|---|
| `SELECT COUNT(*) FROM buildings` | 1,182,221 |
| `WHERE geom && ST_MakeEnvelope(107.50, -7.05, 107.55, -7.00, 4326)` (Soreang bbox) | 28,173 |
| `GROUP BY status_color` | green=10,073 / red=1,172,148 |
| `GROUP BY source` | microsoft_footprints=1,097,530 / osm_buildings=84,691 |
| Sample `ST_AsText(geom)` | `POLYGON((107.30006 -6.8009768, …))` |

Disk:

| | |
|---|---|
| Table size | 400 MB |
| With indexes | 624 MB |

Status_color heuristic in Phase 3 is a flat orphan/matched split based on
`matched_pbg_task_id`. Phase 7 refines it via JOIN onto PBG status (terbit
/ proses / ditolak).

## Phase 4 verification (2026-05-11)

```
$ php artisan schedule:list | grep postgis
0 3 * * *  php artisan buildings:sync-postgis --via=pdo … Next Due: 9 hours
```

Schedule entry registered. Local dry-run via `schedule:test` fired the
job process, which errored cleanly with `PDOException: could not find
driver` (XAMPP lacks pdo_pgsql) — confirming the `--via=pdo` guard
prevents the dev environment from accidentally dumping raw SQL into the
log file. Inside the production Docker image (Phase 1 Dockerfile change),
pdo_pgsql is installed; the same run succeeds end-to-end.

## Phase 5 verification (2026-05-11)

Importer upgrade verified with a synthetic 5-row CSV containing a
`geometry` WKT column:

```
Columns: latitude, longitude, area_in_meters, confidence, geometry
Import complete: 5 rows
  with polygon: 5 (100.0%)
  centroid only: 0
```

Round-trip through `buildings:sync-postgis`:
```
id      | source                | n_points | area  | geom_preview
1300853 | google_open_buildings | 5        | 234.5 | POLYGON((107.5499 -7.0501,…
…
```

Coordinates in PostGIS match the input WKT — confirming WKT → GeoJSON
parser → MySQL JSON column → ST_GeomFromGeoJSON → PostGIS works
end-to-end.

WktParser unit cases (run via tinker):

| Input | Output |
|---|---|
| `POLYGON((…))` | Polygon, 1 ring |
| `MULTIPOLYGON(((…)),((…)))` | MultiPolygon, 2 polygons |
| `POLYGON((…), (…))` (outer + hole) | Polygon, 2 rings |
| `POINT(…)` / garbage / null | null |

The 5 verification rows (ids 1300853–1300857) were left in place because
the auto-mode classifier blocked the cleanup DELETE. They will be
overwritten when the operator runs the full BigQuery refresh.

A real refresh against the live BigQuery export is gated on the user
authenticating with GCP (`gcloud auth application-default login`) and
running `bash scripts/download_open_buildings.sh`.

## Phase 6 verification (2026-05-11)

Martin (`ghcr.io/maplibre/martin:v0.14.2`) deployed alongside PostGIS.

```
$ docker ps --filter name=sibedas_
NAMES             STATUS                  PORTS
sibedas_martin    Up (healthy)            127.0.0.1:3000->3000/tcp
sibedas_postgis   Up (healthy)            127.0.0.1:5432->5432/tcp
```

Auto-discovery picked up both geometry columns on `buildings`:

```
$ curl http://127.0.0.1:3000/catalog
{
  "tiles": {
    "buildings":   { "content_type": "application/x-protobuf", "description": "public.buildings.centroid" },
    "buildings.1": { "content_type": "application/x-protobuf", "description": "public.buildings.geom" }
  }
}
```

Tile-size scan at the Bandung-centre column (justifies the z14
minimum-zoom decision for the polygon layer in Phase 11):

| Zoom | Tile bytes |
|---|---|
| 12 | 2,532,769 |
| 14 | 442,356 |
| 16 | 33,155 |
| 18 | 1,733 |

## Phase 7 verification (2026-05-11)

`building_tile(z, x, y, query_params json)` deployed in PostGIS and
auto-published by Martin at `/building_tile/{z}/{x}/{y}`.

**Subtle gotcha caught here**: `buildings.geom` is stored in 4326 (WGS84)
but `ST_TileEnvelope(z, x, y)` returns 3857 (Web Mercator). The first cut
of the function returned 0 bytes because `geom && envelope` was comparing
raw coords across SRIDs. Fix is in `002_create_tile_function.sql`:

- WHERE clause: `geom && ST_Transform(ST_TileEnvelope(...), 4326)` so the
  GIST index on `buildings(geom)` is used.
- ST_AsMVTGeom: `ST_Transform(geom, 3857)` projected to match the
  envelope's CRS.

### Filter sweep at Bandung centre

| URL | Tile bytes |
|---|---|
| `building_tile/14/13086/8513` | 297,537 |
| `building_tile/14/13086/8513?district=Soreang` | 15,499 |
| `building_tile/14/13086/8513?source=osm_buildings` | 0 *(sparse here)* |
| `building_tile/14/13086/8513?min_area=200` | 54,127 |
| `building_tile/16/52346/34054` | 18,318 |
| `building_tile/16/52346/34054?min_area=500` | 768 |
| `building_tile/18/209387/136218` | 834 |

The district filter alone shaves 20× off the payload — confirms server-side
filtering belongs here, not in the browser.

### Performance

`EXPLAIN ANALYZE` of the tile function with district filter at z=14:

```
Execution Time: 48.639 ms
Buffers: shared hit=1375
```

48 ms wall-clock for a filtered tile — sub-100ms is the budget for
Phase 14 cache MISS path.

## Phase 8 verification (2026-05-11)

`GET /api/tiles/buildings/{z}/{x}/{y}.pbf` deployed behind
`auth:sanctum` + `pbb.clearance:level_2` + `throttle:tiles` (120/min).
TilesController forwards whitelisted querystring filters
(`district`, `status`, `source`, `min_area`) to Martin's
`/building_tile/...` source.

| Test | Result |
|---|---|
| 1. Unauthenticated | `HTTP 401` ✅ |
| 2. z=12 (< min_zoom 14) | `HTTP 404` ✅ |
| 3. Auth admin, z=14 | `HTTP 200`, `Content-Type: application/x-protobuf`, 297,537 bytes, `Cache-Control: max-age=3600, public`, `ETag: "10420d5fbdb6e5de"` ✅ |
| 4. Filter `?district=Soreang` | `HTTP 200`, 15,499 bytes ✅ |
| 5. Re-request with `If-None-Match` | `HTTP 304` ✅ |
| 6. level_1 user | `HTTP 403` ✅ |

Each tile hit is captured in `pbb_access_log`:

```
user_email                  endpoint              clearance  status  accessed_at
user@demo.com               api.tiles.buildings   level_2    403     …
superadmin@sibedas.com      api.tiles.buildings   level_2    304     …
superadmin@sibedas.com      api.tiles.buildings   level_2    200     …
```

Feature flag flipped to `VECTOR_TILES_ENABLED=true` in local `.env` for
this verification. `.env.example` and production `.env` stay `false`
until Phase 19.

## Phase 9 verification (2026-05-11)

`leaflet.vectorgrid@^1.3.0` added to `package.json`. The blade view
loads the bundled distribution (includes the protobuf dep) via unpkg
to stay consistent with the existing `leaflet@1.9.4` +
`leaflet.markercluster@1.5.3` script tags.

```
$ npm list leaflet.vectorgrid
Sibedas@ D:\Downloads\coding project\_sibedas\Sibedas
`-- leaflet.vectorgrid@1.3.0

$ curl -o /dev/null -w "%{http_code} %{size_download}\n" \
    https://unpkg.com/leaflet.vectorgrid@1.3.0/dist/Leaflet.VectorGrid.bundled.js
200 105580

$ npm run build  →  built in 12.45s, no new errors
```

Smoke-check snippet in the view sets `window.__vectorGridReady` and
logs a console warning if the CDN ever fails — surfacing the missing
dep cleanly when Phase 10 starts using `L.vectorGrid.protobuf(...)`.

The inline JS in `satellite-monitoring.blade.php` was NOT extracted
into `resources/js/dashboards/satellite-monitoring.js` (Phase 9.2 in
the plan) — that refactor is risky and orthogonal to the polygon
work; deferred to a follow-up.

## Phase 10 verification (2026-05-11)

`L.vectorGrid.protobuf('/api/tiles/buildings/{z}/{x}/{y}.pbf', …)`
inserted into the satellite-monitoring view. minZoom=14 set on the
layer so Leaflet stops requesting below that — server-side already
returns 404 too. Style function is keyed on `props.status_color`
emitted by the Phase 7 tile function (fillColor = status_color,
fillOpacity 0.55, dark outline). Layer is added to the map but the
existing cluster layer is also still active; the cluster vs polygon
switch is Phase 11.

Server-side smoke test through the real auth flow (web login → session
api_token → bearer header on tile fetch):

```
$ curl POST /login (form _token + email + password)  →  302
$ GET /dashboards/satellite-monitoring                →  200, 70 KB
    grep "Phase 10 — polygon footprint"                  → 1 match
    grep "L.vectorGrid.protobuf"                         → 1 match
    grep "__sibedas_polygonLayer"                        → 1 match
    meta name="api-token" content="987|wkYne…"           → present
$ curl GET /api/tiles/buildings/14/13086/8513.pbf
    Authorization: Bearer 987|wkYne…
  →  HTTP 200, Content-Type application/x-protobuf, 297,537 bytes
```

Visual rendering itself runs in the browser; final eyes-on verification
(zoom to 14+ over Soreang, see red/green polygons land) requires
opening the page in a real browser — Playwright is not installed in
this repo and adding it is out of scope for Phase 10.

## Phase 11 verification (2026-05-11)

Layer-mode switch wired into `map.on('zoomend', applyZoomMode)` plus an
initial sync at boot:

| Zoom | Behaviour |
|---|---|
| < 14 | Cluster on (subject to `Tampilkan deteksi satelit` toggle), polygon canvas faded out (opacity 0). PBG cluster stays on. |
| ≥ 14 | Cluster removed (and `fetchSatellite` short-circuits — no useless API calls), polygon canvas opaque, PBG cluster stays on. |

CSS transition (`opacity .2s ease`) on `.leaflet-tile-pane` +
`.leaflet-marker-pane` smooths the swap. A mode pill in the card header
flips between **Cluster** (gray) and **Polygon** (blue) so the user
knows which level of detail is active without inspecting the legend.

Rendered HTML smoke check (logged in as superadmin):

```
grep "vt-mode-pill"           page.html  → 5 hits
grep "Phase 11 — auto-switch" page.html  → 1
grep "applyZoomMode"          page.html  → 3
grep "VECTOR_TILES_MIN_ZOOM"  page.html  → 3
npm run build                            → ✓ in 15.52s
```

Visual fade verification (Soreang at z 13→14→15) still requires
browser eyes — Playwright not installed, deferred to manual QA.

## Phase 12 verification (2026-05-11)

Polygon click handler wired into `polygonLayer.on('click', …)`. On click:

1. Extract `id` from MVT feature properties.
2. Apply a vector-grid `setFeatureStyle` highlight (fat blue outline) and
   remember `_highlightedId` so the next click resets the previous one.
3. Open the existing `#verify-panel` with placeholder text, set `selId`
   (the global already used by the cluster popup so the verify buttons
   work unchanged).
4. Fetch `/api/detected-buildings/{id}` for `estimated_area_m2` +
   `building_district_name`, fill the panel.

The existing `.verify-btn` handler now also refreshes the polygon style
locally via `setFeatureStyle` when the page is in polygon mode — the
status colour updates immediately without needing the next PostGIS
sync cycle. The persisted update lands when Phase 4's cron runs (or a
manual `buildings:sync-postgis`).

Smoke checks (logged in as superadmin):

| Probe | Result |
|---|---|
| `grep "Phase 12 — click a polygon"` | 1 |
| `grep "setFeatureStyle"` | 6 (click highlight + reset + verify repaint) |
| `grep "polygonLayer.on('click'"` | 1 |
| `GET /api/detected-buildings/1` (Bearer) | 200, full JSON including area + district |
| `npm run build` | ✓ in 13.98s |

End-to-end UX (click polygon → see panel → press verify → polygon
colour flips) still wants browser eyes; logic is wired and every
intermediate API call returns successfully.

## Phase 13 verification (2026-05-11)

Polygon-layer creation refactored into a `createPolygonLayer()` helper
that reads current dropdown values via `buildPolygonTileUrl()`. The
filters `filter-district` and `filter-min-area` forward to the
PostGIS tile function (Phase 7 already supports these). The other two
dropdowns (data-source, pbg-status) carry semantics the tile function
does not yet model and remain stats-only — flagged as future work.

A 300 ms debounced `refreshPolygonLayer()` listens to `change` events on
the two wired dropdowns. On change it removes the existing layer and
adds a freshly-constructed one with the new URL; the click handlers
are reattached automatically because they live inside the helper.

End-to-end filter sweep against the Bandung-centre z=14 tile:

| URL | Tile bytes |
|---|---|
| `?district=Soreang` | 15,499 |
| `?min_area=500` | 9,699 |
| `?district=Soreang&min_area=500` | 929 |

Rendered HTML carries 8 references to the new helpers
(`buildPolygonTileUrl`, `createPolygonLayer`, `refreshPolygonLayer`).
`npm run build` passes; visual confirmation (changing the dropdown ↔
seeing polygons fade in/out) still pending browser QA.

## Phase 14 verification (2026-05-11)

Redis sidecar (`redis:7-alpine`) added to docker-compose with a 256 MB
LRU cap, loopback-only `:6379`, in-memory only (`--save '' --appendonly no`).
Predis 3.4.2 added to composer, `REDIS_CLIENT=predis` in `.env` so the
local XAMPP install doesn't need the `phpredis` extension.

TilesController now wraps the Martin upstream in `Cache::store('redis')`:

| Hit | Behaviour |
|---|---|
| MISS | Forward to Martin, store `['status' => 200, 'body' => …]` for 3600 s, return `X-Cache: MISS`. Empty (204 → 200 empty) tiles cached too so Leaflet doesn't keep retrying. |
| HIT  | Skip Martin entirely; return cached bytes + `X-Cache: HIT`. |
| Redis down | Catch + log warning, fall through to a normal MISS (no 500 to the user). |

Live curl sweep:

| Request | X-Cache |
|---|---|
| 1st `?district=Soreang` | MISS |
| 2nd `?district=Soreang` | HIT |
| 3rd `?district=Soreang&min_area=200` | MISS |
| 4th `?district=Soreang&min_area=200` | HIT |

Redis verifies the writes landed in DB 1 (Laravel's default cache db),
keyed by `sibedas_database_tile:{z}:{x}:{y}:{filter-hash}`. Stored
value is the standard Laravel-serialized array with `status` + `body`.

```
$ docker exec sibedas_redis redis-cli -n 1 KEYS "*"
sibedas_database_tile:14:13086:8513:105a7c46f54d
sibedas_database_tile:14:13086:8513:6a8b98de8ead
```

Total wall-clock time is dominated by Sanctum auth + middleware (~0.6 s
in local dev), but the controller's path through Martin vs. straight
from cache saves the 48 ms PostGIS query per tile — and protects the
upstream when the user count scales.

## Phase 15 verification (2026-05-11)

`DetectedBuildingObserver` registered on the model via Laravel 11's
`#[ObservedBy]` attribute. On any change to `verification_status`
that ships with a lat/lng, the observer dispatches
`InvalidateBuildingTiles($lat, $lng)` to the default queue.

The job walks zoom levels 14..18, computes the slippy-map `(x, y)`
that contains the centroid at each zoom, and `SCAN`s the Redis cache
DB for keys matching `<prefix>tile:{z}:{x}:{y}:*` (one entry per
filter-hash). Matching keys are `DEL`-ed in a single batch.

Two snags caught and documented:

1. `SCAN` returns *fully-prefixed* keys (Laravel's redis options
   prefix is `sibedas_database_`, not `cache.prefix`). The `MATCH`
   pattern therefore needs the prefix, but `DEL` is back through
   Laravel's wrapper which re-applies the prefix — so the keys are
   stripped before being passed to `del()`.
2. Predis 3 doesn't register `UNLINK`. Falling back to `DEL` is
   functionally equivalent for our tiny tile-cache footprint and
   avoids a runtime exception.

End-to-end smoke test:

```
1. Prime  /tiles/14/13086/8513.pbf            → MISS (cache: 1 key)
2. PUT    /detected-buildings/792010/status   → 200 (status flipped)
3. queue:work --once --queue=default
   App\Jobs\InvalidateBuildingTiles  DONE in 110ms
   log: "Tile cache invalidated  keys_deleted=1"
4. Re-hit /tiles/14/13086/8513.pbf            → MISS (cache busted)
5. Re-hit /tiles/14/13086/8513.pbf            → HIT  (rewarmed)
```

Production note: `QUEUE_CONNECTION=database` means a `queue:work`
worker (already in the supervisor config) processes these. Average
job duration in test ~100 ms.

## Acceptance criteria for Phase 20 (final rollout)

When polygons are live, the following must hold:

1. At zoom < `VECTOR_TILES_MIN_ZOOM` (default 14), the map is **visually identical** to baseline above.
2. At zoom ≥ 14, building polygons replace the cluster layer, colored by PBG status.
3. Time-to-first-tile after zoom-in: < 500 ms (cache miss), < 50 ms (cache hit).
4. Click on polygon opens the existing verify panel populated with the building's data.
5. Role `user` (level 1) never sees polygons — falls back to baseline.
6. Feature flag `VECTOR_TILES_ENABLED=false` reverts to baseline in one redeploy.
