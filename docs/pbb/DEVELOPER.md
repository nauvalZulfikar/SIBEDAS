# Modul Rekonsiliasi PBB — Developer Guide

For engineers extending or maintaining the PBB module. Assumes Laravel 11 + MariaDB familiarity. For ops/runbook, see `RUNBOOK.md`. For end-user, see `USER_GUIDE.md`.

---

## 1. ERD

```
                    pbb_kecamatan_lookup (PK djp_code char4)
                         │ djp_code
                         │
   ┌─────────────────────┼─────────────────────┐
   │                                            │
   │ kecamatan_djp_code                         │ djp_kec_code
   ▼                                            ▼
pbb_records                              pbb_kelurahan_lookup
  - nop UNIQUE (PK)                       - djp_kec_code, djp_desa_code (composite UNIQUE)
  - nama_wp, alamat                       - kelurahan_name
  - terbangun_flag                        - nop_count, terbangun_count
  - luas_bumi, luas_bangunan              - sum_luas_bangunan_m2
  - kecamatan_name, kelurahan_name
  ...
   │ pbb_record_id (FK, populated Phase 7+)
   ▼
detected_buildings
  - latitude, longitude, estimated_area_m2
  - detection_source (microsoft_footprints / sentinel_cv / osm_buildings / pdam_estimate)
  - kecamatan (BPS PIP, Title Case)
  - building_ward_name (kelurahan via kelurahan_kab_bandung.geojson PIP)
  - matched_pbg_task_id (FK to pbg_task)

reconciliation_summary (materialized, refreshed by pbb:recompute-reconciliation)
  - scope ENUM('kab','kec','kelurahan')
  - kecamatan_name, kelurahan_name (nullable for scope=kab)
  - pbb_total, pbb_terbangun, pbb_lahan_kosong
  - sat_count, sat_area_m2
  - pbg_terbit_count
  - gap_sat_minus_terbangun BIGINT (signed, can be negative)
  - gap_pct DECIMAL(10,2)
  - UNIQUE(scope, kecamatan_name, kelurahan_name)

roles
  - pbb_clearance ENUM('level_1','level_2','level_3')

pbb_access_log
  - user_id, user_email, clearance_required, endpoint, method, ip, ua,
    query_params (JSON), response_status, accessed_at
  - INDEX(accessed_at, clearance_required)
```

---

## 2. Service-layer API

### `App\Services\PbbReconciliationService`

```php
public function getKabSummary(): array          // cached 1h, key: pbb_recon_v1:kab
public function getPerKec(): array              // cached 1h, key: pbb_recon_v1:per_kec
public function getPerKelurahan(string $kec): array  // cached 1h, key: pbb_recon_v1:per_kel:UPPERKEC
public function getNopWithoutSatellite(int $limit, int $offset): array  // not cached
public function getSatelliteWithoutNop(int $limit, int $offset): array  // not cached
public function recompute(): array              // truncate + repopulate reconciliation_summary, busts caches
```

### Cache strategy

- **Read methods** cached 1 hour (TTL `CACHE_TTL_SEC = 3600`)
- **Cache key prefix**: `pbb_recon_v1:` (bump version saat ubah shape)
- `recompute()` busts `kab`, `per_kec`, dan **semua** `per_kel:*` keys via DB enumeration

```php
// Bumping cache version (e.g. add new field):
private const CACHE_KEY_PREFIX = 'pbb_recon_v2:';  // ← incremented from v1
```

### Recompute internals

```
recompute():
  1. SET FOREIGN_KEY_CHECKS=0
  2. TRUNCATE reconciliation_summary
  3. INSERT scope='kab' row (1)
  4. foreach kec: INSERT scope='kec' row (31)
  5. cursor() over pbb_kelurahan_lookup × pbb_kecamatan_lookup join
     → INSERT scope='kelurahan' rows (275)
  6. SET FOREIGN_KEY_CHECKS=1
  7. Cache::forget for all known keys
  8. return ['rows_inserted', 'elapsed_ms', 'computed_at']
```

