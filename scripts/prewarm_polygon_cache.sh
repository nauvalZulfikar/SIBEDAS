#!/usr/bin/env bash
# Pre-fetch polygon tiles around the common Kab. Bandung viewing centres
# so the next browser load hits a warm Redis cache instead of paying the
# ~30 s cold per-tile cost.
#
# Idempotent. Runs ~5-10 minutes single-threaded (Laravel artisan serve
# is single-threaded, so parallel curl doesn't help much).

set -euo pipefail
cd "$(dirname "$0")/.."

if [ -z "${SIBEDAS_TOKEN:-}" ]; then
    SIBEDAS_TOKEN=$(php artisan tinker --execute="echo \App\Models\User::find(5)->createToken('prewarm')->plainTextToken;" 2>/dev/null | tail -1)
fi
H="Authorization: Bearer ${SIBEDAS_TOKEN}"

# (lng, lat) → (x, y) at given zoom for slippy map tiles.
ll2xy() {
    local lng=$1 lat=$2 z=$3
    python -c "
import math
lng, lat, z = $lng, $lat, $z
n = 2 ** z
x = int((lng + 180) / 360 * n)
y = int((1 - math.log(math.tan(math.radians(lat)) + 1/math.cos(math.radians(lat))) / math.pi) / 2 * n)
print(f'{x} {y}')
"
}

# Centres of interest (urban density, OSM hotspots).
declare -a CENTRES=(
    "107.595 -6.97 Margahayu"
    "107.625 -7.01 Baleendah"
    "107.51 -7.04 Soreang"
    "107.46 -7.09 Banjaran"
    "107.55 -6.95 Margaasih"
    "107.62 -6.96 Bojongsoang"
    "107.85 -6.99 Cicalengka"
    "107.82 -7.03 Cikancung"
    "107.80 -6.96 Rancaekek"
    "107.94 -7.00 Nagreg"
)

ZOOMS=(14 15 16 17)
TOTAL=0
HITS=0
START=$(date +%s)

for centre in "${CENTRES[@]}"; do
    read -r lng lat name <<< "$centre"
    for z in "${ZOOMS[@]}"; do
        read -r cx cy < <(ll2xy "$lng" "$lat" "$z")
        # 3x3 grid of tiles around the centre
        for dx in -1 0 1; do
            for dy in -1 0 1; do
                x=$((cx + dx))
                y=$((cy + dy))
                code=$(curl -s -o /dev/null -w "%{http_code}" -H "$H" \
                    "http://127.0.0.1:8002/api/tiles/buildings/${z}/${x}/${y}.pbf?exclude_source=microsoft_footprints" \
                    --max-time 60)
                TOTAL=$((TOTAL + 1))
                [ "$code" = "200" ] && HITS=$((HITS + 1))
            done
        done
        elapsed=$(($(date +%s) - START))
        printf "  %-12s z=%-2s done  cumulative %d/%d tiles  elapsed=%ds\n" "$name" "$z" "$HITS" "$TOTAL" "$elapsed"
    done
done

echo
echo "== prewarm done: ${HITS}/${TOTAL} tiles cached in $(($(date +%s) - START)) seconds =="
