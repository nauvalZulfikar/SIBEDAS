# Vector Tiles — Operator Runbook

Operational guide for the PostGIS + martin stack supporting building-footprint
polygons on the satellite monitoring dashboard. Read this before touching
prod.

## 1. Stack Overview

| Component | Container | Purpose |
|---|---|---|
| `postgis` | `sibedas_postgis` | Spatial DB holding `buildings` polygons (Phase 1+) |
| `martin`  | `sibedas_martin`  | Vector tile server reading from PostGIS (Phase 6+). Loopback-only on `:3000`; never expose publicly — Laravel TilesController (Phase 8) is the only public path. |
| `redis`   | `sibedas_redis`   | Tile cache for TilesController (Phase 14). 256 MB LRU, ephemeral (no AOF/RDB) — losing it is fine, next request just re-warms. Loopback-only on `:6379`. |

Existing services (`app`, `nginx`, `db`) are **unchanged**.

## 2. Phase 1 — PostGIS Sidecar

### Start
```bash
docker compose up -d postgis
```

### Verify
```bash
docker compose exec postgis psql -U sibedas_spatial -d sibedas_spatial \
  -c "SELECT PostGIS_Version(), version();"
```
Expected output: `3.4 USE_GEOS=1 USE_PROJ=1 USE_STATS=1 | PostgreSQL 16.4 …`

### Connect from Laravel
The `postgis` connection in `config/database.php` is wired but unused by the
app until later phases. Quick test:
```bash
php artisan tinker --execute="echo DB::connection('postgis')->select('SELECT PostGIS_Version() AS v')[0]->v . PHP_EOL;"
```
Requires `pdo_pgsql` extension. Bundled in the production Docker image; for
local (XAMPP) install, add `extension=pdo_pgsql` to `php.ini` or skip — the
app does not call this connection while `VECTOR_TILES_ENABLED=false`.

### Stop
```bash
docker compose stop postgis
```
The volume `sibedas_postgis_data` is retained — restarting resumes the same
database.

### Wipe (DESTRUCTIVE — only in dev)
```bash
docker compose down postgis
docker volume rm sibedas_sibedas_postgis_data
```

### Apply schema migrations
Production / staging (inside the app container):
```bash
docker compose exec app php artisan postgis:migrate
```
Dev (host XAMPP without `pdo_pgsql`) — pipe SQL directly:
```powershell
Get-Content database/migrations/postgis/001_create_buildings.sql -Raw |
  docker exec -i sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial
```
Both routes are idempotent. Migrations are tracked in the
`postgis_migrations` table.

### Sync buildings (Phase 3)

The command `buildings:sync-postgis` mirrors MySQL → PostGIS.

Production / staging (uses Laravel DB::connection('postgis')):
```bash
docker compose exec app php artisan buildings:sync-postgis
```

Dev (XAMPP without `pdo_pgsql`) — print SQL to stdout, pipe to psql:
```bash
php artisan buildings:sync-postgis --insert-batch=500 --via=stdout \
  2> /dev/null \
  | docker exec -i sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial -q
```

Options:
- `--limit=N` — cap rows (testing)
- `--chunk=5000` — MySQL read batch size
- `--insert-batch=500` — PostGIS upsert batch size
- `--from-id=N` — resume from a primary key (exclusive)
- `--via=auto|pdo|stdout` — output backend (auto picks pdo if extension loaded)

Sync is idempotent: rows are upserted on conflict by `id`. Re-runs only
refresh geometry / centroid / status_color / updated_at.

Expected timing (local, 1.18M rows): ~6 minutes wall-clock at default
batch sizes. Production should match or beat that.

### Scheduled sync (Phase 4)

Runs daily at **03:00 WIB**, after the daily PBB recompute at 02:00. The
schedule is pinned to `--via=pdo` so a missing pdo_pgsql extension errors
out cleanly instead of dumping raw SQL to the log file.

```
0 3 * * *  php artisan buildings:sync-postgis --via=pdo
```

Guards:
- `withoutOverlapping(120)` — 120 min lock prevents the next day's tick
  from starting if today's run is still going.
- `runInBackground()` — does not block other scheduled tasks.
- `appendOutputTo(storage/logs/buildings-sync.log)` — STDOUT + STDERR
  appended; trim the log periodically (logrotate / cron).
- `onFailure` — writes to Laravel log + Sentry (if bound).

Manual trigger (fires the job immediately, ignoring the cron):
```bash
docker compose exec app php artisan schedule:test --name="buildings:sync-postgis --via=pdo"
```

## 7. Phase 5 — Real Polygon Ingest (Google Open Buildings)

Until this phase, ~93% of `detected_buildings` had no real polygon and
the PostGIS mirror used a square envelope built from centroid + sqrt(area).
Phase 5 wires the importer to parse the `geometry` WKT column from
Google Open Buildings v3 so the real footprint replaces the square.

### Refresh the dataset

1. Install + authenticate gcloud:
   ```bash
   gcloud auth application-default login
   gcloud config set project <YOUR_GCP_PROJECT>
   ```
2. Run the download script:
   ```bash
   bash scripts/download_open_buildings.sh
   ```
   Writes `storage/app/open-buildings/bandung_buildings.csv` (~400-600 MB,
   ~1.1 M rows). First 1 TB/month of BigQuery scans are free; this query
   uses ~2 GB.

3. Re-import into MySQL (will prompt to delete the existing 1.09M Google
   rows first — say yes):
   ```bash
   php artisan buildings:import-open-buildings --source=google
   ```
   New flag `--skip-geometry` is available if you only want the
   centroid+area path. End-of-run summary now reports
   "with polygon: N (P%)".

