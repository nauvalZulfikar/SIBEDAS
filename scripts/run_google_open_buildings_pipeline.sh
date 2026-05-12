#!/usr/bin/env bash
# All-in-one runner for Steps 4-7 of the Google Open Buildings polygon
# refresh. Run this AFTER you've completed Step 3 (gcloud auth login,
# gcloud config set project, gcloud auth application-default login).
#
# This script:
#   4. Downloads polygon CSV from BigQuery (~15 min)
#   5. Imports CSV into MySQL detected_buildings (~10 min)
#   6. Syncs the new MySQL rows into the PostGIS buildings table (~6 min)
#   7. Verifies the result (real_polygon counts per source)
#
# Logs to tmp/google-pipeline-YYYYMMDD-HHMMSS.log and prints major
# milestones to stdout so you can leave it unattended.

set -euo pipefail

cd "$(dirname "$0")/.."
TS=$(date +%Y%m%d-%H%M%S)
LOG="tmp/google-pipeline-${TS}.log"
mkdir -p tmp

echo "== Google Open Buildings polygon pipeline =="
echo "Logging to: $LOG"
echo

# --- Preflight ---
echo "[preflight] checking gcloud auth..."
if ! gcloud auth list --format='value(account)' 2>/dev/null | grep -q '@'; then
    echo "ERROR: gcloud auth login belum selesai. Jalanin dulu:"
    echo "  gcloud auth login"
    echo "  gcloud auth application-default login"
    exit 1
fi
echo "[preflight] gcloud account: $(gcloud auth list --format='value(account)' --filter=status:ACTIVE | head -1)"
echo "[preflight] gcloud project: $(gcloud config get-value project 2>/dev/null)"

if ! bq ls >/dev/null 2>&1; then
    echo "ERROR: 'bq ls' gagal. Cek: gcloud auth application-default login + billing project aktif."
    exit 1
fi
echo "[preflight] bq CLI ready"
echo

# --- Step 4: download ---
echo "== STEP 4: downloading Google Open Buildings (Kab. Bandung) =="
echo "  This typically takes 5-15 min and uses ~2 GB of your BigQuery free tier."
echo
SECONDS=0
bash scripts/download_open_buildings.sh 2>&1 | tee -a "$LOG"
ROWS=$(wc -l < storage/app/open-buildings/bandung_buildings.csv)
echo "[step 4] done in ${SECONDS}s — $((ROWS - 1)) rows"
echo

# --- Step 5: import to MySQL ---
echo "== STEP 5: importing into MySQL detected_buildings =="
SECONDS=0
# The artisan command has a 'Delete N existing google_open_buildings
# records?' prompt. Pipe `yes` to auto-confirm.
yes | php -d memory_limit=1G artisan buildings:import-open-buildings --source=google 2>&1 | tee -a "$LOG"
echo "[step 5] done in ${SECONDS}s"
echo

# --- Step 6: sync to PostGIS ---
echo "== STEP 6: syncing MySQL → PostGIS buildings =="
SECONDS=0
# Local fallback uses stdout mode + docker exec psql because XAMPP PHP
# has no pdo_pgsql.
php artisan buildings:sync-postgis --insert-batch=500 --via=stdout 2>>"$LOG" \
    | docker exec -i sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial -q
echo "[step 6] done in ${SECONDS}s"
echo

# --- Step 7: verify ---
echo "== STEP 7: verifying polygon coverage =="
echo
echo "BEFORE  (microsoft 0 real, google 5 test rows, osm ~17k real)"
echo "AFTER   (target: google ~1.09M real)"
echo
docker exec sibedas_postgis psql -U sibedas_spatial -d sibedas_spatial -c "
SELECT source,
       COUNT(*) AS total,
       COUNT(*) FILTER (WHERE ST_NPoints(geom) > 5) AS real_polygon,
       ROUND(100.0 * COUNT(*) FILTER (WHERE ST_NPoints(geom) > 5) / NULLIF(COUNT(*),0), 1) AS pct_real
FROM buildings GROUP BY source ORDER BY total DESC;"

# Flush tile cache so the next browser request rebuilds with the new polygons.
echo "[step 7] flushing tile cache..."
docker exec sibedas_redis redis-cli -n 1 FLUSHDB >/dev/null

echo
echo "== DONE =="
echo "Hard-refresh the satellite-monitoring page (Ctrl+Shift+R) and the"
echo "polygons across every kecamatan should now be real outlines."
echo "Log: $LOG"
