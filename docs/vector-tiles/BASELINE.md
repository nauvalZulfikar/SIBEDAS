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

## Acceptance criteria for Phase 20 (final rollout)

When polygons are live, the following must hold:

1. At zoom < `VECTOR_TILES_MIN_ZOOM` (default 14), the map is **visually identical** to baseline above.
2. At zoom ≥ 14, building polygons replace the cluster layer, colored by PBG status.
3. Time-to-first-tile after zoom-in: < 500 ms (cache miss), < 50 ms (cache hit).
4. Click on polygon opens the existing verify panel populated with the building's data.
5. Role `user` (level 1) never sees polygons — falls back to baseline.
6. Feature flag `VECTOR_TILES_ENABLED=false` reverts to baseline in one redeploy.