4. Re-sync PostGIS so the mirror picks up real polygons:
   ```bash
   docker compose exec app php artisan buildings:sync-postgis
   # — or wait until 03:00 WIB
   ```

## 8. Phase 6 — Martin Tile Server

Martin auto-discovers every geometry column in the `public` schema and
publishes a source per column. With our `buildings` table that produces:

| Source | Geometry column | Used for |
|---|---|---|
| `buildings.1` | `geom` (Polygon) | The polygon layer (Phase 10). |
| `buildings`   | `centroid` (Point) | Reserved — possible dot fallback. |

### Start / stop
```bash
docker compose up -d martin    # boots after postgis is healthy
docker compose stop martin
docker compose restart martin  # re-reads config.yaml
```

### Verify
```bash
curl http://127.0.0.1:3000/health      # 200 "OK"
curl http://127.0.0.1:3000/catalog     # JSON listing sources
curl -o tile.pbf http://127.0.0.1:3000/buildings.1/16/52346/34054
file tile.pbf                          # → "data" (binary protobuf)
```

### Config

`docker/martin/config.yaml` is mounted read-only. Changes need
`docker compose restart martin`. Note the password is templated via
`${MARTIN_DATABASE_URL}` env so credentials never leak into the file.

### Resource budget

| Layer | RAM | CPU |
|---|---|---|
| martin | 64 MB reserved / 256 MB cap | 0.5 cap |

### Tile sources

| Path (Martin direct) | Source | Filterable | Notes |
|---|---|---|---|
| `/buildings.1/{z}/{x}/{y}` | Raw table column (Phase 6) | no | Auto-published; emits every column as a property |
| `/building_tile/{z}/{x}/{y}` | Function (Phase 7) | `?district=&status=&source=&min_area=` | **Backend uses this** — narrow property set, server-side filter, SRID-correct |

### Public-facing proxy (Phase 8)

The browser never hits Martin directly. `TilesController` exposes:

```
GET /api/tiles/buildings/{z}/{x}/{y}.pbf?district=&status=&source=&min_area=
```

Stack of guards:
- `auth:sanctum` — must have a Sanctum token cookie / Bearer.
- `pbb.clearance:level_2` — admin / superadmin only. Each request is
  written to `pbb_access_log`.
- `throttle:tiles` — 120 tiles/min/user (defined in `AppServiceProvider`).
- Feature flag — returns 503 if `VECTOR_TILES_ENABLED=false`.
- Min-zoom guard — returns 404 if `z < VECTOR_TILES_MIN_ZOOM` (default
  14) so a hand-crafted request can't pull multi-MB tiles.
- ETag — deterministic from `{z}/{x}/{y}` + filter hash; 304 on match.

CORS / nginx: Treat tile paths like any `/api/*` route. Cache-Control
is set on every successful response (`public, max-age=3600`); the
existing nginx config does not strip it.

### Tile size profile

Sampled at the Bandung-centre tile:

| Zoom | Bytes | Notes |
|---|---|---|
| 12 | 2.5 MB | Too heavy — Phase 11 hides the polygon layer below z14 |
| 14 | 442 KB | Acceptable as the entry zoom |
| 16 | 33 KB  | Smooth |
| 18 | 1.7 KB | Single-building scale |

### Detecting old square polygons

Squares have exactly 5 points (4 corners + closing point) AND are
`ST_IsRectangle`-true. Real footprints typically have 6+ points and are
not axis-aligned:
```sql
SELECT
  COUNT(*) FILTER (WHERE ST_NPoints(geom) > 5) AS real_polygons,
  COUNT(*) FILTER (WHERE ST_NPoints(geom) = 5) AS likely_squares,
  COUNT(*)                                     AS total
FROM buildings;
```
After Phase 5 refresh, `real_polygons` should jump from ~84k (OSM only)
toward ~1.1 M (OSM + Google).

## 3. Credentials & Secrets

All controlled via `.env`:

| Variable | Default | Notes |
|---|---|---|
| `POSTGIS_HOST` | `postgis` (in-network) or `127.0.0.1` (local) | Compose service name from the app container, loopback from the host |
| `POSTGIS_PORT` | `5432` | Exposed only on loopback; never bind to `0.0.0.0` |
| `POSTGIS_DB` | `sibedas_spatial` | |
| `POSTGIS_USER` | `sibedas_spatial` | |
| `POSTGIS_PASSWORD` | `changeme` in `.env.example` | **Rotate before first prod boot.** |

## 4. Resource Budget

| Component | RAM (soft) | RAM (hard) | Disk |
|---|---|---|---|
| PostGIS | 256 MB | 1 GB | starts ~80 MB, grows to ~2–3 GB after Phase 3 sync of 1.18 M buildings |

If the VPS is tight: drop the hard limit to 768 MB after Phase 3 and re-test
sync timing.

## 5. Feature Flag

```env
VECTOR_TILES_ENABLED=false   # production default until Phase 19 signoff
VECTOR_TILES_MIN_ZOOM=14     # tile layer activates at this zoom level
```

When `false`:
- App never queries PostGIS.
- Frontend renders the existing dot+cluster layer only.
- PostGIS container can stay running idle (cheap) or stay stopped.

Toggling does **not** require a code deploy — just `php artisan config:clear`
and reload.

## 6. Rollback

At any phase, reverting is one of:

| Phase scope | Rollback |
|---|---|
| 0–1 (this commit) | `git revert` the Phase commits or set `VECTOR_TILES_ENABLED=false`. PostGIS container can be left running idle. |
| Later phases | See per-phase rollback sections (added as phases land). |
