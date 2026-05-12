# Sibedas — Architecture Overview

*Last refreshed: 2026-04-28*

## Project summary

**Sibedas** (Sistem Informasi Bedah Daerah) is the internal data & permit-management platform for **Dinas Pekerjaan Umum dan Tata Ruang (DPUTR) Kabupaten Bandung**.

Core domain: **PBG (Persetujuan Bangunan Gedung — building permits)** — application intake, status tracking, retribution (fee) calculation, payment recap, and reporting. Around the PBG core sits a wider data layer for spatial planning, UMKM, tourism, and advertisement records.

## Stack

- **Backend**: Laravel 11.x, PHP 8.2+
- **Frontend**: Vite 5 + Blade templates + Tailwind 3 + ApexCharts + GridJS + Leaflet
- **Database**: MariaDB 10.6 (prod) / MySQL via XAMPP (local dev)
- **Queues / cache / sessions**: database driver (no Redis required)
- **Container**: Docker Compose (`docker-compose.yml` for prod, `docker-compose.local.yml` for full-stack local)
- **External integrations**: Google Sheets API, OpenAI (gpt-4o-mini), SIMBG (national building portal scraper)

## Repository layout (canonical)

```
Sibedas/
├── app/                      # Laravel application code
│   ├── Console/Commands/     # CLI commands (scrapers, sync, calculations)
│   ├── Http/Controllers/     # ~30 controllers, organized by feature
│   ├── Jobs/                 # Queued background work
│   ├── Models/               # Eloquent models
│   └── Services/             # ServiceGoogleSheet, RetributionFormulas, etc.
├── database/migrations/      # 90+ migrations
├── docker/                   # nginx + php-fpm Dockerfile artifacts
├── docs/                     # Project documentation
├── public/                   # Web root (Vite manifest lives here in prod)
├── resources/
│   ├── js/                   # Vite-managed JS entry points (per page)
│   ├── scss/                 # SCSS sources transformed by Vite
│   └── views/                # Blade templates
├── routes/                   # web.php, api.php, console.php
├── scripts/                  # Operational scripts (see scripts/README.md)
├── storage/                  # Logs, sessions, uploaded files
└── tests/                    # PHPUnit/Pest tests
```

`sibedas_dev/` (if present locally) is a stale agent/deployment snapshot — **not** part of the canonical repo.

## Domain entities

### PBG core

| Table | Purpose |
|-------|---------|
| `pbg_task` | One row per building permit application |
| `pbg_task_details` | Building dimensions, function, tipe |
| `pbg_task_retributions` | Calculated fee breakdown |
| `pbg_task_payments` | Payment records & status |
| `pbg_statuses` | Status code dictionary |
| `pbg_task_google_sheet` | Sync state with external Google Sheets |
| `pbg_task_attachments` | Document uploads (RAB, KRK, DLH, etc.) |
| `retribution_estimates` | Pre-calculated retribution estimates per parcel |
| `usulan_retribusi` (column on `pbg_task`) | Latest proposed fee — added 2026-03-26 |

### Spatial / geographic

`provincies`, `regencies`, `districts`, `villages`, `spatial_plannings`, `detected_buildings` (satellite-detected), `kecamatan_stats` (aggregate per district).

### Data modules

`umkm`, `tourisms`, `business_or_industries`, `advertisements`, `customers`, `taxs`.

### RBAC & system

`users`, `roles`, `menus`, `user_role`, `role_menu`, `global_settings`, `data_settings`.

## Key services & integrations

### 1. SIMBG scraping (background)

Pulls building-permit data from the national SIMBG portal.

- Controller: `App\Http\Controllers\Api\ScrapingController`
- Command: `php artisan start:scraping`
- Job: `App\Jobs\ScrapingDataJob`
- Long-running (5h timeout), pauseable/resumable. Monitor via `php artisan monitor:scraping` (`MonitorScrapingJob.php`).

### 2. Retribution calculation engine

Calculates building-permit fees from spatial-planning indices, building dimensions, and function class.

