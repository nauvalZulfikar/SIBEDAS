# Agents — Sibedas PBG

**Project**: Sibedas (Sistem Informasi Bedah Daerah) — internal data & permit-management platform untuk **Dinas Pekerjaan Umum dan Tata Ruang (DPUTR) Kabupaten Bandung**.

**Domain core**: PBG (Persetujuan Bangunan Gedung — building permits) — input, status tracking, retribusi, payment recap, reporting. Plus data layer untuk spatial planning, UMKM, pariwisata, reklame, dan deteksi bangunan satelit.

**Stack**: Laravel 11 · PHP 8.2 · MariaDB 10.6 · Vite 5 · Bootstrap 5 + Tailwind 3 · Docker
**Live URL**: `https://sibedaspbg.aureonforge.com`
**VPS**: `root@72.60.196.21` → `/root/projects/sibedaspbg/`
**Agent ID**: `sibedas`

---

## Project Overview

Web app for Kab. Bandung DPUTR. Core functions:
- **PBG management** — building permit applications (input, status, lampiran, retribusi, pembayaran)
- **SIMBG sync** — scrape national building portal, sync 2-way to internal DB
- **Google Sheets sync** — bidirectional sync of selected PBG records
- **AI chatbot** (OpenAI gpt-4o-mini) — user-facing + leadership ("Chatbot Pimpinan")
- **Retribution engine** — complex fee calculation from spatial planning indices, building dims, function class
- **Satellite building detection** — Microsoft/Google Open Buildings + GEE imagery, point-in-polygon match to PBG → flag bangunan tanpa izin (~1M detected)
- **Public search** — citizen-facing parcel/building lookup
- **Reports** — Excel/PDF export per modul (PBG PTSP, payment recap, growth, dst.)

---

## Repository Layout (Canonical)

```
Sibedas/
├── app/
│   ├── Console/Commands/         # CLI: scrapers, sync, calc, satellite imports
│   ├── Http/Controllers/         # ~30 controllers, organized by feature
│   ├── Jobs/                     # Queued background work
│   ├── Models/                   # Eloquent models (~40)
│   ├── Services/                 # OpenAIService, ServiceGoogleSheet, RetributionCalculatorService, ServicePbgTask, ServiceTabPbgTask, ServiceTokenSIMBG
│   └── Traits/                   # HasRetributionCalculation, etc.
├── config/
│   ├── sentry.php                # Sentry Laravel SDK config
│   └── ...
├── database/
│   └── migrations/               # 90+ migrations
├── docker/                       # nginx + php-fpm + supervisor
├── docs/
│   ├── README.md
│   └── USER_GUIDE.md             # Panduan staf dinas per modul
├── public/
│   ├── data/
│   │   └── kecamatan_kab_bandung.geojson
│   └── build/                    # Vite output (gitignored)
├── resources/
│   ├── js/                       # Vite entry points (per page)
│   ├── scss/                     # SCSS sources
│   └── views/                    # Blade templates
├── routes/
│   ├── web.php
│   ├── api.php                   # 200+ routes
│   └── console.php
├── scripts/
│   ├── README.md                 # Operasional script guide
│   ├── backup-db.sh              # Daily DB backup w/ rotation
│   ├── populate_kecamatan.py     # Ad-hoc helper
│   ├── setup-*.sh                # First-time VPS setup
│   ├── webhook.php
│   └── legacy/                   # 4 stale deploy scripts (exit 1)
├── tests/
│   ├── Feature/
│   │   ├── ExampleTest.php
│   │   └── AuthRedirectTest.php
│   └── Unit/
│       ├── ExampleTest.php
│       └── RetributionCalculatorServiceTest.php
├── .github/workflows/
│   └── deploy.yml                # CI/CD: test → build → rsync → smoke
├── architecture_overview.md
├── agents.md                     # This file
├── CLAUDE.md                     # Project AI context (gitignored)
├── docker-compose.yml            # Production
├── docker-compose.local.yml      # Full local stack
├── Dockerfile
└── phpunit.xml
```

---

## Agent Configuration (`.agent.yaml`)

| Key | Value |
|-----|-------|
| Agent ID | `sibedas` |
| LLM Provider | OpenRouter — `deepseek-chat-v3-0324` |
| Fallback LLM | Groq — `llama-3.3-70b` |
| Max Tokens | 4096 |
| Temperature | 0.2 |
| File Editing | Enabled |
| Commands/Deploy | Disabled |
| Protected Paths | `.env`, `.env.production`, `docker-compose.yml`, `CLAUDE.md` |

---

## Services (`app/Services/`)

