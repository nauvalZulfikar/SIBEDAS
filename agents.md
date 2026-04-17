# Agents — Sibedas PBG

**Project:** Sibedas PBG (Sistem Informasi Bedah Data Pendidikan Berkelanjutan Guru)
**Stack:** Laravel 11 · PHP 8.2 · MariaDB · Docker · Vite · Tailwind CSS
**Domain:** sibedaspbg.aureonforge.com
**Agent ID:** `sibedas`

---

## Project Overview

Web application for managing PBG (Pendidikan Berkelanjutan Guru) data. Core functions:
- Scraping & syncing PBG task data from external SIMBG API
- Synchronizing data to/from Google Sheets
- AI chatbot assistance (OpenAI GPT-4o-mini)
- Retribution & payment calculations
- Spatial planning, UMKM, tourism, and advertisement data management
- Multi-role user access with dashboards and reports

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
| Protected Paths | `.env`, `.env.production`, `docker-compose.yml` |

---

## Services

### 1. OpenAIService
**File:** `app/Services/OpenAIService.php`

AI text generation service powering the chatbot features.
- Model: `gpt-4o-mini`
- Supports chat history context
- Prompt validation and classification
- Template-based prompt injection

**Used by:** `ChatbotController`, `ChatbotPimpinanController`

---

### 2. ServiceGoogleSheet
**File:** `app/Services/ServiceGoogleSheet.php`

Google Sheets API integration for bidirectional data sync.
- Reads/writes data from configured spreadsheets
- Syncs PBG payment data from sheets
- 50KB+ implementation — the largest service in the codebase

**Used by:** `ScrapingDataJob`, console commands

---

### 3. ServicePbgTask
**File:** `app/Services/ServicePbgTask.php`

Fetches PBG task data from the external SIMBG API.
- Authenticates via `ServiceTokenSIMBG`
- Scrapes parent-level task listings

---

### 4. ServiceTabPbgTask
**File:** `app/Services/ServiceTabPbgTask.php`

Fetches detail/tab-level data for each PBG task.
- Scrapes sub-task details from SIMBG API
- 35KB+ implementation

---

### 5. ServiceTokenSIMBG
**File:** `app/Services/ServiceTokenSIMBG.php`

Manages authentication tokens for the SIMBG external API.
- Token acquisition and refresh

---

### 6. RetributionCalculatorService
**File:** `app/Services/RetributionCalculatorService.php`

Handles complex retribution/tax calculation logic for building permits.
- Uses `RetributionConfig`, `CalculableRetribution` models
- Called during PBG task processing

---

## Queue Jobs

### ScrapingDataJob
**File:** `app/Jobs/ScrapingDataJob.php`

Main data scraping orchestrator — the backbone of the ETL pipeline.
- **Timeout:** 5 hours
- **Flow:** PBG Tasks → Task Details → Google Sheets → BigData Resume
- **Features:** pause, resume (from UUID), cancel
- **Dispatcher:** Triggered by `app:start-scraping-data` command or manual API

### RetrySyncronizeJob
**File:** `app/Jobs/RetrySyncronizeJob.php`

Retries failed synchronization operations.

---

## Console Commands

| Command | Schedule | Description |
|---------|----------|-------------|
| `app:start-scraping-data` | Daily at 00:00 | Starts full scraping pipeline |
| `app:monitor-scraping` | Every 30 minutes | Monitors active scraping jobs |
| `app:sync-google-sheet-data` | Manual | Syncs data to Google Sheets |
| `app:sync-pbg-task-payments` | Manual | Syncs payment data from Sheets |
| `app:sync-dashboard-pbg` | Manual | Refreshes dashboard aggregates |
| `app:inject-spatial-plannings-data` | Manual | Injects spatial planning records |
| `app:assign-spatial-plannings-to-calculation` | Manual | Links spatial data to retribution |
| `app:test-retribution-calculation` | Manual | Tests calculation logic |
| `app:truncate-pbg-table` | Manual | Clears PBG task data |
| `app:truncate-spatial-planning-data` | Manual | Clears spatial planning data |

---

## API Endpoints (Key Groups)

