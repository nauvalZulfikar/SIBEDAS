# PBB Reconciliation — Staging Deployment Playbook

> **Audience**: SRE / IT yang melakukan deploy staging pertama kali.
> **Goal**: pilot 1 minggu di `https://sibedas-staging.aureonforge.com` dengan ~5 user Bapenda. Kalau lulus → Phase 14 (production deploy).

## 0. Apa yang sudah disiapkan di repo

| File | Tujuan |
|---|---|
| `.env.staging.example` | Template env vars staging (APP_ENV=staging, DB sibedas_staging, cache prefix terpisah) |
| `app/Http/Controllers/Api/PbbHealthController.php` | Health probe `/api/health/pbb` (no auth, 6 component checks) |
| `scripts/deploy-staging.sh` | Idempotent deploy: pull + composer + npm + migrate + cache + recompute + health |
| `docs/pbb/DEPLOY_STAGING.md` | File ini — playbook manual |

## 1. Server prerequisites (di VPS aureonforge — `72.60.196.21`)

Asumsi: pakai pola yang sama dgn proyek lain di `/root/projects/` + nginx host fronted. Kalau pakai Docker pattern (mengikuti prod sibedaspbg), sesuaikan COPY → `docker cp`.

```bash
# Tools
apt update && apt install -y php8.2 php8.2-{mysql,mbstring,xml,bcmath,curl,gd,zip} \
    composer nodejs npm mariadb-client python3 python3-pip nginx certbot \
    python3-certbot-nginx

# Python deps untuk Phase 7 spatial scripts (kalau perlu re-run)
pip3 install requests shapely
```

## 2. DNS + SSL

```bash
# Tambahkan A record sibedas-staging.aureonforge.com → 72.60.196.21 di registrar.
# Lalu:
certbot --nginx -d sibedas-staging.aureonforge.com
```

## 3. Database

```bash
mysql -uroot -p <<SQL
CREATE DATABASE sibedas_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sibedas_staging'@'localhost' IDENTIFIED BY '<set-strong-password>';
GRANT ALL ON sibedas_staging.* TO 'sibedas_staging'@'localhost';
FLUSH PRIVILEGES;
SQL
```

Catatan: pakai password strong, simpan di password manager Bapenda IT.

## 4. Clone & first-time setup

```bash
mkdir -p /root/projects/sibedaspbg-staging
cd /root/projects/sibedaspbg-staging
git clone https://github.com/nauvalZulfikar/SIBEDAS.git .
git checkout master   # atau branch staging kalau ada

# Env
cp .env.staging.example .env
nano .env             # isi DB_PASSWORD + APP_KEY (kosongkan dulu)
php artisan key:generate
nano .env             # set WEBHOOK_SECRET ke value baru (rotate dari prod)

# First deploy
chmod +x scripts/deploy-staging.sh
APP_URL="https://sibedas-staging.aureonforge.com" bash scripts/deploy-staging.sh --skip-data
```

## 5. Data import (PBB + spatial)

### 5.1 Drop CSV PBB ke server
```bash
scp Bapenda_PBB_2025.csv root@72.60.196.21:/root/projects/sibedaspbg-staging/storage/app/private/data-pbb/
```

### 5.2 Import → 1.15M records (~10 menit)
```bash
cd /root/projects/sibedaspbg-staging
php artisan pbb:import storage/app/private/data-pbb/Bapenda_PBB_2025.csv
```

### 5.3 Backfill `building_ward_name` via point-in-polygon
```bash
# GeoJSON sudah ada di public/data/kelurahan_kab_bandung.geojson (63 features)
python3 scripts/populate_kelurahan.py
# Output: ~4500 detected_buildings dapat building_ward_name
```

### 5.4 Recompute reconciliation summary
```bash
php artisan pbb:recompute-reconciliation
# Output: 307 rows (1 kab + 31 kec + 275 kelurahan)
```

### 5.5 First snapshot
```bash
php artisan pbb:snapshot-reconciliation
# Output: storage/app/private/exports/reconciliation/2026-05.xlsx
```

## 6. Cron — schedule:run

Pilih salah satu:

### Opsi A: cron tradisional
```bash
crontab -e
# Tambahkan:
* * * * * cd /root/projects/sibedaspbg-staging && php artisan schedule:run >> /var/log/sibedas-staging-cron.log 2>&1
```

### Opsi B: systemd timer (lebih clean, lebih observable)
```ini
# /etc/systemd/system/sibedas-staging-scheduler.service
[Unit]
Description=Sibedas Staging Laravel Scheduler

[Service]
Type=oneshot
WorkingDirectory=/root/projects/sibedaspbg-staging
ExecStart=/usr/bin/php artisan schedule:run

# /etc/systemd/system/sibedas-staging-scheduler.timer
[Unit]
Description=Run Sibedas Staging Scheduler every minute

[Timer]
OnCalendar=*:0/1
Persistent=true

[Install]
WantedBy=timers.target
```

```bash
systemctl enable --now sibedas-staging-scheduler.timer
systemctl list-timers | grep sibedas-staging
```

Yang dijalankan otomatis (lihat `routes/console.php`):
- **02:00 daily** → `pbb:recompute-reconciliation`
- **03:00 setiap tanggal 1** → `pbb:snapshot-reconciliation` (auto-rotate 24 file)

## 7. Nginx vhost

