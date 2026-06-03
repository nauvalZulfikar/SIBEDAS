#!/usr/bin/env bash
# Deploy 2026-05-28: fix Dashboard Pimpinan link + drop Monitoring Satelit menu
# Run from Windows (Git-Bash) atau device manapun yg bisa SSH ke 72.60.196.21.
# Eksekusi: bash deploy-2026-05-28.sh
set -euo pipefail

VPS="root@72.60.196.21"
APP_DIR="/root/projects/sibedaspbg"

echo "==> 1/3 Patch leader.blade.php"
ssh "$VPS" "cd $APP_DIR && sed -i \"s/'filter' => 'all'/'filter' => 'potention'/\" resources/views/dashboards/leader.blade.php && grep -n 'filter.*potention' resources/views/dashboards/leader.blade.php"

echo "==> 2/3 Drop Monitoring Satelit menu + clear caches"
ssh "$VPS" "cd $APP_DIR && \
  docker compose exec -T mariadb mysql -uroot -p\${DB_PASSWORD:-\$(grep ^DB_PASSWORD= .env | cut -d= -f2-)} sibedas -e \"DELETE FROM menus WHERE url='dashboard.satellite-monitoring'; SELECT id,name,url FROM menus WHERE url LIKE '%satel%';\" && \
  docker compose exec -T app php artisan view:clear && \
  docker compose exec -T app php artisan cache:clear"

echo "==> 3/3 Verify"
ssh "$VPS" "cd $APP_DIR && grep -n 'filter.*=>' resources/views/dashboards/leader.blade.php | head -5"
echo "Done."