### 1. OpenAIService
**File**: `OpenAIService.php` · powers user + pimpinan chatbots
- Model: `gpt-4o-mini`
- Chat history context, prompt validation, template injection
- Used by: `Chatbot/`, `ChatbotPimpinan/` controllers

### 2. ServiceGoogleSheet
**File**: `ServiceGoogleSheet.php` · 2-way Google Sheets sync (50KB+ implementation)
- Used by: `SyncGoogleSheetData` command, `ScrapingDataJob`

### 3. ServicePbgTask
**File**: `ServicePbgTask.php` · scrapes PBG task listings from SIMBG API

### 4. ServiceTabPbgTask
**File**: `ServiceTabPbgTask.php` · scrapes detail/sub-task data from SIMBG (35KB+)

### 5. ServiceTokenSIMBG
**File**: `ServiceTokenSIMBG.php` · token acquisition + refresh

### 6. RetributionCalculatorService
**File**: `RetributionCalculatorService.php` · permit fee calc engine
- Inputs: building type, floors, area
- Excel-formula equivalent: `h5 = floor((coef × (ip_perm + ip_complex + heightMult × heightIdx)) × 10000) / 10000`
- Total = (area × locality × baseValue × h5) × (1 + infraMult)
- **Pinned by 7 unit tests** in `tests/Unit/RetributionCalculatorServiceTest.php`

---

## Queue Jobs

### ScrapingDataJob
**File**: `app/Jobs/ScrapingDataJob.php` · main ETL orchestrator
- **Timeout**: 5 hours
- **Flow**: PBG Tasks → Task Details → Google Sheets → BigData Resume
- **Features**: pause, resume from UUID, cancel
- **Trigger**: `php artisan app:start-scraping-data`

### RetrySyncronizeJob
**File**: `app/Jobs/RetrySyncronizeJob.php` · retry failed sync ops

---

## Console Commands (`app/Console/Commands/`)

| Command | Schedule | Description |
|---------|----------|-------------|
| `app:start-scraping-data` | Daily 00:00 | Full SIMBG scrape pipeline |
| `app:monitor-scraping` | Every 30 min | Monitor active scraping jobs |
| `app:sync-google-sheet-data` | Manual | Sync to Google Sheets |
| `app:sync-pbg-task-payments` | Manual | Sync payment data from Sheets |
| `app:sync-dashboard-pbg` | Manual | Refresh dashboard aggregates |
| `app:test-retribution-calculation` | Manual | Smoke test retribusi formula |
| `app:inject-spatial-plannings-data` | Manual | Insert spatial planning records |
| `app:assign-spatial-plannings-to-calculation` | Manual | Link spatial → retribusi |
| `app:truncate-pbg-table` | Manual | DANGEROUS — clears PBG data |
| `app:truncate-spatial-planning-data` | Manual | DANGEROUS — clears spatial |
| `import:open-buildings` | Manual | Import Microsoft Open Buildings dataset |
| `import:gee-results` | Manual | Import Google Earth Engine satellite results |
| `enrich:building-districts` | Manual | Backfill kecamatan via point-in-polygon |
| `match:detected-buildings` | Manual | Spatial-match detected → PBG (radius 50m) |
| `kecamatan-stats:refresh` | Manual | Recompute snapshot table for satellite stats |

---

## API Endpoints (Key Groups, `routes/api.php`)

| Route Group | Description |
|-------------|-------------|
| `POST /login` | Authentication (Sanctum) |
| `/api-pbg-task/*` | PBG task CRUD + management |
| `/api-google-sheet/*` | Sheets sync triggers |
| `/scraping/*` | Scraping control (start/pause/cancel) |
| `/bigdata-resume/*` | Summary/aggregate data |
| `/dashboard-potential-count` | Luar Sistem dashboard data |
| `/detected-buildings/*` | Satellite detection (stats, geojson, pbg-geojson, refresh-stats, status update) |
| `/advertisements/*`, `/umkm/*`, `/tourisms/*`, `/spatial-plannings/*` | Modul data |
| `/report-*`, `/payment-recaps` | Reporting + recap |
| `/taxs/*` | Tax management |
| `/chatbot/*`, `/main-chatbot/*` | AI chatbot |
| `POST /webhook/github` | GitHub webhook (legacy — replaced by Actions) |

---

## Database (Key Tables)

### PBG Core
| Table | Purpose |
|-------|---------|
| `pbg_task` | Building permit application |
| `pbg_task_details` | Building dims, function, type |
| `pbg_task_retributions` | Calculated fee breakdown |
| `pbg_task_payments` | Payment records |
| `pbg_statuses` | Status code dictionary |
| `pbg_task_google_sheet` | Sync state |
| `pbg_task_attachments` | Document uploads |
| `retribution_estimates` | Pre-calc retribusi per parcel |

