#!/bin/bash
# Auto-deploy script triggered by GitHub webhook
# Runs on VPS host (not inside container)

REPO_DIR="/var/www/SIBEDAS/sibedas_dev/sibedas_dev"
APP_DIR="/var/www"
CONTAINER="sibedas_app"
COMPOSE_FILE="/var/www/SIBEDAS/sibedas_dev/docker-compose.yml"

echo "[$(date)] === Auto-deploy started ==="

# Pull latest code
cd "$REPO_DIR"
git config --global --add safe.directory "$REPO_DIR"
git pull origin master 2>&1

# Get changed files from last commit
CHANGED=$(git diff --name-only HEAD~1 HEAD 2>/dev/null)
echo "[$(date)] Changed files: $CHANGED"

# Docker cp each changed file into container
while IFS= read -r file; do
    [ -z "$file" ] && continue
    # Strip sibedas_dev/ prefix if present
    file="${file#sibedas_dev/}"
    LOCAL="$REPO_DIR/$file"
    [ -f "$LOCAL" ] || continue
    DEST="$APP_DIR/$file"
    docker cp "$LOCAL" "$CONTAINER:$DEST" 2>&1
    echo "[$(date)] Copied: $file"
done <<< "$CHANGED"

# Clear Laravel cache
docker exec "$CONTAINER" php artisan optimize:clear 2>&1

echo "[$(date)] === Auto-deploy complete ==="