Total: 307 rows in ~4 seconds (laptop dev MariaDB 10.4 + XAMPP).

---

## 3. RBAC middleware

### `App\Http\Middleware\EnsurePbbClearance`

Alias: `pbb.clearance` (registered in `bootstrap/app.php`).

```php
Route::middleware('pbb.clearance:level_2')->group(function () {
    Route::get('/api/foo', ...);
});
```

### Clearance resolution

```
resolveUserClearance($user):
  1. If $user is null → 'level_1' (anonymous default-deny)
  2. Pluck pbb_clearance from all $user->roles()
  3. Return rank-by-MAX (highest privilege wins)
  4. Unknown clearance values are treated as rank 0 (no escalation)
```

### Audit logging

| Trigger | Logged? |
|---|---|
| Hit endpoint with `level_1` middleware | NO (high volume) |
| Hit endpoint with `level_2` or `level_3` middleware | YES |
| 403 response (any level) | YES |
| Failure to log (DB down, etc) | swallowed try/catch |

Log row data:
```
user_id, user_email, clearance_required, endpoint (route name),
method, ip, user_agent (trunc 255), query_params (JSON), response_status, accessed_at
```

---

## 4. Frontend pattern

### Token flow

```
User logs in (web session)
  → controller injects $pbbClearance to view
  → blade: window.RECON_TOKEN = "{{ session('api_token') }}"
  → blade: window.RECON_CLEARANCE = "{{ $pbbClearance }}"
  → JS reads window.RECON_TOKEN, sends as Bearer header to /api/*
  → Sanctum middleware authenticates request
  → pbb.clearance middleware authorizes by level
```

### Per-clearance UI hide pattern

```blade
@php
    $rank = ['level_1' => 1, 'level_2' => 2, 'level_3' => 3];
    $myLvl = $rank[$pbbClearance ?? 'level_1'];
@endphp

@if ($myLvl >= 2)
    <button>Audit Tab</button>
@endif

@if ($pbbClearance === 'level_3')
    <button id="btn-recompute">Recompute</button>
@endif
```

### Adding a new chart type

ApexCharts already loaded via `package.json`. Pattern:

```js
import ApexCharts from "apexcharts";

const chart = new ApexCharts(document.getElementById("my-chart"), {
    chart: { type: "bar", height: 360 },
    series: [{ name: "Foo", data: [...] }],
    xaxis: { categories: [...] },
});
chart.render();
```

---

## 5. Adding a new export format

1. New `App\Exports\Foo` class implementing `WithMultipleSheets` (or `FromArray` for single-sheet)
2. New controller method using `Excel::download(new Foo(), 'name.xlsx')`
3. New route under `pbb.clearance:level_X` group in `routes/api.php`
4. Frontend dropdown item `data-export="foo"` + JS handler in `triggerDownload()`

PDF: same pattern but `Pdf::loadView('exports.foo', $data)->download(...)`.

---

## 6. Adding a new endpoint

Checklist:

- [ ] Decide clearance level (`level_1` / `level_2` / `level_3`)
- [ ] Route in `routes/api.php` under correct middleware sub-group
- [ ] Controller method (return `JsonResponse`, structure: `{data, meta?, note?}`)
- [ ] If returns PII → masking helper from controller `maskPii([...fields])`
- [ ] Update `tests/Feature/ReconciliationApiClearanceTest.php` with 1 test per clearance tier
- [ ] Update `docs/postman/sibedas-pbb-reconciliation.json` with new request
- [ ] Update `docs/pbb/USER_GUIDE.md` if user-facing
- [ ] Update OpenAPI/Swagger spec — TODO not yet introduced

---

## 7. Spatial linking (Phase 7) limitations