### Satellite Detection
| Table | Purpose |
|-------|---------|
| `detected_buildings` | ~1M satellite-detected polygons (lat/lng + matched_pbg_task_id FK) |
| `kecamatan_stats` | Pre-computed per-district snapshot for fast stats |

### Spatial / Geographic
`provincies`, `regencies`, `districts`, `villages`, `spatial_plannings`

### Modul Data
`umkm` · `tourisms` · `business_or_industries` · `advertisements` · `customers` (PDAM) · `taxs`

### RBAC & System
`users` · `roles` · `menus` · `user_role` · `role_menu` · `global_settings` · `data_settings`

### Retribution Engine
`retribution_configs` · `retribution_calculations` · `building_types` · `height_indices` · `retribution_indices`

---

## Frontend

### Stack
| Tool | Version | Use |
|------|---------|-----|
| Vite | 5.4 | Build tool + HMR dev server |
| Bootstrap | 5.3.3 | UI components |
| Tailwind CSS | 3.4.13 | Utility CSS |
| ApexCharts | 3.44.2 | Dashboard charts |
| Leaflet | 1.9.4 + MarkerCluster | Interactive maps |
| GridJS | 5.1.0 | Data tables |
| SweetAlert2 | 11.16.0 | Modals/alerts |
| Flatpickr | 4.6.13 | Date pickers |
| Quill | 1.3.7 | Rich text editor |

### Key Page Modules
| Page | File |
|------|------|
| Dashboard BigData | `resources/views/dashboards/bigdata.blade.php` |
| Dashboard Potensi — Luar Sistem | `resources/views/dashboards/potentials/inside_system.blade.php` |
| Dashboard Potensi — Dalam Sistem | `resources/views/dashboards/potentials/outside_system.blade.php` |
| Satellite Monitoring | `resources/views/dashboards/satellite-monitoring.blade.php` |
| PBG Task list/detail | `resources/views/pbg-task/*.blade.php` |

### Recent Frontend Work (April 2026)
- **Satellite sync panels** added to Dashboard Potensi (Luar + Dalam Sistem) — pull from `/api/detected-buildings/stats`
- **Satellite Monitoring filter restructure**:
  - 3 dropdown lama (Kategori Usaha + Sumber Data + Sub-Jenis) → merged jadi 1 dropdown "Jenis Bangunan" dengan optgroup organized by Usaha vs Non Usaha
  - Status PBG dropdown jadi 2-tier optgroup: "Dalam Sistem" (SK Terbit/Proses/Ditolak) + "Luar Sistem" (Tanpa Izin Sah)
  - "Deteksi Satelit" filter dihapus (overlap dengan Status PBG → Luar Sistem)
  - "Terapkan" button dihapus — semua filter auto-render on change
  - Performance: `applyPbgStatusFilter` pakai `whereIn(subquery)` instead of `whereHas` (fix slow correlated EXISTS pada 1M+ rows)

---

## External Integrations

| Integration | Purpose | Library |
|-------------|---------|---------|
| Google Sheets API | Bidirectional data sync | `google/apiclient` v2.12 |
| OpenAI API | gpt-4o-mini chatbot | `openai-php/client` v0.10.3 |
| SIMBG API | Source PBG task data | GuzzleHTTP v7.9 |
| Sentry | Error tracking (DSN pending) | `sentry/sentry-laravel` v4.x |
| GitHub Actions | CI/CD on push to master | `.github/workflows/deploy.yml` |
| GitHub Webhooks | Legacy auto-deploy (deprecated) | `scripts/webhook.php` |

---

## Testing

**Framework**: PHPUnit 11.5 (Pest plugin available, primary tests use PHPUnit syntax).
**Run**: `php vendor/bin/phpunit` (13 tests, 33 assertions, ~1s).

| Suite | Count | Coverage |
|-------|------:|----------|
| Unit/RetributionCalculatorServiceTest | 7 | Formula h5, total, scaling, excel mode, struktur output |
| Feature/AuthRedirectTest | 4 | Smoke: root redirect, login render, protected redirect, no-500 on bad post |
| Feature/ExampleTest | 1 | App returns successful response |
| Unit/ExampleTest | 1 | Stub |

**Note**: Feature tests run without MySQL service via phpunit.xml env overrides (SESSION_DRIVER=array, etc.). Tests don't write DB.

---

## Deployment

