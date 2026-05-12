# Modul Rekonsiliasi PBB — Operations Runbook

Untuk **Admin / IT / SRE** yang maintain Sibedas PBB module di prod & dev. Ini bukan dokumen onboarding — ini quick-reference saat ada masalah jam 3 pagi.

---

## 1. Architecture overview

```
┌──────────────────┐     ┌──────────────────────┐     ┌────────────────────┐
│  DATA PBB.xlsx   │     │  Citra Satelit       │     │  SIMBG (PBG)       │
│  (1.15M NOP)     │     │  (Microsoft, OSM)    │     │  status terbit     │
└─────────┬────────┘     └──────────┬───────────┘     └─────────┬──────────┘
          │                         │                            │
   pbb:import                buildings:import-osm        scrape job (existing)
          │                         │                            │
          ▼                         ▼                            ▼
┌─────────────────┐     ┌─────────────────────┐       ┌──────────────────┐
│ pbb_records     │     │ detected_buildings  │       │ pbg_task         │
│ + lookup tables │     │  (1.18M rows)       │       │ + pbg_task_dets  │
└────────┬────────┘     └──────────┬──────────┘       └────────┬─────────┘
         │                         │                            │
         └─────────────────┬───────┴────────────────────────────┘
                           │
                  pbb:recompute-reconciliation
                  (daily 02:00 + manual via API)
                           │
                           ▼
                ┌──────────────────────┐
                │ reconciliation_summary│
                │ (307 rows materialized)│
                └──────────┬───────────┘
                           │ Cache (1h TTL)
                           ▼
              /api/reconciliation/*  →  /dashboards/reconciliation
              (clearance-tiered, audit-logged)

                  pbb:snapshot-reconciliation
                  (monthly day-1 03:00)
                           │
                           ▼
              storage/app/private/exports/reconciliation/*.xlsx
              (24-month retention)
```

---

## 2. Artisan commands

| Command | When to run | Duration | Idempotent |
|---|---|---|---|
| `pbb:import {path} --truncate --chunk=2000` | After receiving new PBB Excel from Bapenda | ~3 min for 1.15M rows | Yes, NOP UNIQUE constraint |
| `buildings:import-osm --bbox=... --district=...` | When need supplemental satellite outside Microsoft footprint coverage | ~5-10 min | Yes |
| `pbb:recompute-reconciliation` | After any data import OR daily auto via scheduler | ~4 sec | Yes, always full TRUNCATE+INSERT |
| `pbb:snapshot-reconciliation [--retain=24]` | Monthly auto OR manual archival | ~3 sec | Yes (overwrites month file) |

```bash
# Trigger recompute manually (dev / debug)
cd /opt/sibedas && php artisan pbb:recompute-reconciliation

# View recent snapshots
ls -la storage/app/private/exports/reconciliation/
```

---

## 3. Schedule (`routes/console.php`)

```php
Schedule::command("app:start-scraping-data --confirm")->dailyAt("00:00");
Schedule::command("app:monitor-scraping")->everyThirtyMinutes();
Schedule::command("pbb:recompute-reconciliation")->dailyAt("02:00");
Schedule::command("pbb:snapshot-reconciliation")->monthlyOn(1, "03:00");
```

**Cron prerequisite** (Linux prod):
```bash
* * * * * cd /opt/sibedas && php artisan schedule:run >> /dev/null 2>&1
```

Verify scheduler is alive:
```bash
php artisan schedule:list
```

---

## 4. Common failures & fixes

### 🔴 Recompute fails: "MySQL refused connection"

```
SQLSTATE[HY000] [2002] No connection could be made
```

**Linux prod:**
```bash
systemctl status mysql
systemctl restart mysql
```

**Dev (XAMPP):** Start mysqld via XAMPP control panel or:
```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe"
```

### 🔴 Recompute fails: "Numeric value out of range gap_pct"

Migration `2026_05_05_002000_widen_gap_pct...` should have made `DECIMAL(10,2)`. If new fresh DB, migrate:
```bash
php artisan migrate
```

### 🔴 Snapshot tidak ke-generate

Cek file di `storage/app/private/exports/reconciliation/`. Kalau missing:
```bash
# Manual trigger
php artisan pbb:snapshot-reconciliation -vvv

# Cek permission (prod)
ls -la storage/app/private/exports/
chown -R www-data:www-data storage/app/private/exports/
```

### 🔴 Endpoint `/api/reconciliation/*` returns 401

User sanctum token expired or session lost. Login ulang. Untuk debug:
```bash
php artisan tinker
> $u = User::where('email', 'admin@bapenda.go.id')->first();
> $u->createToken('debug')->plainTextToken
```

### 🔴 Page `/dashboards/reconciliation` blank / JS error

```bash
# Rebuild assets
npm run build

# Cek hot file (kalau ada di prod, bug)
ls public/hot && rm public/hot

# Cek manifest exists
ls public/build/manifest.json
```

### 🔴 Building point-in-polygon ngga jalan

Phase 1 + Phase 7 ingest dependency:
```bash
# 1. Pastikan Python env ready
pip install shapely mysql-connector-python

# 2. Re-run kelurahan PIP
cd /opt/sibedas
python scripts/populate_kelurahan.py
```

---

## 5. Backup strategy

### Tables to backup

