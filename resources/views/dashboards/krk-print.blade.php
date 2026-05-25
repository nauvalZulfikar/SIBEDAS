<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lampiran KRK — {{ $kecamatan }}</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.vectorgrid@1.3.0/dist/Leaflet.VectorGrid.bundled.js"></script>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 210mm; height: 297mm; font-family: Arial, sans-serif; font-size: 9pt; color: #111; }
        .page { width: 210mm; height: 297mm; padding: 4mm; display: grid;
            grid-template-columns: 132mm 66mm;
            grid-template-rows: 10mm 100mm 38mm 78mm 50mm;
            gap: 2mm; }
        .header { grid-column: 1 / span 2; border: 1px solid #111; padding: 4mm;
            display: flex; align-items: center; justify-content: space-between; }
        .header h1 { font-size: 14pt; }
        .header .meta { text-align: right; font-size: 8pt; line-height: 1.3; }
        .main-map { grid-column: 1; grid-row: 2 / span 3; border: 1px solid #111; position: relative; overflow: hidden; }
        .main-map .map-canvas { width: 100%; height: 100%; }
        .inset-orientasi { grid-column: 2; grid-row: 2; border: 1px solid #111; position: relative; overflow: hidden; }
        .inset-label { position: absolute; top: 2mm; left: 2mm; right: 2mm;
            background: rgba(255,255,255,0.85); padding: 1mm 2mm; font-size: 8pt;
            font-weight: bold; text-align: center; z-index: 500; }
        .inset-orientasi .map-canvas { width: 100%; height: 100%; }
        .stat-box { grid-column: 2; grid-row: 3; border: 1px solid #111; padding: 3mm;
            display: grid; grid-template-columns: 1fr 1fr; gap: 1mm 3mm; }
        .stat-box .stat { line-height: 1.4; }
        .stat-box .stat .lbl { font-size: 7pt; color: #555; text-transform: uppercase; }
        .stat-box .stat .val { font-size: 11pt; font-weight: bold; }
        .legend { grid-column: 2; grid-row: 4; border: 1px solid #111; padding: 2mm;
            font-size: 7.5pt; }
        .legend h3 { font-size: 8pt; text-align: center; border-bottom: 1px solid #555;
            padding-bottom: 1mm; margin-bottom: 1.5mm; }
        .legend .item { display: flex; align-items: center; margin-bottom: 1mm; }
        .legend .swatch { width: 8mm; height: 4mm; border: 0.5px solid #333; margin-right: 2mm; flex-shrink: 0; }
        .zone-table { grid-column: 1; grid-row: 5; border: 1px solid #111; padding: 2mm;
            font-size: 8pt; }
        .zone-table h3 { font-size: 9pt; text-align: center; margin-bottom: 1.5mm; }
        .zone-table table { width: 100%; border-collapse: collapse; }
        .zone-table td, .zone-table th { border: 0.3px solid #444; padding: 0.5mm 1mm; text-align: left; }
        .zone-table th { background: #f3f4f6; font-weight: bold; font-size: 7.5pt; }
        .zone-table td.num { text-align: right; }
        .scale-block { grid-column: 2; grid-row: 5; border: 1px solid #111; padding: 2mm;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; font-size: 7.5pt; }
        .scale-block .compass { font-size: 24pt; }
        .scale-block .scale-bar { width: 80%; height: 3mm; background: linear-gradient(
            to right, #111 0 50%, #fff 50% 100%); border: 0.5px solid #111; margin-top: 1mm; }
        /* Hide leaflet attribution to keep layout tidy */
        .leaflet-control-attribution, .leaflet-control-zoom { display: none !important; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <h1>LAMPIRAN KETERANGAN RENCANA KOTA (KRK)</h1>
            <div style="font-size: 10pt; margin-top: 1mm;">Kecamatan {{ $kecamatan }} · Kab. Bandung</div>
        </div>
        <div class="meta">
            <div>DPUTR Kabupaten Bandung</div>
            <div>Generated: {{ $generatedAt }}</div>
            <div>Sumber zona: OSM proxy (pending Perda 1/2024)</div>
        </div>
    </div>

    <div class="main-map">
        <div class="inset-label">PETA POLA RUANG — KECAMATAN {{ strtoupper($kecamatan) }}</div>
        <div id="main-map" class="map-canvas"></div>
    </div>

    <div class="inset-orientasi">
        <div class="inset-label">ORIENTASI KABUPATEN</div>
        <div id="orientasi-map" class="map-canvas"></div>
    </div>

    <div class="stat-box">
        <div class="stat"><div class="lbl">Total bangunan terdeteksi</div><div class="val">{{ number_format($totalDetected, 0, ',', '.') }}</div></div>
        <div class="stat"><div class="lbl">Tanpa izin sah</div><div class="val" style="color:#dc2626">{{ number_format($withoutPermit, 0, ',', '.') }}</div></div>
        <div class="stat"><div class="lbl">SK PBG terbit</div><div class="val" style="color:#16a34a">{{ number_format($permitValid, 0, ',', '.') }}</div></div>
        <div class="stat"><div class="lbl">PBG dalam proses</div><div class="val" style="color:#f59e0b">{{ number_format($permitInProcess, 0, ',', '.') }}</div></div>
    </div>

    <div class="legend">
        <h3>LEGENDA POLA RUANG</h3>
        @php
            $legend = [
                ['permukiman','#fde68a','Permukiman'],
                ['perdagangan_jasa','#f97316','Perdagangan & Jasa'],
                ['industri','#9333ea','Industri'],
                ['pertanian','#86efac','Pertanian'],
                ['rth','#22c55e','RTH / Taman'],
                ['kawasan_lindung','#15803d','Kawasan Lindung'],
                ['badan_air','#3b82f6','Badan Air'],
                ['sosial_pemakaman','#a3a3a3','Sosial & Pemakaman'],
                ['militer','#7f1d1d','Militer / Hankam'],
            ];
        @endphp
        @foreach ($legend as [$key, $color, $label])
            <div class="item"><span class="swatch" style="background:{{ $color }}"></span>{{ $label }}</div>
        @endforeach
    </div>

    <div class="zone-table">
        <h3>RINCIAN LUAS ZONA — KECAMATAN {{ strtoupper($kecamatan) }}</h3>
        <table>
            <thead>
                <tr><th>Kategori Zona</th><th class="num">Jumlah Polygon</th><th class="num">Luas (Ha)</th></tr>
            </thead>
            <tbody>
                @forelse ($zoneRows as $row)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' & ', $row->zone_category)) }}</td>
                        <td class="num">{{ number_format($row->jumlah, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($row->total_ha, 1, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:#888">Data zona belum tersedia untuk kecamatan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="scale-block">
        <div class="compass">⬆</div>
        <div>UTARA</div>
        <div class="scale-bar"></div>
        <div style="margin-top:1mm">Skala adaptif</div>
    </div>
</div>

<script>
const KEC_NAME = @json($kecamatan);
// Kab. Bandung bbox
const BS_BOUNDS = [[-7.310, 107.250], [-6.810, 107.940]];

const ZONE_COLOR = {
    permukiman:'#fde68a', perdagangan_jasa:'#f97316', industri:'#9333ea',
    pertanian:'#86efac', rth:'#22c55e', kawasan_lindung:'#15803d',
    badan_air:'#3b82f6', sosial_pemakaman:'#a3a3a3', militer:'#7f1d1d',
    lainnya:'#e5e7eb',
};

// Main map — focused on kecamatan; render RTRW polygons
const mainMap = L.map('main-map', {
    zoomControl: false, attributionControl: false, preferCanvas: true,
}).setView([-7.05, 107.55], 12);

L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 18, opacity: 0.6,
}).addTo(mainMap);

// Print view runs in a headless browser on the same host as Martin,
// so we can hit Martin directly on loopback without auth proxy.
// Pre-loaded GeoJSON from controller (one query per kec, embedded inline).
// Simpler and renders reliably without vector-tile layer-name guesswork.
const RTRW_GEOJSON = @json($rtrwGeojson);
const rtrwLayer = L.geoJSON(RTRW_GEOJSON, {
    style: f => ({
        fillColor: f.properties.color_hex || ZONE_COLOR[f.properties.zone_category] || '#e5e7eb',
        fillOpacity: 0.7,
        color: '#1f2937',
        weight: 0.4,
        opacity: 0.8,
    }),
}).addTo(mainMap);

// Kecamatan geojson is inlined by the controller — works under file:// too.
const KEC_GEOJSON = @json($kecGeojson);
(function(){
    const gj = KEC_GEOJSON;
    if (!gj) { window.__krkReady = true; return; }
    const f = (gj.features || []).find(x => (x.properties?.name || '').toUpperCase() === KEC_NAME.toUpperCase());
    if (f) {
        const layer = L.geoJSON(f, { style: { color:'#dc2626', weight:2.5, fill:false, opacity:1 } }).addTo(mainMap);
        mainMap.fitBounds(layer.getBounds(), { padding: [10, 10] });
    }
    // Mini orientasi map — show kecamatan-highlighted as red within kabupaten
    const orientasi = L.map('orientasi-map', { zoomControl:false, attributionControl:false, preferCanvas:true });
    L.geoJSON(gj, {
        style: feat => ((feat.properties?.name || '').toUpperCase() === KEC_NAME.toUpperCase())
            ? { color:'#dc2626', weight:2, fillColor:'#fecaca', fillOpacity:0.8 }
            : { color:'#94a3b8', weight:0.5, fillColor:'#f1f5f9', fillOpacity:0.6 },
    }).addTo(orientasi);
    orientasi.fitBounds(BS_BOUNDS);

    // Signal to headless browser that the page is ready to be PDF-captured.
    window.__krkReady = true;
})();

// Safety beacon for the headless capturer
window.addEventListener('load', () => {
    setTimeout(() => { window.__krkReady = true; }, 5000);
});
</script>
</body>
</html>
