# Vector Tiles — Operator Runbook

Operational guide for the PostGIS + martin stack supporting building-footprint
polygons on the satellite monitoring dashboard. Read this before touching
prod.

## 1. Stack Overview

| Component | Container | Purpose |
|---|---|---|
| `postgis` | `sibedas_postgis` | Spatial DB holding `buildings` polygons (Phase 1+) |
| `martin` | `sibedas_martin` | Vector tile server reading from PostGIS (Phase 6+) |

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
