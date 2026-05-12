#!/usr/bin/env bash
# Download Google Open Buildings v3 footprints for Kab. Bandung into a CSV
# that buildings:import-open-buildings can ingest with full WKT polygons.
#
# Prerequisites:
#   - gcloud CLI installed + authenticated (`gcloud auth application-default login`)
#   - A GCP project with BigQuery enabled and a default billing account
#     (the query scans ~2 GB of public data; first 1 TB/month is free)
#   - bq CLI available (ships with gcloud)
#
# Output: storage/app/open-buildings/bandung_buildings.csv
# Expected size: ~400-600 MB, ~1.1 M rows.
#
# Re-run buildings:import-open-buildings --source=google after this script.

set -euo pipefail

OUT_DIR="storage/app/open-buildings"
OUT_FILE="${OUT_DIR}/bandung_buildings.csv"

# Bbox kept in sync with ImportOpenBuildings::LAT_MIN/MAX/LNG_MIN/MAX
LAT_MIN="-7.32"
LAT_MAX="-6.80"
LNG_MIN="107.23"
LNG_MAX="107.96"

mkdir -p "${OUT_DIR}"

if ! command -v bq >/dev/null 2>&1; then
  echo "Error: bq CLI not found. Install gcloud SDK: https://cloud.google.com/sdk/docs/install" >&2
  exit 1
fi

echo "Querying Google Open Buildings v3 for bbox [${LAT_MIN},${LNG_MIN}] - [${LAT_MAX},${LNG_MAX}]..."
echo "Output: ${OUT_FILE}"

bq query \
  --use_legacy_sql=false \
  --format=csv \
  --max_rows=10000000 \
  --nouse_cache \
  "SELECT
     latitude,
     longitude,
     area_in_meters,
     confidence,
     ST_AsText(geometry) AS geometry
   FROM \`bigquery-public-data.open_buildings_v3.buildings\`
   WHERE latitude  BETWEEN ${LAT_MIN} AND ${LAT_MAX}
     AND longitude BETWEEN ${LNG_MIN} AND ${LNG_MAX}" \
  > "${OUT_FILE}"

rows=$(($(wc -l < "${OUT_FILE}") - 1))
size=$(du -h "${OUT_FILE}" | cut -f1)
echo "Downloaded ${rows} rows (${size}) to ${OUT_FILE}"
echo "Next: php artisan buildings:import-open-buildings --source=google"
