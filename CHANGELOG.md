# Changelog

All notable changes to Sibedas. PBB Reconciliation module = Phases 0-15 (current: 1-13 done).

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
