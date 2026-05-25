# Sibedas Scheduler Setup

Sibedas has two automated jobs that keep the dashboard instant for users:

| Job | When | What it does | Typical duration |
|---|---|---|---|
| `sibedas:nightly --skip-sync` | 03:30 daily | Rebuild `kecamatan_stats` aggregates | 1-2 min |
| `sibedas:weekly` | 04:00 every Monday | Pre-warm Martin tile cache + pre-generate 31 KRK PDFs | 15-30 min |

Plus two pre-existing jobs the team already runs (see `routes/console.php`):
- `buildings:sync-postgis` (03:00 daily, ~6 min initial / ~1 min incremental)
- `pbb:recompute-reconciliation` (02:00 daily)

All of these are dispatched from a **single tick command** — `php artisan schedule:run` — that must be invoked every minute by the OS.

---

## Windows — Task Scheduler

1. Open **Task Scheduler** (`taskschd.msc`).
2. **Create Task** (not "Create Basic Task" — we need the advanced options).
3. **General tab:**
   - Name: `Sibedas Laravel Schedule`
   - User: your normal user, "Run whether user is logged on or not", **uncheck** "Do not store password" if asked.
   - Check **Run with highest privileges**.
4. **Triggers tab → New:**
   - Begin task: **On a schedule**
   - One time, start: today at the next minute
   - Repeat task every **1 minute** for a duration of **Indefinitely**
   - Enabled: yes
5. **Actions tab → New:**
   - Program: `C:\xampp\php\php.exe` (or wherever your `php.exe` is — find it with `where php` in PowerShell)
   - Arguments: `artisan schedule:run`
   - Start in: `D:\Downloads\coding project\_sibedas\Sibedas`
6. **Conditions tab:** uncheck "Start the task only if the computer is on AC power" (so it runs on battery too).
7. **Settings tab:**
   - Allow task to be run on demand: ✓
   - If task fails, restart every 5 minutes, attempt 3 times
   - Stop if runs longer than: 1 hour
8. Click **OK**, enter your Windows password if prompted.

Verify it's working: tail `storage/logs/laravel.log` for the next ~5 minutes — you should see no errors, and at the next 03:30 / Monday 04:00 you'll see the scheduled commands fire.

---

## Linux/Mac — cron

Add to user crontab (`crontab -e`):

```
* * * * * cd /path/to/sibedas && php artisan schedule:run >> /dev/null 2>&1
```

---

## Manual on-demand commands

These commands work the same way whether triggered by scheduler or run by hand:

```bash
# Just rebuild kecamatan_stats (~1 min)
php artisan sibedas:nightly --skip-sync

# Full nightly: postgis sync + stats refresh (~3-8 min)
php artisan sibedas:nightly

# Tile cache + PDF pre-gen (~15-30 min)
php artisan sibedas:weekly

# Just tile cache, skip PDF
php artisan sibedas:weekly --skip-pdf

# Just PDF, skip tile cache
php artisan sibedas:weekly --skip-tiles

# Customise tile zoom range (default 14-16)
php artisan sibedas:weekly --zoom-min=14 --zoom-max=15 --skip-pdf
```

---

## Logs to tail when debugging

- `storage/logs/sibedas-nightly.log` — Tier-1 output
- `storage/logs/sibedas-weekly.log` — Tier-2 output
- `storage/logs/buildings-sync.log` — postgis mirror sync
- `storage/logs/laravel.log` — fallback for everything else

---

## What "pre-cache" means for the user

After the Monday 04:00 run, opening any kecamatan in the dashboard and clicking **Cetak KRK** streams the cached PDF instantly (no headless Chrome cold start). Cache is treated as fresh for 7 days; after that the endpoint re-generates on demand.

`tmp/krk-cache/` directory holds the cached files. Delete to force regeneration (next weekly run rebuilds).
