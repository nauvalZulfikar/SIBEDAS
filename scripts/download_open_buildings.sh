#!/usr/bin/env bash
# Download Google Open Buildings v3 footprints for Kab. Bandung into a CSV
# that buildings:import-open-buildings can ingest with full WKT polygons.
#
# Background: the v3 dataset is sharded by S2 cell. For Kab. Bandung
# (lat ~-7, lng ~107.5), all four bbox corners land in the same S2
# level-6 cell `2e69`, distributed as:
#   gs://open-buildings-data/v3/polygons_s2_level_6_gzip_no_header/2e69_buildings.csv.gz
# That single file is 1.6 GB compressed (~10 GB uncompressed). The
# script streams it through gunzip + awk so we never store the full
# unfiltered set — only Kab. Bandung rows (~1.1 M of ~50 M global rows
# in that cell) reach disk.
#
# Output: storage/app/open-buildings/bandung_buildings.csv
# Expected ~400-600 MB final, ~1.1 M rows.
#
# Prerequisites:
#   - gcloud + gsutil installed and authenticated
#   - awk + gunzip (Git Bash on Windows has both)
#
# Re-run buildings:import-open-buildings --source=google after this script.

set -euo pipefail

# Ensure gcloud SDK is on PATH when invoked from a shell that pre-dates
# the user-PATH update (Git Bash inherits PATH at launch).
for candidate in \
    "/d/dev-tools/google-cloud-sdk/bin" \
    "D:/dev-tools/google-cloud-sdk/bin" \
    "$HOME/google-cloud-sdk/bin"; do
    if [ -d "$candidate" ]; then
        case ":$PATH:" in
            *":$candidate:"*) ;;
            *) export PATH="$candidate:$PATH" ;;
        esac
    fi
done

OUT_DIR="storage/app/open-buildings"
OUT_FILE="${OUT_DIR}/bandung_buildings.csv"
GCS="gs://open-buildings-data/v3/polygons_s2_level_6_gzip_no_header/2e69_buildings.csv.gz"

# Kab. Bandung bbox — kept in sync with ImportOpenBuildings constants.
LAT_MIN="-7.32"
LAT_MAX="-6.80"
LNG_MIN="107.23"
LNG_MAX="107.96"

mkdir -p "${OUT_DIR}"

if ! command -v gsutil >/dev/null 2>&1; then
    echo "Error: gsutil not found. Install gcloud SDK + run gcloud auth login." >&2
    exit 1
fi
if ! command -v gunzip >/dev/null 2>&1; then
    echo "Error: gunzip not found. Install Git Bash or MSYS2." >&2
    exit 1
fi

echo "Streaming Google Open Buildings v3 cell 2e69 (1.6 GB compressed),"
echo "filtering to Kab. Bandung bbox [${LAT_MIN},${LNG_MIN}] - [${LAT_MAX},${LNG_MAX}]..."
echo "Output: ${OUT_FILE}"
echo "This typically takes 10-25 min depending on connection."
echo

# Write header first — ImportOpenBuildings expects a header row with the
# standard column names.
echo "latitude,longitude,area_in_meters,confidence,geometry,full_plus_code" > "${OUT_FILE}"

# Stream: gsutil cat → gunzip → awk filter by bbox → append.
# The 2e69 file is "no_header" so we don't have a header line to skip.
# Quoted WKT polygons may contain commas inside parentheses, so use a
# very simple approach: split on the FIRST four commas only — first
# four fields are simple numbers, the rest is geometry+plus_code.
gsutil cat "${GCS}" \
    | gunzip \
    | awk -F, -v latmin="${LAT_MIN}" -v latmax="${LAT_MAX}" \
                -v lngmin="${LNG_MIN}" -v lngmax="${LNG_MAX}" '
        ($1+0) >= latmin && ($1+0) <= latmax \
        && ($2+0) >= lngmin && ($2+0) <= lngmax \
      ' \
    >> "${OUT_FILE}"

rows=$(($(wc -l < "${OUT_FILE}") - 1))
size=$(du -h "${OUT_FILE}" | cut -f1)
echo
echo "Downloaded + filtered ${rows} rows (${size}) to ${OUT_FILE}"
echo "Next: php artisan buildings:import-open-buildings --source=google"
