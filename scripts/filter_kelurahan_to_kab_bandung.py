"""
Phase 7 — filter Overpass kelurahan dump to only those whose name matches a
known Kab. Bandung kelurahan from pbb_kelurahan_lookup. The simplified
kecamatan_kab_bandung.geojson polygons are too coarse for parent-containment
filtering, so we use authoritative DJP names instead.

Input:
  public/data/kelurahan_kab_bandung.geojson (raw, 409 features)
  storage/app/private/data-pbb/pbb_kelurahan_names.json (275 authoritative)
Output: public/data/kelurahan_kab_bandung.geojson overwritten with filtered
features tagged with parent kecamatan from PBB.
"""
import json, os, re
from collections import Counter
from shapely.geometry import shape

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
KEL = os.path.join(ROOT, 'public', 'data', 'kelurahan_kab_bandung.geojson')
NAMES = os.path.join(ROOT, 'storage', 'app', 'private', 'data-pbb', 'pbb_kelurahan_names.json')

def normalize(s: str) -> str:
    if not s: return ''
    s = s.upper().strip()
    for p in ('DESA ', 'KELURAHAN '):
        if s.startswith(p): s = s[len(p):]
    s = re.sub(r'[^A-Z0-9 ]', ' ', s)
    s = re.sub(r'\s+', ' ', s).strip()
    return s

names = json.load(open(NAMES, 'r', encoding='utf-8'))
# Build lookup: normalized_name -> [list of (kec, kel)]
lookup = {}
for r in names:
    key = normalize(r['kel'])
    lookup.setdefault(key, []).append((r['kec'], r['kel']))
print(f'Authoritative: {len(names)} kelurahan, {len(lookup)} unique normalized keys')

raw = json.load(open(KEL, 'r', encoding='utf-8'))
print(f'Raw OSM: {len(raw["features"])} features')

kept = []
unmatched_osm = []
for feat in raw['features']:
    osm_name = feat['properties'].get('name', '')
    key = normalize(osm_name)
    if key in lookup:
        # If multiple kec have same kel name, just use first match
        # (we resolve later via centroid containment among Kab Bandung kec)
        cands = lookup[key]
        kec_name, kel_name = cands[0]
        # Validate geometry
        try:
            g = shape(feat['geometry'])
            if not g.is_valid: g = g.buffer(0)
            if g.is_empty: continue
        except Exception:
            continue
        feat['properties']['name'] = kel_name
        feat['properties']['raw_name'] = osm_name
        feat['properties']['kecamatan'] = kec_name
        feat['properties']['_alts'] = len(cands)
        kept.append(feat)
    else:
        unmatched_osm.append(osm_name)

print(f'Matched: {len(kept)} | Unmatched OSM names: {len(unmatched_osm)}')

# Find PBB kelurahan that have NO geometry match
matched_keys = set()
for feat in kept:
    matched_keys.add(normalize(feat['properties']['name']))
missing_pbb = []
for r in names:
    if normalize(r['kel']) not in matched_keys:
        missing_pbb.append(f"{r['kec']}/{r['kel']}")
print(f'PBB kelurahan WITHOUT polygon: {len(missing_pbb)}')
if missing_pbb[:10]:
    print('  examples:', missing_pbb[:10])

# Sort
kept.sort(key=lambda f: (f['properties']['kecamatan'], f['properties']['name']))
out = {'type': 'FeatureCollection', 'features': kept}
with open(KEL, 'w', encoding='utf-8') as f:
    json.dump(out, f, ensure_ascii=False)
print(f'Wrote -> {KEL}')

ctr = Counter(f['properties']['kecamatan'] for f in kept)
print(f'Coverage: {len(ctr)}/31 kec')
for k, v in sorted(ctr.items()):
    print(f'  {k}: {v}')
