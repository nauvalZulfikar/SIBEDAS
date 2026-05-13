#!/usr/bin/env python3
"""Pre-warm Redis polygon-tile cache around common Kab. Bandung viewing
centres so the next browser load hits a warm cache (<100 ms/tile)
instead of paying the ~25 s cold per-tile cost.

Usage:
  export SIBEDAS_TOKEN=$(php artisan tinker --execute=...)
  python3 scripts/prewarm_polygon_cache.py
"""
import math
import os
import subprocess
import time
import urllib.request

CENTRES = [
    (107.595, -6.97,  'Margahayu'),
    (107.625, -7.01,  'Baleendah'),
    (107.51,  -7.04,  'Soreang'),
    (107.46,  -7.09,  'Banjaran'),
    (107.55,  -6.95,  'Margaasih'),
    (107.62,  -6.96,  'Bojongsoang'),
    (107.85,  -6.99,  'Cicalengka'),
    (107.82,  -7.03,  'Cikancung'),
    (107.80,  -6.96,  'Rancaekek'),
    (107.94,  -7.00,  'Nagreg'),
]
ZOOMS = [14, 15, 16, 17, 18]  # full coverage after cache flush
BASE = 'http://127.0.0.1:8002/api/tiles/buildings'
QS   = '?exclude_source=microsoft_footprints'

def ll2xy(lng, lat, z):
    n = 2 ** z
    x = int((lng + 180) / 360 * n)
    y = int((1 - math.log(math.tan(math.radians(lat)) + 1 / math.cos(math.radians(lat))) / math.pi) / 2 * n)
    return x, y

def get_token():
    tok = os.environ.get('SIBEDAS_TOKEN')
    if tok: return tok
    out = subprocess.check_output(
        ['php', 'artisan', 'tinker', '--execute=echo \\App\\Models\\User::find(5)->createToken("prewarm")->plainTextToken;'],
        text=True
    )
    return out.strip().splitlines()[-1]

token = get_token()
headers = {'Authorization': f'Bearer {token}'}

total = 0
hit = 0
miss = 0
start = time.time()

for lng, lat, name in CENTRES:
    for z in ZOOMS:
        cx, cy = ll2xy(lng, lat, z)
        for dx in (-1, 0, 1):
            for dy in (-1, 0, 1):
                url = f'{BASE}/{z}/{cx + dx}/{cy + dy}.pbf{QS}'
                total += 1
                try:
                    req = urllib.request.Request(url, headers=headers)
                    with urllib.request.urlopen(req, timeout=90) as r:
                        cache_header = r.headers.get('X-Cache', '-')
                        size = len(r.read())
                    if cache_header == 'HIT':
                        hit += 1
                    else:
                        miss += 1
                except Exception as e:
                    print(f'  ! {url} → {e}')
        elapsed = int(time.time() - start)
        print(f'  {name:<12} z={z}  done  cumulative hit={hit} miss={miss} total={total}  elapsed={elapsed}s', flush=True)

print(f'\n== done: hit={hit} miss={miss} total={total} in {int(time.time() - start)}s ==')
