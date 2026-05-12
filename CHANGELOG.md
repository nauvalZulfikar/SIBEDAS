# Changelog

All notable changes to Sibedas. PBB Reconciliation module = Phases 0-15 (current: 1-13 done). Vector-tiles polygon layer = separate 20-phase track (Phases 0-17 done at time of writing; staging + production deploy pending).

## [2026-05-11] — Vector-Tiles Polygon Layer Phases 0-17

### Added
- **Phase 17** — Test suite: `tests/Unit/WktParserTest.php`, `tests/Unit/TileCoordMathTest.php`, `tests/Feature/TilesControllerTest.php` (16 cases / 35 assertions / 0.8 s).
- **Phase 16** — Frontend role gate: `<meta name="user-clearance">` from `session('pbb_clearance')`; satellite-monitoring view skips polygon layer + hides mode pill for level_1 users.
- **Phase 15** — Cache invalidation: `DetectedBuildingObserver` + `App\Jobs\InvalidateBuildingTiles` walk z=14..18, SCAN/DEL Redis keys matching `tile:{z}:{x}:{y}:*` on verify.
- **Phase 14** — Redis tile cache: `redis:7-alpine` sidecar (256 MB LRU), predis composer dep, TilesController wraps Martin in `Cache::store('redis')` with `tile:{z}:{x}:{y}:{filter-hash}` keys, 1-hour TTL, `X-Cache: HIT/MISS` response header.
- **Phase 13** — Filter forwarding: `buildPolygonTileUrl()` reads `filter-district` + `filter-min-area`; debounced (300 ms) `refreshPolygonLayer()` rebuilds the layer.
- **Phase 12** — Click-to-verify: `polygonLayer.on('click', …)` highlights + opens the existing `#verify-panel`; verify buttons repaint via `setFeatureStyle` immediately.
- **Phase 11** — Zoom-aware switch: cluster mode below z14, polygon mode at z14+; CSS 200 ms opacity transition; `Cluster ↔ Polygon` pill in the map header.
- **Phase 10** — Polygon layer wired: `L.vectorGrid.protobuf('/api/tiles/buildings/{z}/{x}/{y}.pbf', …)` with canvas renderer, status-color style, min zoom 14.
- **Phase 9** — `leaflet.vectorgrid@^1.3.0` added to package.json; bundled distribution loaded from unpkg to match existing Leaflet pattern; `window.__vectorGridReady` smoke check.
- **Phase 8** — Laravel tile proxy: `app/Http/Controllers/Api/TilesController.php`, route `GET /api/tiles/buildings/{z}/{x}/{y}.pbf`, behind `auth:sanctum` + `pbb.clearance:level_2` + `throttle:tiles` (120/min/user). ETag → 304, deterministic by filter hash. Audit log entry per request.
- **Phase 7** — PostGIS tile function `building_tile(z, x, y, query_params json)` returns MVT bytes; filterable on district / status / source / min_area; transforms envelope back to 4326 for GIST index hit (SRID dance).
- **Phase 6** — Martin tile server (`ghcr.io/maplibre/martin:v0.14.2`) with auto-publish + 50 k feature cap; loopback-only on 3000; tile-size profile recorded per zoom.
- **Phase 5** — WKT polygon ingestion: `App\Support\WktParser` (POLYGON / MULTIPOLYGON / holes); `buildings:import-open-buildings` parses the optional `geometry` column from Google BigQuery exports; `scripts/download_open_buildings.sh` wrapping `bq query`.
- **Phase 4** — Daily 03:00 WIB sync via `routes/console.php` (`buildings:sync-postgis --via=pdo`), `withoutOverlapping(120)`, background, sentry-on-failure.
- **Phase 3** — Sync command `buildings:sync-postgis` (1.18 M rows in ~6 min) with dual backend (`--via=pdo|stdout`) so the local XAMPP build without `pdo_pgsql` can pipe SQL to docker exec psql.
- **Phase 2** — `database/migrations/postgis/001_create_buildings.sql` schema + idempotent runner `php artisan postgis:migrate` with tracking table.
- **Phase 1** — PostGIS sidecar (`postgis/postgis:16-3.4-alpine`), Laravel `postgis` connection in `config/database.php`, Dockerfile installs `pdo_pgsql` for production image.
- **Phase 0** — Feature flag `VECTOR_TILES_ENABLED` in `config/features.php` + `docs/vector-tiles/BASELINE.md` (running phase ledger).

### Schema changes
- New PostGIS database `sibedas_spatial`. Tables `buildings`, `postgis_migrations`.
- No changes to existing MySQL schema.
- `roles.pbb_clearance` is reused (introduced earlier for PBB reconciliation).

### Files of note
- `docs/vector-tiles/ARCHITECTURE.md` — end-to-end diagram
- `docs/vector-tiles/RUNBOOK.md` — start/stop/wipe/troubleshoot
- `docs/vector-tiles/BASELINE.md` — per-phase verification ledger
- `docker-compose.yml` — three new services (postgis, martin, redis)
- `config/features.php`, `config/services.php` (martin host)

