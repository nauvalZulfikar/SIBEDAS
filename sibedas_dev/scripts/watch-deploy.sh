#!/bin/bash
# Runs every minute via cron on VPS host
# Watches for deploy.flag written by webhook controller

FLAG="/var/www/SIBEDAS/sibedas_dev/storage/deploy.flag"
REPO="/var/www/SIBEDAS/sibedas_dev/sibedas_dev"
APP_DIR="/var/www/SIBEDAS/sibedas_dev"
CONTAINER="sibedas_app"
LOG="/var/log/sibedas-deploy.log"

[ -f "$FLAG" ] || exit 0

echo "[$(date)] Deploy triggered" >> "$LOG"
rm -f "$FLAG"

# Git pull
cd "$REPO"
git config --global --add safe.directory "$REPO" 2>/dev/null
git pull origin master >> "$LOG" 2>&1

# Get changed files from last commit
CHANGED=$(git diff --name-only HEAD~1 HEAD 2>/dev/null)
echo "[$(date)] Changed: $CHANGED" >> "$LOG"

# Docker cp each changed file
while IFS= read -r file; do
    [ -z "$file" ] && continue
    file="${file#sibedas_dev/}"
    LOCAL="$REPO/$file"
    [ -f "$LOCAL" ] || continue
    docker cp "$LOCAL" "$CONTAINER:/var/www/$file" >> "$LOG" 2>&1
done <<< "$CHANGED"

# Clear cache
docker exec "$CONTAINER" php artisan optimize:clear >> "$LOG" 2>&1

echo "[$(date)] Deploy complete" >> "$LOG"
