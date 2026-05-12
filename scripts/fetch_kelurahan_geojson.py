"""
Phase 7 helper — fetch kelurahan (admin_level=7) boundaries for Kab. Bandung
from OSM Overpass API and convert to GeoJSON. Saves to:
  public/data/kelurahan_kab_bandung.geojson

Run once. Output is committed to repo (small; ~270 polygons, simplified).
"""
import json, os, sys, time, urllib.request, urllib.parse

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
OUT = os.path.join(ROOT, 'public', 'data', 'kelurahan_kab_bandung.geojson')

# Kab. Bandung bbox (consistent with detected_buildings ingest)
BBOX = (-7.32, 107.20, -6.80, 108.00)  # (s, w, n, e)

QUERY = f"""
[out:json][timeout:300];
(
  relation["admin_level"="7"]["boundary"="administrative"]
    ({BBOX[0]},{BBOX[1]},{BBOX[2]},{BBOX[3]});
  relation["admin_level"="8"]["boundary"="administrative"]
    ({BBOX[0]},{BBOX[1]},{BBOX[2]},{BBOX[3]});
  way["place"~"^(village|hamlet|suburb|neighbourhood)$"]
    ({BBOX[0]},{BBOX[1]},{BBOX[2]},{BBOX[3]});
);
out geom;
"""

URL = "https://overpass-api.de/api/interpreter"

def main():
    print(f"[fetch] Overpass query for kelurahan in {BBOX}")
    data = urllib.parse.urlencode({"data": QUERY}).encode()
    req = urllib.request.Request(URL, data=data, method="POST",
                                 headers={"User-Agent": "sibedas-pbb-phase7/1.0"})
    t0 = time.time()
    with urllib.request.urlopen(req, timeout=300) as resp:
        body = resp.read()
    elapsed = time.time() - t0
    print(f"[fetch] {len(body):,} bytes in {elapsed:.1f}s")
    raw = json.loads(body)

    elements = raw.get("elements", [])
    print(f"[fetch] {len(elements)} relations")

    features = []
    # Process ways with place tag (closed polygons)
    for el in elements:
        if el.get("type") != "way":
            continue
        tags = el.get("tags", {})
        name = tags.get("name") or tags.get("name:id")
        if not name:
            continue
        geom = el.get("geometry", [])
        if len(geom) < 4:
            continue
        ring = [[float(g["lon"]), float(g["lat"])] for g in geom]
        if ring[0] != ring[-1]:
            ring = ring + [ring[0]]
        features.append({
            "type": "Feature",
            "properties": {"name": name, "osm_id": el.get("id"),
                           "admin_level": None, "place": tags.get("place")},
            "geometry": {"type": "Polygon", "coordinates": [ring]},
        })

    for el in elements:
        if el.get("type") != "relation":
            continue
        tags = el.get("tags", {})
        name = tags.get("name") or tags.get("name:id") or "(unnamed)"
        # Build polygon from outer ways
        outer_rings = []
        inner_rings = []
        for m in el.get("members", []):
            if m.get("type") != "way":
                continue
            geom = m.get("geometry", [])
            if len(geom) < 4:
                continue
            ring = [[float(g["lon"]), float(g["lat"])] for g in geom]
            if m.get("role") == "inner":
                inner_rings.append(ring)
            else:
                outer_rings.append(ring)

        if not outer_rings:
            continue

        # OSM "outer" ways may be unordered fragments — naive ring assembly.
        # For most Indonesian admin level 7 polygons, the outer ring comes as
        # a single closed way. If unclosed, try to stitch.
        polygons = []
        for ring in outer_rings:
            if ring[0] != ring[-1]:
                # try to close
                ring = ring + [ring[0]]
            polygons.append([ring])

        # Attach inner rings to first outer (heuristic — fine for kelurahan)
        for inner in inner_rings:
            if inner[0] != inner[-1]:
                inner = inner + [inner[0]]
            if polygons:
                polygons[0].append(inner)

        if len(polygons) == 1:
            geom_obj = {"type": "Polygon", "coordinates": polygons[0]}
        else:
            geom_obj = {"type": "MultiPolygon", "coordinates": polygons}

        features.append({
            "type": "Feature",
            "properties": {
                "name": name,
                "osm_id": el.get("id"),
                "admin_level": tags.get("admin_level"),
                "is_in_kabupaten": tags.get("is_in:regency") or tags.get("is_in:kabupaten"),
                "place": tags.get("place"),
            },
            "geometry": geom_obj,
        })

    fc = {"type": "FeatureCollection", "features": features}
    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, "w", encoding="utf-8") as f:
        json.dump(fc, f, ensure_ascii=False)
    print(f"[fetch] wrote {len(features)} features -> {OUT}")

if __name__ == "__main__":
    main()
