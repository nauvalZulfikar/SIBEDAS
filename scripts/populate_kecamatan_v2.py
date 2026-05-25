"""
Ad-hoc helper — NOT part of deploy/CI.

v2: id-range pagination, autocommit on both connections. Avoids the
long-held read transaction in v1 that caused 1205 lock-wait timeouts
when MariaDB was under any concurrent load.

    python scripts/populate_kecamatan_v2.py

Updates detected_buildings.kecamatan via point-in-polygon against
public/data/kecamatan_kab_bandung.geojson. Rows whose centroid falls
outside the kecamatan polygons (i.e. outside Kab Bandung) keep NULL —
that is expected for source datasets that extend past the regency.
"""
import json, time, sys, os
from shapely.geometry import Point, shape
from shapely.prepared import prep
import mysql.connector

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
GEOJSON = os.path.join(ROOT, 'public', 'data', 'kecamatan_kab_bandung.geojson')

fc = json.load(open(GEOJSON, 'r', encoding='utf-8'))
kec_list = []
for feat in fc['features']:
    g = shape(feat['geometry'])
    kec_list.append({
        'name': feat['properties']['name'],
        'shape': prep(g),
        'bbox': g.bounds,
    })
print(f'Loaded {len(kec_list)} kecamatan polygons', flush=True)

cnx = mysql.connector.connect(
    host='127.0.0.1', user='root', password='', database='sibedas',
    autocommit=True,
)
cur = cnx.cursor()

cur.execute("SELECT MAX(id) FROM detected_buildings")
max_id = cur.fetchone()[0] or 0
cur.execute("SELECT COUNT(*) FROM detected_buildings WHERE kecamatan IS NULL")
null_total = cur.fetchone()[0]
print(f'max_id={max_id:,} null_rows={null_total:,}', flush=True)

CHUNK = 50000
last_id = 0
processed = 0
updated = 0
unmatched = 0
t0 = time.time()

while last_id < max_id:
    upper = last_id + CHUNK
    cur.execute(
        "SELECT id, latitude, longitude FROM detected_buildings "
        "WHERE id > %s AND id <= %s AND kecamatan IS NULL",
        (last_id, upper),
    )
    rows = cur.fetchall()  # small result, fully fetched -> no held transaction
    last_id = upper

    batch = []
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
            unmatched += 1
        processed += 1

    if batch:
        cur.executemany("UPDATE detected_buildings SET kecamatan=%s WHERE id=%s", batch)
        updated += len(batch)

    elapsed = time.time() - t0
    rate = processed / elapsed if elapsed > 0 else 0
    pct = (last_id / max_id * 100) if max_id else 0
    print(
        f'  id<={last_id:,} ({pct:.1f}%) processed={processed:,} '
        f'updated={updated:,} unmatched={unmatched:,} rate={rate:,.0f}/s '
        f'elapsed={elapsed:.0f}s',
        flush=True,
    )

cur.close()
cnx.close()

print(f'\nDone in {time.time()-t0:.1f}s')
print(f'Updated: {updated:,}')
print(f'Unmatched (outside Kab Bandung polygons): {unmatched:,}')