### Production
- **Method**: GitHub Actions on push to `master` (`.github/workflows/deploy.yml`)
- **Job sequence**: test → build assets → rsync → docker compose exec migrate + cache + restart → smoke test
- **Required GitHub Secret**: `PROD_SSH_KEY` (private key with VPS access)
- **Container ports**: 9080/9443 (host nginx reverse-proxies to `sibedaspbg.aureonforge.com`)
- **DB persistence**: `sibedas_db` MariaDB container kept alive across deploys

### Manual / Agent-Triggered
Use the global `sibedas-deployer` Claude Code agent (at `~/.claude/agents/sibedas-deployer.md`).

### Backup
- **Script**: `scripts/backup-db.sh` (gzip + 14-day rotation)
- **Cron suggestion**: `30 2 * * * /root/projects/sibedaspbg/scripts/backup-db.sh /root/backups/sibedaspbg >> /var/log/sibedas-backup.log 2>&1`

### Operational Gotchas
1. **`npm ci`** — broken on this repo (rollup optional-deps bug). Lockfile regenerated 2026-04-28; `npm ci` now works after fresh lockfile.
2. **rsync exclusions** for deploys: `.env`, `.env.agent`, `.agent.yaml`, `.agent-memory.md`, `.git`, `node_modules`, `vendor`, `storage/logs/*`, `*.sql`, `RAB dan PPT/`, `sibedas_dev/`, `tmp/`.
3. **Windows local rsync**: manually installed at `C:\Users\Lenovo\bin\rsync.exe` — fragile, mitigated by GitHub Actions handling deploy.

---

## Local Development

### Setup
```bash
# Clone
git clone https://github.com/nauvalZulfikar/SIBEDAS.git
cd SIBEDAS

# PHP deps
composer install

# JS deps (use install, NOT ci)
npm install

# Copy env & generate key
cp .env.example .env
php artisan key:generate
```

### Run
```bash
# Backend (port 8002 — see .env APP_URL)
php artisan serve --host=127.0.0.1 --port=8002

# Vite dev server (HMR, port 5174)
npx vite --port 5174 --strictPort

# DB: MariaDB/MySQL via XAMPP locally (port 3306, db `sibedas`)
# OR full Docker stack:
docker compose -f docker-compose.local.yml up -d
```

### Default Local Credentials
- Email: `user@demo.com`
- Password: `password`

---

## Sending Tasks to This Agent

```bash
curl -s -X POST http://127.0.0.1:8010/prompt \
  -H "Authorization: Bearer e12dca3d1fc22564d38dd316eaa10505fe3868856243852995639a374cf6cf96" \
  -H "Content-Type: application/json" \
  -d '{
    "target": "@sibedas",
    "prompt": "INSTRUCTION HERE",
    "context_files": [],
    "deploy_after": false
  }'
```

> Set `deploy_after: true` untuk auto-deploy setelah agent finish editing.

---

## Recent Activity Snapshot (Last Touched 2026-04-29)

**Repo cleanup (April 28)**:
- ~900 MB removed: `sibedas_dev/`, `sibedas_dev.zip`, `sibedas.sql`, `RAB dan PPT/`, `proposal/` moved out
- 4 stale deploy scripts → `scripts/legacy/` with `exit 1` guards
- `architecture_overview.md`, `docs/USER_GUIDE.md`, `scripts/README.md` written
- `.env.example` synced with real dev setup
- Sentry SDK installed (DSN pending activation)

**Feature work (April 28-29)**:
- Satellite sync panels added to Dashboard Potensi pages (Luar + Dalam Sistem)
- Satellite Monitoring filter restructured:
  - Single "Jenis Bangunan" dropdown with 17 sub-source options (Reklame, Pajak ×5, UMKM, Pariwisata, PDAM, Tata Ruang, PBG function_type)
  - Status PBG split into Dalam Sistem (SK Terbit/Proses/Ditolak) + Luar Sistem (Tanpa Izin Sah)
  - Auto-render on filter change (no Apply button)
  - Default = Semua Status (all data shown)
- Backend filter performance optimized — `applyPbgStatusFilter` rewritten with `whereIn(subquery)` (was correlated EXISTS, slow on 1M rows). Map render: 5-30s → ~1s.
- 7 unit tests added pinning retribusi formula

**Pending manual actions**:
1. Add GitHub Secret `PROD_SSH_KEY` → workflow deploy can run
2. Configure Sentry DSN in prod `.env` to activate error tracking
3. Schedule `scripts/backup-db.sh` via cron
4. Disable legacy webhook on VPS after CI/CD verified