### Operator notes
- Production rollout (Phases 18–20) still pending. Feature flag stays `false` in `.env.example` and prod `.env` until staging signoff.
- The 1.18 M-row PostGIS backfill takes ~6 minutes; run once during a low-traffic window before flipping the flag.
- `redis:7-alpine` is ephemeral (`--save ''  --appendonly no`); losing the cache is fine, the next request rewarms.

---

## [2026-05-05] — PBB Module Phase 5-13

### Added
- **Phase 13** — Staging deploy artifacts: public health endpoint `GET /api/health/pbb` (6 component checks, returns 200/503 for monitoring probes), `.env.staging.example` template (separate DB / cache prefix / session cookie), idempotent `scripts/deploy-staging.sh`, full step-by-step `docs/pbb/DEPLOY_STAGING.md` playbook (DNS+SSL, DB setup, data import, cron / systemd timer, nginx vhost, 5-user account creation, smoke test, monitoring, rollback).
- **Phase 12** — Module documentation: `docs/pbb/USER_GUIDE.md`, `docs/pbb/RUNBOOK.md`, `docs/pbb/DEVELOPER.md`, this `CHANGELOG.md`.
- **Phase 11** — 27 new tests pinning masking, RBAC, export shape, UI hide. `php artisan test` → 40 passing.
- **Phase 10** — Export & reporting: PDF (DomPDF) / Excel (4-sheet maatwebsite) / CSV (streamDownload). Monthly `pbb:snapshot-reconciliation` scheduler. Frontend Export dropdown with PII Masked badge.
- **Phase 9** — Fine-grained RBAC: `roles.pbb_clearance` enum (level_1..3), `EnsurePbbClearance` middleware (alias `pbb.clearance:level_X`), `pbb_access_log` table, PII masking helper (UU PDP), clearance-driven UI hide.
- **Phase 8** — Frontend dashboard `/dashboards/reconciliation`: 4 KPI cards, ApexCharts bar per kec, drill-down kelurahan modal with coverage badge, audit list tab, recompute btn (admin).
- **Phase 7** — Spatial linking (partial): OSM Overpass → 63 kelurahan polygons → PIP for `building_ward_name`. Per-kelurahan sat_count surfaced via API with `coverage_status` flag.
- **Phase 6** — API endpoints: `GET /api/reconciliation/{summary,per-kec,kelurahan/{kec},no-satellite-nop,no-nop-satellite}` + `POST /api/reconciliation/recompute`. Postman collection at `docs/postman/sibedas-pbb-reconciliation.json`.
- **Phase 5** — `PbbReconciliationService` (5 cached methods, TTL 1h) + `reconciliation_summary` materialized table + daily 02:00 recompute scheduler.

### Schema changes
- `roles.pbb_clearance` ENUM('level_1','level_2','level_3') NOT NULL DEFAULT 'level_1'
- New table `pbb_access_log` (audit trail)
- New table `reconciliation_summary` (materialized)
- `reconciliation_summary.gap_pct` widened DECIMAL(6,2) → (10,2)

### Files of note
- `app/Services/PbbReconciliationService.php`
- `app/Http/Controllers/Api/ReconciliationController.php`
- `app/Http/Controllers/Dashboards/ReconciliationController.php`
- `app/Http/Middleware/EnsurePbbClearance.php`
- `app/Console/Commands/{RecomputeReconciliationCommand,SnapshotReconciliationCommand}.php`
- `app/Exports/ReconciliationExport.php`
- `resources/views/dashboards/reconciliation.blade.php`
- `resources/views/exports/reconciliation_report.blade.php`
- `resources/js/dashboards/reconciliation.js`

## [2026-05-04 / 2026-05-05] — PBB Module Phase 0-4

### Added
- **Phase 4** — OSM Overpass building ingest (`buildings:import-osm`) for 3 kec missing from Microsoft footprint dataset.
- **Phase 3** — Satellite ingest bbox fix: clipped from `[-7.20,-6.80] × [107.30,107.80]` to `[-7.32,-6.80] × [107.23,107.96]` to cover east Cicalengka/Nagreg/Cikancung.
- **Phase 2** — Lookup tables `pbb_kecamatan_lookup` (31 rows) + `pbb_kelurahan_lookup` (275 rows) auto-populated from imported NOP groupby.
- **Phase 1** — `pbb_records` table + `pbb:import {path}` command for streaming xlsx/csv ingest. Imported 1,152,326 rows.
- **Phase 0** — DJP-PBB → BPS regency code mapping derivation (32.06 ↔ 32.04, 31 kec, 275 kelurahan).

### Schema changes
- New tables: `pbb_records`, `pbb_kecamatan_lookup`, `pbb_kelurahan_lookup`
- `detected_buildings` + `pbb_record_id` (nullable, populated by Phase 7+)
- `spatial_plannings` + `nop` (nullable, NOP linkage)

## Earlier (pre-PBB Module)

Sibedas pre-existing modules: PBG, retribusi, satellite monitoring, advertisement, UMKM, tourism, spatial planning, taxation, customers, big data resume. See git history for incremental changes prior to 2026-05-04.