```nginx
# /etc/nginx/sites-available/sibedas-staging.aureonforge.com
server {
    listen 80;
    server_name sibedas-staging.aureonforge.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sibedas-staging.aureonforge.com;

    ssl_certificate /etc/letsencrypt/live/sibedas-staging.aureonforge.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sibedas-staging.aureonforge.com/privkey.pem;

    root /root/projects/sibedaspbg-staging/public;
    index index.php;

    client_max_body_size 50M;   # untuk upload CSV PBB besar

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(env|git) {
        deny all;
    }

    access_log /var/log/nginx/sibedas-staging-access.log;
    error_log /var/log/nginx/sibedas-staging-error.log;
}
```

```bash
ln -s /etc/nginx/sites-available/sibedas-staging.aureonforge.com /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

## 8. User accounts (5 staging users)

Buat lewat seeder atau tinker:

```bash
php artisan tinker
```

```php
// 1 superadmin (Bapenda IT lead) — clearance level_3
$u = App\Models\User::create(['name'=>'Bapenda IT','email'=>'it@bapenda.kab-bandung.go.id','password'=>bcrypt('<temp>'),'position'=>'IT']);
$superadmin = App\Models\Role::where('name','superadmin')->first();
$u->roles()->attach($superadmin->id);

// 2 admin (Kasubag PBB) — clearance level_2
foreach (['kasubag1@bapenda...','kasubag2@bapenda...'] as $email) {
    $u = App\Models\User::create([...]);
    $u->roles()->attach(App\Models\Role::where('name','admin')->first()->id);
}

// 2 operator — clearance level_1
foreach (['op1@bapenda...','op2@bapenda...'] as $email) {
    $u = App\Models\User::create([...]);
    $u->roles()->attach(App\Models\Role::where('name','staff')->first()->id);
}
```

Email kredensial sementara → user via WhatsApp / channel resmi Bapenda. Minta semua user **ganti password** saat login pertama.

## 9. Smoke test (post-deploy checklist)

```bash
# 9.1 Health endpoint
curl https://sibedas-staging.aureonforge.com/api/health/pbb | jq
# Harus: status=ok, semua 6 components ok, version != "unknown"

# 9.2 Login → dashboard PBB
# Buka https://sibedas-staging.aureonforge.com di browser
# Login pakai akun superadmin → menu PBB Reconciliation
# Verify: 4 KPI cards muncul, bar chart 31 kecamatan, modal drill-down jalan

# 9.3 Test 3-tier RBAC
# Login level_1 → /api/reconciliation/audit harus 403
# Login level_2 → /api/reconciliation/audit harus 200 dgn pii_masked=true
# Login level_3 → /api/reconciliation/audit harus 200 dgn pii_masked=false

# 9.4 Test export
# Klik tombol "Export Excel" → download file 4-sheet
# Klik tombol "Export PDF" → download A4

# 9.5 Recompute
# Login superadmin → klik "Recompute" → toast success, summary updated

# 9.6 Cron verification (tunggu 1 menit)
tail -f /var/log/sibedas-staging-cron.log
# atau
journalctl -u sibedas-staging-scheduler.service -f
```

## 10. Auto-deploy webhook (optional — Phase 13.5)

Kalau mau push-to-deploy:
1. Bikin endpoint mirip `scripts/webhook.php` tapi panggil `bash scripts/deploy-staging.sh` (bukan `docker cp`)
2. GitHub webhook → URL khusus staging branch
3. Set WEBHOOK_SECRET di nginx env atau systemd EnvironmentFile

Untuk pilot 1 minggu **manual deploy cukup** — tidak perlu push-to-deploy yet.

## 11. Monitoring 1-minggu pilot

Setiap pagi cek:

| Metric | Cara cek | Threshold alert |
|---|---|---|
| Health endpoint | `curl /api/health/pbb` | status=degraded → investigate |
| Schedule runs | `tail /var/log/sibedas-staging-cron.log` | recompute >24 jam tanpa output |
| Audit log volume | `SELECT COUNT(*) FROM pbb_access_log WHERE accessed_at > NOW() - INTERVAL 1 DAY` | <5/hari → user belum pakai |
| Disk space | `df -h /root` | >80% → prune snapshots manual |
| Error log | `tail /storage/logs/laravel.log` | exception >5/hari → bug |
| User feedback | Form Google atau channel WhatsApp dedicated | — |

## 12. Rollback plan

Kalau ada issue critical:

```bash
cd /root/projects/sibedaspbg-staging
git log --oneline -10        # cari commit "good"
git checkout <good-sha>
APP_URL=... bash scripts/deploy-staging.sh --skip-data
```

DB rollback hanya kalau migration breaks data:
```bash
php artisan migrate:rollback --step=1 --force
```

## 13. Hand-over ke Phase 14

Phase 13 done jika:
- ✅ Health endpoint 200 ok untuk 7 hari berturut-turut
- ✅ Schedule:run kelar tanpa error
- ✅ 5 user Bapenda berhasil login + drill-down + export
- ✅ Tidak ada P1 issue dalam audit log
- ✅ Feedback form: ≥3 user reply, mayoritas tidak ada blocker

Hand-off ke Phase 14: rotate WEBHOOK_SECRET, copy `.env.staging` → `.env.production` (ubah APP_ENV, DB_DATABASE, domain), repeat playbook ini di prod path.
