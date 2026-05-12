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

## Acceptance criteria for Phase 20 (final rollout)

When polygons are live, the following must hold:

1. At zoom < `VECTOR_TILES_MIN_ZOOM` (default 14), the map is **visually identical** to baseline above.
2. At zoom ≥ 14, building polygons replace the cluster layer, colored by PBG status.
3. Time-to-first-tile after zoom-in: < 500 ms (cache miss), < 50 ms (cache hit).
4. Click on polygon opens the existing verify panel populated with the building's data.
5. Role `user` (level 1) never sees polygons — falls back to baseline.
6. Feature flag `VECTOR_TILES_ENABLED=false` reverts to baseline in one redeploy.