- Models: `RetributionFormulas`, related index tables
- Command: `php artisan test:retribution-calculation` (`TestRetributionCalculation.php`)
- Latest tweak: `usulan_retribusi` column added 2026-03-26 (replaces older `nilai_retribusi_bangunan` in some flows)

### 3. Google Sheets sync

Bidirectional sync of selected PBG statuses to a designated Google Sheet for external stakeholders.

- Service: `App\Services\ServiceGoogleSheet`
- Command: `php artisan sync:google-sheet-data`

### 4. AI chatbots

Two chatbot surfaces, both backed by OpenAI gpt-4o-mini:

- `Chatbot/` — user-facing assistant for PBG queries
- `ChatbotPimpinan/` — leadership-level summarization & analytics

### 5. Satellite building detection (added April 2026)

Imports detected building polygons (Open Buildings dataset / Google Earth Engine), enriches them with district info, and matches against existing PBG records to surface unpermitted construction.

- Commands: `import:open-buildings`, `import:gee-results`, `enrich:building-districts`, `match:detected-buildings`, `refresh:kecamatan-stats`
- Tables: `detected_buildings`, `kecamatan_stats`
- Controller: `Api\DetectedBuildingController`
- Dashboard: `Dashboards/Bigdata` (map view)

## User-facing modules (controllers)

| Module | Path | Purpose |
|--------|------|---------|
| Authentication | `Auth/` | Login, password, sessions |
| Home / Dashboards | `Home/`, `Dashboards/` | Bigdata, Pimpinan, PBG, Lack-of-Potential |
| PBG management | `PbgTask*`, `Approval/` | Task CRUD, approvals, attachments |
| Quick Search | `QuickSearchController` | Internal data search |
| Public Search | `Routing/`, public routes | Citizen-facing parcel/building lookup |
| Customers | `CustomersController` | Applicant master data |
| Reklame | `Data/Advertisements/` | Advertisement permits |
| UMKM | `Data/Umkm/` | Small enterprise data |
| Tourism | `Data/Tourisms/` | Tourism site data |
| Spatial plannings | `Data/SpatialPlannings/` | Tata ruang data |
| Reports | `Report/`, `BigdataResumes`, `PaymentRecaps` | Excel/PDF exports |
| Chatbot | `Chatbot/`, `ChatbotPimpinan/` | AI assistance |
| RBAC admin | `Master/`, `Roles`, `Menus`, `Settings/` | User & permission management |
| Taxation | `Taxation/` | Tax/levy admin |
| Settings | `Settings/`, `DataSetting`, `GoogleApis` | System config |

## Frontend

Vite is configured with the `laravel-vite-plugin`. Each Blade page references the JS/SCSS entry points it needs via `@vite([...])`. The full list of entry points lives in `vite.config.js`.

In dev: `npm run dev` (or `npx vite --port <N>`) writes `public/hot` so Laravel knows to load assets from the dev server with HMR. In prod: `npm run build` emits to `public/build/` with a manifest.

## Deployment

- **Production target**: `/root/projects/sibedaspbg/` on `root@72.60.196.21`
- **Domain**: `sibedaspbg.aureonforge.com` (and `sibedaspbg.cloud`)
- **Method**: GitHub Actions (`.github/workflows/deploy.yml`) on push to `master`, or the global `sibedas-deployer` Claude agent for manual triggers
- **Container ports**: 9080/9443 (host nginx reverse-proxies)
- **DB persistence**: `sibedas_db` MariaDB container kept alive across deploys (`docker compose up -d`, never `down`)

See `scripts/README.md` for the full deploy guide.

## Operational notes

- **`npm ci` is broken** on this repo — use `npm install`.
- **rsync exclusions** for deploy: `.env`, `.env.agent`, `.agent.yaml`, `.agent-memory.md`.
- **Local dev**: backend `php artisan serve --port=8002`; frontend `npx vite --port 5174`; DB via XAMPP MySQL (port 3306, db `sibedas`).