```sql
-- Critical (cannot rebuild without source files)
pbb_records                  -- 1.15M rows, ~150MB
detected_buildings           -- 1.18M rows, ~200MB

-- Derived (can re-recompute)
pbb_kecamatan_lookup         -- 31 rows
pbb_kelurahan_lookup         -- 275 rows
reconciliation_summary       -- 307 rows

-- Compliance (KEEP — UU PDP audit trail)
pbb_access_log               -- variable, retention 90 days
```

### Backup commands

```bash
# Daily mysqldump
mysqldump --no-tablespaces sibedas \
  pbb_records pbb_kecamatan_lookup pbb_kelurahan_lookup \
  detected_buildings reconciliation_summary pbb_access_log \
  > /backup/sibedas-pbb-$(date +%F).sql

# Compress
gzip /backup/sibedas-pbb-*.sql

# Retention: keep 30 days
find /backup -name "sibedas-pbb-*.sql.gz" -mtime +30 -delete
```

### Restore drill

```bash
# 1. Restore SQL dump
gunzip < backup.sql.gz | mysql sibedas

# 2. Re-run derived recompute
php artisan pbb:recompute-reconciliation
```

---

## 6. Audit log queries

Logs disimpan di `pbb_access_log` table, indexed by `(accessed_at, clearance_required)`.

```sql
-- Siapa akses data PII (level_2/3) hari ini?
SELECT user_email, endpoint, COUNT(*) as cnt
FROM pbb_access_log
WHERE accessed_at >= CURDATE()
  AND clearance_required IN ('level_2','level_3')
GROUP BY user_email, endpoint
ORDER BY cnt DESC;

-- Akses gagal (403) — mungkin butuh klarifikasi role
SELECT user_email, endpoint, accessed_at, ip
FROM pbb_access_log
WHERE response_status = 403
  AND accessed_at >= NOW() - INTERVAL 7 DAY
ORDER BY accessed_at DESC
LIMIT 50;

-- Recompute pattern (debug timing)
SELECT user_email, accessed_at, response_status
FROM pbb_access_log
WHERE endpoint = 'api.reconciliation.recompute'
ORDER BY accessed_at DESC LIMIT 20;
```

### Retention cleanup

> **TODO Phase 12.5**: belum ada cron cleanup. Recommended:
```sql
-- Manual prune > 90 days
DELETE FROM pbb_access_log WHERE accessed_at < NOW() - INTERVAL 90 DAY;
```

---

## 7. Performance reference

Baseline (1.15M PBB + 1.18M satellite + 76 PBG):

| Operation | Cold (no cache) | Warm (cache) |
|---|---|---|
| `getKabSummary()` | ~20 ms | ~1 ms |
| `getPerKec()` | ~150 ms | ~2 ms |
| `getPerKelurahan('Soreang')` | ~80 ms | ~2 ms |
| `recompute()` (full) | ~4000 ms | n/a |
| Excel export (4 sheet) | ~700 ms | n/a |
| PDF export (1 page) | ~500 ms | n/a |
| Snapshot CLI (Excel save) | ~3000 ms | n/a |

**If recompute > 30 sec**, investigate slow queries:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
-- Reproduce, then read slow_query_log file
```

---

## 8. Disk space monitoring

| Path | Approx size | Growth |
|---|---|---|
| `pbb_records` table | ~150 MB | Monthly batch import |
| `detected_buildings` table | ~200 MB | Quarterly satellite update |
| `storage/app/private/data-pbb/` | ~150 MB (decoded CSV + JSON) | Static |
| `storage/app/private/exports/reconciliation/` | ~50 KB × 24 months = ~1.2 MB | Monthly |
| `pbb_access_log` | ~1 KB/row, ~10k rows/month | ~10 MB/month |

---

## 9. Phase rollup status

| Phase | Status | Date | Owner |
|---|---|---|---|
| 0 — Mapping derivation | ✅ Done | 2026-05-04 | dev |
| 1 — PBB ingest | ✅ Done | 2026-05-04 | dev |
| 2 — Lookup tables | ✅ Done | 2026-05-04 | dev |
| 3 — Satellite bbox fix | ✅ Done | 2026-05-04 | dev |
| 4 — OSM supplemental | ✅ Done | 2026-05-05 | dev |
| 5 — Reconciliation service | ✅ Done | 2026-05-05 | dev |
| 6 — API endpoints | ✅ Done | 2026-05-05 | dev |
| 7 — Spatial linking | ⚠️ Partial (data gap) | 2026-05-05 | dev |
| 8 — Frontend dashboard | ✅ Done | 2026-05-05 | dev |
| 9 — RBAC fine-grained | ✅ Done | 2026-05-05 | dev |
| 10 — Export & reporting | ✅ Done | 2026-05-05 | dev |
| 11 — Testing & QA | ✅ Done | 2026-05-05 | dev |
| 12 — Documentation | ✅ Done | 2026-05-05 | dev |
| 13 — Staging deploy | ⏳ Pending | — | TBD |
| 14 — Production deploy | ⏳ Pending | — | TBD |
| 15 — Iteration & feedback | ⏳ Pending | — | TBD |

Detail per-phase: `storage/app/private/data-pbb/PHASE_*_REPORT.md`.

---

## 10. Hubungan emergency

- **DPUTR IT**: maintain server / network
- **Bapenda IT**: data PBB integrity / role assignment
- **Dev lead**: bug code / arsitektur
- **Pak Camat**: stakeholder primer untuk angka per-kecamatan
