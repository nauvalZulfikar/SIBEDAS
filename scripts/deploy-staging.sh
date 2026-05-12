#!/bin/bash
# Sibedas-PBB Staging Deploy
# Idempotent: aman dijalankan berulang kali. Tidak destructive.
#
# Usage (di server staging, sebagai user dengan akses ke APP_DIR):
#   bash scripts/deploy-staging.sh           # pull + composer + build + migrate
#   bash scripts/deploy-staging.sh --skip-data   # tanpa import PBB / recompute (lebih cepat)
#   bash scripts/deploy-staging.sh --health-only # hanya cek /api/health/pbb
#
# Asumsi:
#   - APP_DIR sudah ada, sudah git clone, sudah ada .env (copy dari .env.staging.example)
#   - PHP 8.2+, composer, node 20+, npm sudah terpasang
#   - MySQL/MariaDB up, DB sibedas_staging sudah dibuat manual (dgn user yg sesuai .env)
#   - Cron `php artisan schedule:run` aktif (lihat DEPLOY_STAGING.md §6)

set -euo pipefail

APP_DIR="${APP_DIR:-/root/projects/sibedaspbg-staging}"
SKIP_DATA=0
HEALTH_ONLY=0

for arg in "$@"; do
    case "$arg" in
        --skip-data) SKIP_DATA=1 ;;
        --health-only) HEALTH_ONLY=1 ;;
        *) echo "Unknown arg: $arg" && exit 2 ;;
    esac
done

log() { echo "[deploy-staging $(date +%H:%M:%S)] $*"; }

cd "$APP_DIR"

if [ "$HEALTH_ONLY" -eq 1 ]; then
    log "Health probe only..."
    curl -fsS "${APP_URL:-http://localhost}/api/health/pbb" | head -c 2000
    echo
    exit 0
fi

# 1. Pull
log "git pull origin master..."
git pull origin master

# 2. PHP deps
log "composer install (no-dev, optimize)..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 3. JS build
log "npm ci && npm run build..."
npm ci --silent
npm run build

# 4. Migrations
log "artisan migrate --force..."
php artisan migrate --force

# 5. Cache: clear stale, rebuild
log "artisan config/route/view cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Storage symlink (idempotent)
log "ensure storage symlink..."
php artisan storage:link || true

# 7. PBB data — only if first deploy or explicitly forced
if [ "$SKIP_DATA" -eq 0 ]; then
    PBB_COUNT=$(php artisan tinker --execute='echo \DB::table("pbb_records")->count();' 2>/dev/null | tail -1)
    if [ "${PBB_COUNT:-0}" -lt 100000 ]; then
        log "PBB belum ter-import (count=$PBB_COUNT). Jalankan: php artisan pbb:import storage/app/private/data-pbb/<file>.csv"
        log "Skipping import — manual step (Bapenda harus drop CSV dulu). Lihat DEPLOY_STAGING.md §5."
    else
        log "PBB ada $PBB_COUNT records, recompute reconciliation summary..."
        php artisan pbb:recompute-reconciliation
    fi
fi

# 8. Health probe
log "health probe..."
sleep 2
curl -fsS "${APP_URL:-http://localhost}/api/health/pbb" | head -c 1500
echo
log "deploy-staging selesai."
