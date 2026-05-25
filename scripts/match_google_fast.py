"""
Fast PBG-match for Google polygons. Replaces the slow Eloquent-based
`buildings:match` command with KDTree + bulk UPDATE — ~5 min vs ~5 hours.

Steps:
  1. Load all PBG task details (id, lat, lng) from MySQL.
  2. Build a cKDTree over the PBG coords (approx as planar deg; 50m
     ≈ 0.00045° so we use 0.0005° threshold then haversine-verify).
  3. Stream Google polygons from detected_buildings WHERE
     matched_pbg_task_id IS NULL AND detection_source='google_open_buildings'
     in chunks of 100k.
  4. For each row, query KDTree.query_ball_point(coord, 0.0005),
     then haversine each candidate, pick nearest within 50m.
  5. Collect (pbg_id, distance, building_id) tuples; flush every 5000
     rows via bulk UPDATE using IF()/CASE in a single statement.

Usage:
  python scripts/match_google_fast.py
"""
import math
import time
import sys
import mysql.connector
from scipy.spatial import cKDTree

RADIUS_M = 50.0
COORD_THRESHOLD_DEG = 0.0005  # ~55m bounding box; haversine verifies exact 50m

def haversine_m(lat1, lng1, lat2, lng2):
    R = 6371000.0
    phi1 = math.radians(lat1); phi2 = math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dl   = math.radians(lng2 - lng1)
    a = math.sin(dphi/2)**2 + math.cos(phi1)*math.cos(phi2)*math.sin(dl/2)**2
    return 2*R*math.asin(math.sqrt(a))

cnx = mysql.connector.connect(host='127.0.0.1', user='root', password='',
                              database='sibedas', autocommit=True)
cur = cnx.cursor()

print('Loading PBG records with coords...', flush=True)
cur.execute(
    "SELECT id, latitude, longitude FROM pbg_task_details "
    "WHERE latitude IS NOT NULL AND longitude IS NOT NULL "
    "  AND latitude != 0 AND longitude != 0"
)
pbg = cur.fetchall()
print(f'  loaded {len(pbg)} PBG records', flush=True)

pbg_ids   = [p[0] for p in pbg]
pbg_coords = [(float(p[1]), float(p[2])) for p in pbg]
tree = cKDTree(pbg_coords)
print('  cKDTree built', flush=True)

print('Counting Google rows still NULL...', flush=True)
cur.execute(
    "SELECT COUNT(*) FROM detected_buildings "
    "WHERE detection_source='google_open_buildings' AND matched_pbg_task_id IS NULL"
)
total = cur.fetchone()[0]
print(f'  to process: {total:,}', flush=True)

CHUNK = 100_000
BATCH_UPDATE = 2000

last_id = 0
processed = 0
matched_count = 0
batch_updates = []  # (pbg_id, distance_m, building_id)
t0 = time.time()

def flush(batch):
    global matched_count
    if not batch: return
    # Single statement with N row updates via INSERT ... ON DUPLICATE KEY UPDATE
    # is cleaner than CASE WHEN. We send N statements as multi-stmt is off — so
    # use executemany on UPDATE.
    cur.executemany(
        "UPDATE detected_buildings SET matched_pbg_task_id=%s, match_distance_m=%s WHERE id=%s",
        batch,
    )
    matched_count += len(batch)

while True:
    cur.execute(
        "SELECT id, latitude, longitude FROM detected_buildings "
        "WHERE detection_source='google_open_buildings' "
        "  AND matched_pbg_task_id IS NULL "
        "  AND id > %s "
        "ORDER BY id LIMIT %s",
        (last_id, CHUNK),
    )
    rows = cur.fetchall()
    if not rows:
        break
    for rid, lat, lng in rows:
        last_id = rid
        latf = float(lat); lngf = float(lng)
        cand_idx = tree.query_ball_point((latf, lngf), COORD_THRESHOLD_DEG)
        if not cand_idx:
            processed += 1
            continue
        best_d = 9999.0
        best_pbg = None
        for idx in cand_idx:
            d = haversine_m(latf, lngf, pbg_coords[idx][0], pbg_coords[idx][1])
            if d < best_d and d <= RADIUS_M:
                best_d = d
                best_pbg = pbg_ids[idx]
        if best_pbg is not None:
            batch_updates.append((best_pbg, round(best_d, 2), rid))
            if len(batch_updates) >= BATCH_UPDATE:
                flush(batch_updates)
                batch_updates = []
        processed += 1

    elapsed = time.time() - t0
    pct = 100 * processed / total if total else 100
    rate = processed / elapsed if elapsed else 0
    eta = (total - processed) / rate if rate else 0
    print(
        f'  processed={processed:,} ({pct:.1f}%) matched={matched_count + len(batch_updates):,} '
        f'rate={rate:,.0f}/s elapsed={elapsed:.0f}s eta={eta:.0f}s',
        flush=True,
    )

flush(batch_updates)
cur.close()
cnx.close()
print(f'\nDone. matched={matched_count} processed={processed} in {time.time()-t0:.1f}s')
