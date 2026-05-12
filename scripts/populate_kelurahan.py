"""
Phase 7 — backfill detected_buildings.building_ward_name via point-in-polygon
against public/data/kelurahan_kab_bandung.geojson.

Only buildings already tagged with `kecamatan` (Phase 1 BPS PIP) are processed,
and we constrain the kelurahan candidates to the matching kecamatan to avoid
cross-kec false positives where the OSM polygon has slight overlap.

Run:
    python scripts/populate_kelurahan.py
"""
import json, time, os, sys
from collections import defaultdict
from shapely.geometry import Point, shape
from shapely.prepared import prep
import mysql.connector

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
GEOJSON = os.path.join(ROOT, 'public', 'data', 'kelurahan_kab_bandung.geojson')

fc = json.load(open(GEOJSON, 'r', encoding='utf-8'))
# Group polygons by parent kec name (uppercased to match detected_buildings.kecamatan format)
by_kec = defaultdict(list)
for feat in fc['features']:
    g = shape(feat['geometry'])
    if not g.is_valid: g = g.buffer(0)
    if g.is_empty: continue
    kec = (feat['properties'].get('kecamatan') or '').strip()
    kel = feat['properties'].get('name')
    if not kec or not kel: continue
    by_kec[kec.title()].append({  # detected_buildings.kecamatan is Title Case
        'name': kel,
        'shape': prep(g),
        'bbox': g.bounds,
    })
print(f'Loaded polygons for {len(by_kec)} kec ({sum(len(v) for v in by_kec.values())} kelurahan total)')

cnx = mysql.connector.connect(host='127.0.0.1', user='root', password='', database='sibedas', autocommit=False)
cnx_w = mysql.connector.connect(host='127.0.0.1', user='root', password='', database='sibedas', autocommit=False)
cur_w = cnx_w.cursor()

# Process only buildings with kecamatan set + within Kab Bandung kec we have polygons for
covered_kec = list(by_kec.keys())
fmt = ','.join(['%s'] * len(covered_kec))
cur_count = cnx.cursor()
cur_count.execute(f"SELECT COUNT(*) FROM detected_buildings WHERE kecamatan IN ({fmt})", covered_kec)
total = cur_count.fetchone()[0]
print(f'Candidate rows (in covered kec): {total:,}')

CHUNK = 50000
updated = 0
processed = 0
no_match = 0
t0 = time.time()

cur_init = cnx_w.cursor()
cur_init.execute("SET SESSION unique_checks = 0")
cur_init.execute("SET SESSION foreign_key_checks = 0")
try: cur_init.execute("SET SESSION innodb_flush_log_at_trx_commit = 2")
except: pass
cur_init.close()

cur_read = cnx.cursor()
cur_read.execute(f"""
    SELECT id, latitude, longitude, kecamatan
    FROM detected_buildings
    WHERE kecamatan IN ({fmt})
""", covered_kec)

batch = []
while True:
    rows = cur_read.fetchmany(CHUNK)
    if not rows: break
    for rid, lat, lng, kec in rows:
        polys = by_kec.get(kec, [])
        if not polys:
            no_match += 1
            processed += 1
            continue
        lat_f, lng_f = float(lat), float(lng)
        pt = Point(lng_f, lat_f)
        matched = None
        for k in polys:
            minx_k, miny_k, maxx_k, maxy_k = k['bbox']
            if lng_f < minx_k or lng_f > maxx_k or lat_f < miny_k or lat_f > maxy_k:
                continue
            if k['shape'].contains(pt):
                matched = k['name']
                break
        if matched:
            batch.append((matched, rid))
        else:
            no_match += 1
        processed += 1

    if batch:
        cur_w.executemany("UPDATE detected_buildings SET building_ward_name=%s WHERE id=%s", batch)
        cnx_w.commit()
        updated += len(batch)
        batch = []

    elapsed = time.time() - t0
    rate = processed / elapsed if elapsed > 0 else 0
    eta = (total - processed) / rate if rate > 0 else 0
    print(f'  processed={processed:,} updated={updated:,} no_match={no_match:,} rate={rate:,.0f}/s eta={eta:.0f}s', flush=True)

cur_w.close(); cur_read.close(); cnx.close(); cnx_w.close()
print(f'\nDone in {time.time()-t0:.1f}s')
print(f'Updated: {updated:,} | No polygon match (in covered kec): {no_match:,}')
