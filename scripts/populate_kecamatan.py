"""
Ad-hoc helper — NOT part of deploy/CI.

Run manually after a fresh detected_buildings import:
    python scripts/populate_kecamatan.py

What it does: backfills detected_buildings.kecamatan via point-in-polygon
against the kecamatan polygons in public/data/kecamatan_kab_bandung.geojson.
The Laravel command `enrich:building-districts` does the same thing in PHP;
this script exists for one-off bulk runs where Python+Shapely is faster.

Requires: shapely, mysql-connector-python (`pip install shapely mysql-connector-python`).
"""
import json, time, sys, os
from shapely.geometry import Point, shape
from shapely.prepared import prep
import mysql.connector

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
GEOJSON = os.path.join(ROOT, 'public', 'data', 'kecamatan_kab_bandung.geojson')

# Load polygons
fc = json.load(open(GEOJSON, 'r', encoding='utf-8'))
kec_list = []  # (name, prepared_shape, bbox)
for feat in fc['features']:
    g = shape(feat['geometry'])
    kec_list.append({
        'name': feat['properties']['name'],
        'shape': prep(g),
        'bbox': g.bounds,  # (minx, miny, maxx, maxy)
    })
print(f'Loaded {len(kec_list)} kecamatan polygons')

# MariaDB: dua connection biar SELECT streaming ga nge-block UPDATE
cnx = mysql.connector.connect(host='127.0.0.1', user='root', password='', database='sibedas', autocommit=False)
cnx_w = mysql.connector.connect(host='127.0.0.1', user='root', password='', database='sibedas', autocommit=False)
cur = cnx_w.cursor()

# Get total count
cur.execute("SELECT COUNT(*) FROM detected_buildings")
total = cur.fetchone()[0]
print(f'Total rows: {total:,}')

CHUNK = 50000
updated = 0
null_count = 0
t0 = time.time()

# Scope to Kab Bandung rough bbox + resume dari row yg belum ter-populate
minx, miny, maxx, maxy = 107.20, -7.30, 107.90, -6.80

# Commit session-level optimizer: disable fsync-per-commit (OK utk one-shot bulk)
cur_init = cnx_w.cursor()
cur_init.execute("SET SESSION unique_checks = 0")
cur_init.execute("SET SESSION foreign_key_checks = 0")
try: cur_init.execute("SET SESSION innodb_flush_log_at_trx_commit = 2")
except: pass
cur_init.close()

cur_read = cnx.cursor()
cur_read.execute(f"""
    SELECT id, latitude, longitude
    FROM detected_buildings
    WHERE latitude BETWEEN {miny} AND {maxy}
      AND longitude BETWEEN {minx} AND {maxx}
      AND kecamatan IS NULL
""")

batch = []
processed = 0
while True:
    rows = cur_read.fetchmany(CHUNK)
    if not rows:
        break
    for rid, lat, lng in rows:
        lat_f, lng_f = float(lat), float(lng)
        pt = Point(lng_f, lat_f)
        matched = None
        for k in kec_list:
            minx_k, miny_k, maxx_k, maxy_k = k['bbox']
            if lng_f < minx_k or lng_f > maxx_k or lat_f < miny_k or lat_f > maxy_k:
                continue
            if k['shape'].contains(pt):
                matched = k['name']
                break
        if matched:
            batch.append((matched, rid))
        else:
            null_count += 1
        processed += 1

    if batch:
        cur.executemany("UPDATE detected_buildings SET kecamatan=%s WHERE id=%s", batch)
        cnx_w.commit()
        updated += len(batch)
        batch = []

    elapsed = time.time() - t0
    rate = processed / elapsed if elapsed > 0 else 0
    eta = (total - processed) / rate if rate > 0 else 0
    print(f'  processed={processed:,} updated={updated:,} null={null_count:,} rate={rate:,.0f}/s eta={eta:.0f}s', flush=True)

cur.close()
cur_read.close()
cnx.close()
cnx_w.close()

print(f'\nDone in {time.time()-t0:.1f}s')
print(f'Updated: {updated:,}')
print(f'Not assigned to any kecamatan: {null_count:,}')