| Route Group | Description |
|-------------|-------------|
| `POST /login` | Authentication |
| `/api-pbg-task/*` | PBG task CRUD & management |
| `/api-google-sheet/*` | Google Sheets sync triggers |
| `/scraping/*` | Scraping control (start/pause/cancel) |
| `/bigdata-resume/*` | Summary/aggregate data |
| `/advertisements/*` | Reklame/advertisement data |
| `/umkm/*` | UMKM business data |
| `/tourisms/*` | Tourism data |
| `/spatial-plannings/*` | Spatial planning data |
| `/report-*` | Reports (Excel/PDF export) |
| `/payment-recaps` | Payment summaries |
| `/taxs/*` | Tax management |
| `POST /webhook/github` | GitHub webhook (auto-deploy) |

---

## Docker Services

| Service | Description | Ports |
|---------|-------------|-------|
| `app` | PHP-FPM + Supervisor (queue workers + scheduler) | 9000 (internal) |
| `nginx` | Web server with SSL termination | 80, 443 |
| `db` | MariaDB database | 3306 (internal) |

**Supervisor processes inside `app`:**
- Queue worker (processes `ScrapingDataJob`, `RetrySyncronizeJob`)
- Laravel scheduler (runs console commands on schedule)

---

## Database Models (33 total)

**PBG Core:**
`PbgTask` · `PbgTaskDetail` · `PbgTaskAttachment` · `PbgTaskPayment`

**Data:**
`BigdataResume` · `SpatialPlanning` · `ImportDatasource`

**Configuration:**
`PbgStatus` · `RetributionConfig` · `RetributionCalculation` · `CalculableRetribution`

**Business Domains:**
`Advertisement` · `Customer` · `BusinessOrIndustry` · `Tax` · `Tourism` · `Umkm`

**Admin/System:**
`User` · `Role` · `Menu` · `GlobalSetting` · `DataSetting` · `TaskAssignment`

**Reference:**
`HeightIndex` · `BuildingType` · (+ others)

---

## Key External Integrations

| Integration | Purpose | Library |
|-------------|---------|---------|
| Google Sheets API | Bidirectional data sync | `google/apiclient` v2.12 |
| OpenAI API | GPT-4o-mini chatbot | `openai-php/client` v0.10.3 |
| SIMBG API | Source PBG task data | GuzzleHTTP v7.9 |
| GitHub Webhooks | CI/CD trigger | Native Laravel |

---

## Frontend Stack

| Tool | Version | Use |
|------|---------|-----|
| Vite | 5.0 | Build tool |
| Tailwind CSS | 3.4.13 | Utility CSS |
| Bootstrap | 5.3.3 | UI components |
| ApexCharts | 3.44.2 | Dashboard charts |
| Leaflet | 1.9.4 | Interactive maps |
| GridJS | 5.1.0 | Data tables |
| SweetAlert2 | 11.16.0 | Modals/alerts |
| Flatpickr | 4.6.13 | Date pickers |
| Quill | 1.3.7 | Rich text editor |

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

> Set `deploy_after: true` to automatically deploy after the agent finishes editing.

---

## Directory Structure

```
sibedaspbg/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 71 controllers (Api, Auth, Chatbot, Report, etc.)
│   │   ├── Middleware/
│   │   ├── Requests/           # Form validation
│   │   └── Resources/          # API response formatting
│   ├── Models/                 # 33 Eloquent models
│   ├── Services/               # 6 core services
│   ├── Jobs/                   # 2 queue jobs
│   ├── Console/Commands/       # 10 Artisan commands
│   ├── Enums/                  # 4 enums
│   ├── Exports/ & Imports/     # Excel handling
│   └── Providers/
├── routes/
│   ├── api.php                 # 200+ API routes
│   ├── web.php
│   └── console.php             # Scheduled tasks
├── database/
│   ├── migrations/
│   └── seeders/
├── docker/
│   ├── nginx/
│   ├── mysql/
│   └── supervisor/
├── docs/                       # Deployment documentation
├── scripts/                    # Deployment scripts
├── docker-compose.yml
├── Dockerfile
├── .agent.yaml
└── sibedas.sql                 # 60MB database dump
```