**Current state**: 63 OSM kelurahan polygons cover ~22% of 275 PBB kelurahan. Building point-in-polygon hit rate ~1.2% in covered kec (polygons are tiny).

**Why we accepted this**: alternatives (Voronoi, GADM, manual digitize) all worse for now.

**Recommended Phase 7+ paths**:

1. **Bapenda peta blok PBB GIS** — authoritative kelurahan-level + sub-blok. Submit formal request. Est: 2-4 weeks waiting.
2. **BPS request peta wilayah desa** — should be public-ish. Est: 1-2 weeks back-and-forth.
3. **Manual digitize 212 missing polygons** in QGIS by intern with BPS reference. Est: 30-50 staff hours / 1 week.

When new polygons arrive:

```bash
# 1. Place new geojson at public/data/kelurahan_kab_bandung.geojson
# 2. Re-run PIP
python scripts/populate_kelurahan.py

# 3. Recompute
php artisan pbb:recompute-reconciliation
```

---

## 8. Test infrastructure

Existing tests rely on **production-shaped local MySQL** (no separate test DB). To run:

```bash
php artisan test                                          # full suite (40)
php artisan test --filter ReconciliationApiClearanceTest  # specific
```

### Adding tests

| Type | Location | Pattern |
|---|---|---|
| Pure logic (no DB) | `tests/Unit/Pbb*.php` | `extends PHPUnit\Framework\TestCase` (NOT Laravel) |
| HTTP feature | `tests/Feature/Reconciliation*.php` | `extends Tests\TestCase` + `Sanctum::actingAs($u)` |
| View rendering | same as Feature | `#[RunInSeparateProcess]` if hits `layouts.partials.sidebar` (global function redeclaration issue) |

### Test data dependencies

Tests assume seeded:
- 4 roles in `roles` table with `pbb_clearance` set (Phase 9 migration)
- 1 `superadmin@sibedas.com` user
- 1 `l2test@sibedas.local` user (Phase 9)
- 1 `user@demo.com` user (Phase 0 seed)
- `pbb_records` populated (Phase 1 — `pbb:import`)
- `reconciliation_summary` populated (Phase 5 — `pbb:recompute-reconciliation`)

---

## 9. Common pitfalls

### MySQL placeholder limit (`Error 1390`)

`pbb:import` chunks 2000 rows × 15 cols = 30000 placeholders (under 65535 limit).

**If chunk too large**: error 1390. Solution: lower `--chunk` flag.

### MariaDB BIGINT UNSIGNED arithmetic

```sql
-- BAD: BIGINT UNSIGNED - BIGINT UNSIGNED can overflow
SELECT sat_count - terbangun_count FROM ...;

-- GOOD: cast to SIGNED first
SELECT CAST(sat_count AS SIGNED) - CAST(terbangun_count AS SIGNED) FROM ...;
```

### Decimal precision overflow

`gap_pct` ALTER from `(6,2)` to `(10,2)` (Phase 10 migration) because partial polygon coverage in Phase 7 caused undercount → gap_pct as low as -3000% which exceeds `(6,2)` range (±9999.99).

### Sidebar partial global functions

`layouts.partials.sidebar.blade.php` declares `isActiveMenu()` and `renderMenu()` at global scope inside `@php` block. Rendering twice in same PHP process throws "Cannot redeclare". Tests use `#[RunInSeparateProcess]` to bypass.

> **Future fix**: refactor to closures or move to a Blade component. Out of scope for PBB module.

---

## 10. Roadmap (Phase 13-15)

| Phase | Scope |
|---|---|
| 13 | Staging deploy: Bapenda QA env, smoke test with 3-5 staff users |
| 14 | Production deploy: prod cron, health check, monitoring alert |
| 15 | Iteration: feedback loop, Phase 7+ polygon coverage upgrade, RW-level support if Bapenda exposes |

See `storage/app/private/data-pbb/PHASE_*_REPORT.md` for per-phase deltas.
