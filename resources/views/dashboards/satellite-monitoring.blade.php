@extends('layouts.vertical', ['subtitle' => 'Monitoring Satelit'])

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
{{-- Phase 9 — Leaflet.VectorGrid (script tag in @section('scripts') below). Version pinned in package.json. No separate CSS file; styling comes from the layer's `vectorTileLayerStyles` option set in Phase 10. --}}
<style>
    #satellite-map-wrapper { position: relative; }
    #satellite-map { height: 550px; border-radius: 8px; }
    .legend-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 6px; }
    .stat-card { transition: transform 0.2s; cursor: pointer; }
    .stat-card:hover { transform: translateY(-2px); }
    #map-loading {
        position: absolute; top: 12px; right: 12px; z-index: 500;
        background: rgba(255,255,255,0.95); padding: 8px 14px; border-radius: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.15); display: none;
        align-items: center; gap: 8px; font-size: 13px; font-weight: 500; color: #333;
    }
    #map-loading.active { display: flex; }
    #map-loading .spinner {
        width: 14px; height: 14px; border: 2px solid #ddd; border-top-color: #0d6efd;
        border-radius: 50%; animation: mspin 0.7s linear infinite;
    }
    @keyframes mspin { to { transform: rotate(360deg); } }
    /* Phase 11 — fade transition for the polygon layer only. We DO NOT
       touch the global .leaflet-tile-pane opacity here because that pane
       also holds the base satellite imagery (Esri + Google Hybrid);
       hiding it would blank out the whole map at zoom-out. The polygon
       vector-tile layer lives on its own .vt-pane below. */
    .leaflet-pane.vt-pane { transition: opacity .2s ease; }
    .vt-mode-pill {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600;
        color: #fff; background: #6b7280; letter-spacing: .02em; cursor: help;
    }
    .vt-mode-pill.polygon { background: #1e88e5; }
    .vt-mode-pill.cluster { background: #6b7280; }
    .vt-hint {
        position: absolute; top: 8px; left: 50px; z-index: 500;
        background: rgba(13, 110, 253, 0.92); color: #fff; padding: 6px 12px;
        border-radius: 6px; font-size: 12px; font-weight: 500;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2); pointer-events: none;
        display: none;
    }
    .vt-hint.show { display: block; animation: fadeInOut 4s ease; }
    @keyframes fadeInOut {
        0%, 90% { opacity: 1; } 100% { opacity: 0; }
    }
    .district-label {
        background: rgba(15,23,42,0.88); color: #fff; font-size: 10px; font-weight: 500;
        padding: 4px 7px; border-radius: 4px; white-space: nowrap; pointer-events: none;
        border: none; box-shadow: 0 1px 4px rgba(0,0,0,0.35); line-height: 1.35;
        text-align: left;
    }
    .district-label strong { font-weight: 700; font-size: 11px; }
    .district-label .kc-stat { display: flex; align-items: center; gap: 4px; margin-top: 1px; }
    .district-label .kc-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .desa-label {
        background: rgba(37,99,235,0.92); color: #fff; font-size: 9px; font-weight: 600;
        padding: 1px 5px; border-radius: 3px; white-space: nowrap; pointer-events: none;
        border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
</style>
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Monitoring', 'subtitle' => 'Deteksi Bangunan Satelit — Kab. Kab. Bandung'])

<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-primary rounded me-3"><iconify-icon icon="solar:buildings-broken" class="fs-32 avatar-title text-primary"></iconify-icon></div>
            <div><p class="text-muted mb-0">Total Terdeteksi (satelit)</p><h4 class="mb-0" id="stat-total">-</h4></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-success rounded me-3"><iconify-icon icon="solar:verified-check-broken" class="fs-32 avatar-title text-success"></iconify-icon></div>
            <div><p class="text-muted mb-0">Ber-izin Sah (SK PBG Terbit)</p><h4 class="mb-0 text-success" id="stat-permit-valid">-</h4></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-danger rounded me-3"><iconify-icon icon="solar:danger-triangle-broken" class="fs-32 avatar-title text-danger"></iconify-icon></div>
            <div><p class="text-muted mb-0">Tanpa Izin Sah</p><h4 class="mb-0 text-danger" id="stat-without-permit">-</h4>
                <small class="text-muted" id="stat-without-breakdown">-</small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-warning rounded me-3"><iconify-icon icon="solar:chart-broken" class="fs-32 avatar-title text-warning"></iconify-icon></div>
            <div><p class="text-muted mb-0">Rasio Berizin</p><h4 class="mb-0 text-warning" id="stat-rate">-</h4>
                <small class="text-muted">terhadap total deteksi</small></div>
        </div></div></div>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-2 px-3">
    <div class="d-flex flex-wrap align-items-center gap-3 small">
        <span class="fw-semibold text-dark">PBG Tercatat di Database (Kab. Bandung):</span>
        <span>Total <b id="pbg-total">-</b></span>
        <span class="text-primary">● SK Terbit <b id="pbg-terbit">-</b></span>
        <span class="text-warning">● Proses <b id="pbg-proses">-</b></span>
        <span class="text-muted">● Ditolak/Batal <b id="pbg-ditolak">-</b></span>
        <span class="ms-auto text-muted fst-italic">Tanpa izin = tidak match PBG, match ke record terhapus, atau match ke PBG yg Ditolak/Batal.</span>
    </div>
</div></div>

<div class="card">
    <div class="card-header py-2"><h5 class="card-title mb-0">Filter</h5></div>
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <label class="form-label mb-1 small">Kecamatan</label>
                <select id="filter-district" class="form-select form-select-sm"><option value="">Semua Kecamatan</option></select>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <label class="form-label mb-1 small">Desa / Kelurahan</label>
                <select id="filter-desa" class="form-select form-select-sm" disabled><option value="">Pilih kecamatan dulu</option></select>
            </div>
            <div class="col-xl-3 col-md-6 col-sm-12">
                <label class="form-label mb-1 small">Jenis Bangunan</label>
                <select id="filter-data-source" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <optgroup label="🟦 USAHA — Reklame">
                        <option value="reklame_survey">Survey Lapangan</option>
                        <option value="tax_reklame">Pajak Reklame</option>
                    </optgroup>
                    <optgroup label="🟦 USAHA — Pajak Daerah">
                        <option value="tax_restoran">Pajak Restoran</option>
                        <option value="tax_hiburan">Pajak Hiburan</option>
                        <option value="tax_hotel">Pajak Hotel</option>
                        <option value="tax_parkir">Pajak Parkir</option>
                    </optgroup>
                    <optgroup label="🟦 USAHA — Bisnis">
                        <option value="umkm">UMKM</option>
                        <option value="pariwisata">Pariwisata (Tourism KBLI)</option>
                    </optgroup>
                    <optgroup label="🟦 USAHA — Tata Ruang / PBG">
                        <option value="tata_ruang_usaha">Tata Ruang Usaha</option>
                        <option value="ft_usaha">PBG: Tempat Usaha / UMKM</option>
                        <option value="ft_multifungsi">PBG: Multifungsi</option>
                    </optgroup>
                    <optgroup label="⬜ NON USAHA — Layanan">
                        <option value="pdam">PDAM (Air Bersih)</option>
                    </optgroup>
                    <optgroup label="⬜ NON USAHA — Tata Ruang / PBG">
                        <option value="tata_ruang_non_usaha">Tata Ruang Non Usaha</option>
                        <option value="ft_hunian">PBG: Hunian / Tempat Tinggal</option>
                        <option value="ft_sosial">PBG: Sosial Budaya</option>
                        <option value="ft_prasarana">PBG: Prasarana</option>
                        <option value="ft_ibadah">PBG: Ibadah / Keagamaan</option>
                        <option value="ft_pendidikan">PBG: Pendidikan / Kebudayaan</option>
                    </optgroup>
                </select>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <label class="form-label mb-1 small">Status PBG</label>
                <select id="filter-pbg-status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <optgroup label="🏛️ Dalam Sistem">
                        <option value="terbit">SK Terbit</option>
                        <option value="proses">Sedang Proses</option>
                        <option value="ditolak">Ditolak / Dibekukan</option>
                    </optgroup>
                    <optgroup label="🏚️ Luar Sistem">
                        <option value="luar_sistem">Tanpa Izin Sah (unmatched/orphan/ditolak)</option>
                    </optgroup>
                </select>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <label class="form-label mb-1 small">Luas Minimum</label>
                <select id="filter-min-area" class="form-select form-select-sm"><option value="" selected>Semua (termasuk noise)</option><option value="50">50+ m²</option><option value="100">100+ m²</option><option value="200">200+ m² (wajib PBG)</option><option value="500">500+ m²</option><option value="1000">1000+ m²</option></select>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <label class="form-label mb-1 small">Kategori Fungsi</label>
                <select id="filter-usage-category" class="form-select form-select-sm">
                    <option value="" selected>Semua</option>
                    <option value="usaha">🟦 Usaha (komersial/UMKM)</option>
                    <option value="non_usaha">🟩 Non-Usaha (hunian/sosial)</option>
                </select>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-12 d-flex align-items-end gap-2">
                <button class="btn btn-outline-secondary btn-sm flex-fill" id="btn-reset-view">
                    <iconify-icon icon="solar:refresh-broken" class="me-1"></iconify-icon>Reset
                </button>
                <button class="btn btn-primary btn-sm flex-fill" id="btn-export-krk" title="Generate Lampiran KRK A4 untuk kecamatan terpilih">
                    <iconify-icon icon="solar:document-broken" class="me-1"></iconify-icon>Cetak KRK
                </button>
            </div>
        </div>
        <div class="mt-2 d-flex flex-wrap gap-3 align-items-center small text-muted" style="display:none !important">
            {{-- 2026-05-19: cluster pin toggles di-hide. Keep inputs alive so existing JS
                 references (toggle-pbg-layer, toggle-sat-layer) jangan throw null deref. --}}
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="toggle-pbg-layer" checked>
                <label class="form-check-label" for="toggle-pbg-layer">Tampilkan titik PBG (izin dari database)</label>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="toggle-sat-layer" checked>
                <label class="form-check-label" for="toggle-sat-layer">Tampilkan deteksi satelit</label>
            </div>
            <span class="ms-auto"><iconify-icon icon="solar:info-circle-broken"></iconify-icon> Polygon outline asli paling padat di <b>Cicalengka</b>, <b>Cikancung</b>, <b>Rancaekek</b>, <b>Nagreg</b>, <b>Paseh</b>.</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    Peta Deteksi Bangunan — Kab. Bandung
                    <span class="vt-mode-pill cluster" id="vt-mode-pill" title="Zoom in (≥14) untuk lihat polygon">Cluster</span>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success" id="stat-refreshed" style="font-weight:500;font-size:11px" title="Tanggal aggregate stats terakhir di-refresh">🟢 Update: <span id="stat-refreshed-val">—</span></span>
                </h5>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="fw-semibold">Polygon (zoom ≥14):</span>
                    <span><span class="legend-dot" style="background:#2563eb"></span>Non-Usaha (solid)</span>
                    <span><span class="legend-dot" style="background:#2563eb;border:2px dashed #1e3a8a;width:14px;height:10px;border-radius:2px"></span>Usaha (strip-strip)</span>
                    <span class="text-muted">· solid = PBG confirmed, pudar = predicted</span>
                    <span class="mx-2">│</span>
                    <span><span class="legend-dot" style="background:#0d6efd;border:2px solid #fff;box-shadow:0 0 0 1px #0d6efd"></span>PBG Terbit</span>
                    <span><span class="legend-dot" style="background:#f59e0b;border:2px solid #fff;box-shadow:0 0 0 1px #f59e0b"></span>PBG Proses</span>
                </div>
            </div>
            <div class="card-body p-0"><div id="satellite-map-wrapper"><div id="satellite-map"></div><div id="map-loading"><div class="spinner"></div><span>Memuat data...</span></div><div id="vt-hint" class="vt-hint"><iconify-icon icon="solar:zoom-in-broken" inline></iconify-icon> Zoom dalam ke level 14+ buat lihat outline polygon tiap bangunan</div></div></div>
            <div class="card-footer py-2 small text-muted" id="map-counter">Menampilkan 0 bangunan di area ini.</div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Data per Kecamatan (Kab. Bandung)</h5>
                <div class="d-flex gap-2 align-items-center">
                    <small class="text-muted" id="snapshot-ts"></small>
                    <button class="btn btn-sm btn-outline-primary" id="btn-refresh-stats" title="Hitung ulang dari database (untuk staff PUTR)"><iconify-icon icon="solar:refresh-broken"></iconify-icon> Refresh</button>
                </div>
            </div>
            <div class="card-body"><div class="table-responsive">
                <table class="table table-hover mb-0 align-middle"><thead class="table-light">
                    <tr>
                        <th>Kecamatan</th>
                        <th style="width:55%">Share: <span class="text-danger">● Tanpa Izin</span> · <span class="text-primary">● SK Terbit</span> · <span class="text-warning">● Proses</span></th>
                        <th class="text-end">Total</th>
                    </tr>
                    <tr id="district-total-row" class="table-secondary"><td colspan="3" class="text-muted">—</td></tr>
                </thead>
                <tbody id="district-tbody"><tr><td colspan="3" class="text-center text-muted">Memuat data...</td></tr></tbody></table>
            </div></div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Jenis Bangunan Ber-izin Sah (SK PBG Terbit)</h5></div>
            <div class="card-body"><div class="row" id="function-type-cards"><div class="col-12 text-center text-muted">Memuat data...</div></div></div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card" id="verify-panel" style="display:none">
            <div class="card-header bg-soft-warning"><h5 class="card-title mb-0">Verifikasi Bangunan</h5></div>
            <div class="card-body">
                <p class="mb-1"><strong>ID:</strong> <span id="verify-id"></span></p>
                <p class="mb-1"><strong>Luas:</strong> <span id="verify-area"></span> m²</p>
                <p class="mb-3"><strong>Kecamatan:</strong> <span id="verify-district"></span></p>
                <div class="d-grid gap-2">
                    <button class="btn btn-danger btn-sm verify-btn" data-status="confirmed_illegal">Confirmed Illegal</button>
                    <button class="btn btn-success btn-sm verify-btn" data-status="confirmed_legal">Confirmed Legal</button>
                    <button class="btn btn-warning btn-sm verify-btn" data-status="under_review">Under Review</button>
                    <button class="btn btn-secondary btn-sm verify-btn" data-status="false_positive">False Positive</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
{{-- Phase 9 — Leaflet.VectorGrid (bundled with its protobuf dep). --}}
<script src="https://unpkg.com/leaflet.vectorgrid@1.3.0/dist/Leaflet.VectorGrid.bundled.js"></script>
<script>
    // Phase 9 smoke check — surface a console warning if vectorgrid fails to
    // load (e.g. CDN blocked). Polygon layer activates in Phase 10.
    if (typeof L === 'undefined' || typeof L.vectorGrid === 'undefined') {
        console.warn('[vector-tiles] Leaflet.VectorGrid not loaded — polygon layer will be disabled.');
        window.__vectorGridReady = false;
    } else {
        window.__vectorGridReady = true;
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API = '/api/detected-buildings';
    const TOKEN = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    const H = {'Accept':'application/json','Content-Type':'application/json','Authorization':TOKEN?`Bearer ${TOKEN}`:''};

    // BBOX konsisten dengan backend KECAMATAN_BBOX (spatial-based, bukan dari kolom database name)
    // BBOX full Kab Bandung = union dari 31 polygon (sumber BPS)
    const BS_BOUNDS = [[-7.310, 107.250], [-6.810, 107.940]];
    const DISTRICT_BOUNDS = {
        'Arjasari':     [[-7.095, 107.590], [-7.030, 107.685]],
        'Baleendah':    [[-7.040, 107.590], [-6.970, 107.680]],
        'Banjaran':     [[-7.085, 107.555], [-7.030, 107.620]],
        'Cangkuang':    [[-7.065, 107.515], [-7.000, 107.595]],
        'Cicalengka':   [[-6.998, 107.815], [-6.942, 107.910]],
        'Cikancung':    [[-7.060, 107.778], [-6.985, 107.860]],
        'Cimaung':      [[-7.135, 107.545], [-7.065, 107.620]],
        'Ciparay':      [[-7.090, 107.615], [-6.985, 107.735]],
        'Ciwidey':      [[-7.135, 107.408], [-7.058, 107.475]],
        'Ibun':         [[-7.110, 107.752], [-7.058, 107.775]],
        'Kertasari':    [[-7.210, 107.665], [-7.155, 107.710]],
        'Majalaya':     [[-7.082, 107.740], [-6.995, 107.780]],
        'Nagreg':       [[-7.045, 107.910], [-6.985, 107.985]],
        'Pacet':        [[-7.132, 107.697], [-7.085, 107.746]],
        'Pameungpeuk':  [[-7.030, 107.560], [-6.995, 107.610]],
        'Pangalengan':  [[-7.240, 107.515], [-7.125, 107.635]],
        'Paseh':        [[-7.072, 107.775], [-7.013, 107.795]],
        'Pasirjambu':   [[-7.190, 107.415], [-7.060, 107.500]],
        'Rancabali':    [[-7.200, 107.250], [-7.110, 107.410]],
        'Rancaekek':    [[-6.998, 107.760], [-6.948, 107.840]],
        'Soreang':      [[-7.078, 107.320], [-7.005, 107.555]],
        'Solokanjeruk': [[-7.030, 107.720], [-6.965, 107.775]],
    };
    const DISTRICT_NAMES = Object.keys(DISTRICT_BOUNDS).sort();

    const map = L.map('satellite-map', {
        maxBounds: BS_BOUNDS,
        maxBoundsViscosity: 1.0,
        worldCopyJump: false,
        maxZoom: 21,
    }).setView([-7.05, 107.55], 11);
    // Expose for headless QA scripts (Playwright). No-op in production usage.
    window.map = map;

    // Set minZoom dinamis agar viewport saat zoom-out paling jauh tetap pas di BS_BOUNDS
    // (bukan tile di luar BS yang kelihatan tapi ga bisa di-pan ke sana)
    function clampMinZoom() {
        const fit = map.getBoundsZoom(BS_BOUNDS, true);
        map.setMinZoom(fit);
        if (map.getZoom() < fit) map.setZoom(fit);
    }
    clampMinZoom();
    map.fitBounds(BS_BOUNDS);
    window.addEventListener('resize', clampMinZoom);

    // Satellite imagery. Google Hybrid (lyrs=y = imagery + roads + labels)
    // is the primary source: 4-subdomain CDN, ~250 ms/tile in parallel,
    // and — critically — uses high-resolution imagery at EVERY zoom level,
    // including z=11 max-zoom-out. Esri World Imagery at z<12 falls back
    // to washed-out Landsat ~30 m/px which looks like a cream blank for
    // farmland areas like Kab. Bandung; users mistake that for "no
    // satellite".
    //
    // Esri is added underneath as a soft fallback in case Google blocks
    // an IP; if both fail there's a cartocdn label overlay that still
    // shows place names.
    const esriImagery = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            attribution: 'Esri World Imagery',
            // Esri native imagery caps at 19; allow Leaflet to keep scaling
            // tiles up to map zoom 21 (upscaled imagery, blurrier but useful).
            maxZoom: 21, maxNativeZoom: 19, noWrap: true, keepBuffer: 4,
        }
    ).addTo(map);
    const googleHybrid = L.tileLayer(
        'https://mt{s}.google.com/vt/lyrs=y&hl=id&x={x}&y={y}&z={z}',
        {
            subdomains: ['0','1','2','3'],
            attribution: 'Google Satellite',
            // Google hybrid has native imagery up to z21 in dense urban areas;
            // Kab. Bandung typically z20 max — let Leaflet upscale if needed.
            maxZoom: 21, maxNativeZoom: 21, noWrap: true, keepBuffer: 4,
        }
    ).addTo(map);
    googleHybrid.on('tileerror', e => console.warn('[google tileerror]', e?.coords));
    // No light_only_labels overlay here — Google Hybrid already includes
    // labels (lyrs=y = imagery + roads + labels). Stacking another label
    // layer just doubles place names.

    // Layer polygon kecamatan (GeoJSON dari BPS). Di-load async — tidak blocking render peta.
    const districtLayer = L.layerGroup().addTo(map);
    const kecPolygonByName = {}; // name -> Leaflet Layer
    // Tier 1 filter-gated rendering — choropleth state.
    let kecChoroplethData = {}; // {name: count} dari stats.unmatched_by_district
    let kecPbgData = {};        // {name: {terbit, proses, ditolak}} dari stats.pbg_by_district
    let kecChoroplethMax = 0;
    function choroplethColor(count) {
        if (!count || kecChoroplethMax === 0) return '#ffbb33';
        const pct = count / kecChoroplethMax;
        if (pct > 0.66) return '#dc2626'; // merah pekat (high density)
        if (pct > 0.33) return '#f97316'; // orange
        if (pct > 0.10) return '#fbbf24'; // yellow
        return '#fde68a';                 // amber pucat (low density)
    }
    function fmtId(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function buildKecLabel(name) {
        const tanpa = kecChoroplethData[name] || 0;
        const pbg = kecPbgData[name] || { terbit: 0, proses: 0, ditolak: 0 };
        if (kecChoroplethMax === 0 && tanpa === 0 && !pbg.terbit && !pbg.proses) return name;
        return `<strong>${name}</strong>` +
               `<div class="kc-stat"><span class="kc-dot" style="background:#dc2626"></span>Tanpa izin: <strong>${fmtId(tanpa)}</strong></div>` +
               `<div class="kc-stat"><span class="kc-dot" style="background:#f59e0b"></span>Proses: <strong>${fmtId(pbg.proses)}</strong></div>` +
               `<div class="kc-stat"><span class="kc-dot" style="background:#22c55e"></span>Berizin: <strong>${fmtId(pbg.terbit)}</strong></div>`;
    }
    function applyChoroplethStyle() {
        if (!kecPolygonByName || Object.keys(kecPolygonByName).length === 0) return;
        // Re-compute max in case data changed.
        const counts = Object.values(kecChoroplethData);
        kecChoroplethMax = counts.length ? Math.max(...counts) : 0;
        Object.entries(kecPolygonByName).forEach(([name, layer]) => {
            const c = kecChoroplethData[name] || 0;
            layer.setStyle({
                color: '#ffbb33', weight: 1.2, opacity: 0.85,
                fillColor: choroplethColor(c),
                fillOpacity: kecChoroplethMax > 0 ? 0.35 : 0.05,
                dashArray: '3,3',
            });
            layer.unbindTooltip();
            layer.bindTooltip(buildKecLabel(name), { permanent: true, direction: 'center', className: 'district-label' });
        });
    }
    // Semua 31 kecamatan Kab Bandung
    const KAB_BANDUNG = new Set(['Arjasari','Baleendah','Banjaran','Bojongsoang','Cangkuang','Cicalengka','Cikancung','Cilengkrang','Cileunyi','Cimaung','Cimenyan','Ciparay','Ciwidey','Dayeuhkolot','Ibun','Katapang','Kertasari','Kutawaringin','Majalaya','Margaasih','Margahayu','Nagreg','Pacet','Pameungpeuk','Pangalengan','Paseh','Pasirjambu','Rancabali','Rancaekek','Soreang','Solokanjeruk']);
    fetch('/data/kecamatan_kab_bandung.geojson')
      .then(r => r.json())
      .then(gj => {
        L.geoJSON(gj, {
            filter: f => KAB_BANDUNG.has(f.properties.name),
            style: { color: '#ffbb33', weight: 1.2, opacity: 0.85, fillColor: '#ffbb33', fillOpacity: 0.05, dashArray: '3,3' },
            onEachFeature: (feature, layer) => {
                const name = feature.properties.name;
                kecPolygonByName[name] = layer;
                layer.bindTooltip(name, { permanent: true, direction: 'center', className: 'district-label' });
                layer.on('click', () => {
                    distSel.value = name;
                    map.fitBounds(layer.getBounds());
                    renderDesaForCurrentFilter();
                    applyFilters();
                });
            }
        }).addTo(districtLayer);
        // If stats already loaded before polygons, paint immediately.
        applyChoroplethStyle();
    })
    .catch(e => console.error('[kecamatan geojson]', e));

    // ======================================================================
    // Tier 1 — Desa drill-down: tampilkan polygon kelurahan/desa hanya saat
    // user pilih satu kecamatan. Outline saja (per-desa stats butuh backend
    // enrichment, not in this tier).
    // ======================================================================
    const desaLayer = L.layerGroup().addTo(map);
    const desaByKecamatan = {}; // {KECAMATAN_UPPER: [features]}
    let desaLoaded = false;
    fetch('/data/kelurahan_kab_bandung.geojson')
      .then(r => r.json())
      .then(gj => {
        (gj.features || []).forEach(f => {
            const kec = (f.properties?.kecamatan || '').toUpperCase();
            if (!kec) return;
            (desaByKecamatan[kec] = desaByKecamatan[kec] || []).push(f);
        });
        desaLoaded = true;
        renderDesaForCurrentFilter();
    })
    .catch(e => console.error('[desa geojson]', e));

    // Lookup: desa name → feature (untuk auto-fit & highlight)
    const desaFeatureByName = {}; // {"KECUPPER||DESA_NAME": feature}

    function renderDesaForCurrentFilter() {
        desaLayer.clearLayers();
        if (!desaLoaded) return;
        const distVal = document.getElementById('filter-district')?.value || '';
        const desaVal = document.getElementById('filter-desa')?.value || '';
        if (!distVal) return;
        const features = desaByKecamatan[distVal.toUpperCase()] || [];
        if (features.length === 0) return;
        L.geoJSON({ type: 'FeatureCollection', features }, {
            style: (feature) => {
                const name = (feature.properties?.raw_name || feature.properties?.name || '').toUpperCase();
                const isSelected = desaVal && name === desaVal.toUpperCase();
                return isSelected
                    ? { color: '#dc2626', weight: 3, opacity: 1, fillColor: '#ef4444', fillOpacity: 0.18, dashArray: null }
                    : { color: '#2563eb', weight: 1, opacity: 0.85, fillColor: '#3b82f6', fillOpacity: 0.08, dashArray: '2,3' };
            },
            onEachFeature: (feature, layer) => {
                const raw = feature.properties?.raw_name || feature.properties?.name || '';
                layer.bindTooltip(raw, { permanent: true, direction: 'center', className: 'desa-label' });
            }
        }).addTo(desaLayer);
    }

    // Populate desa dropdown saat kecamatan berubah
    function populateDesaDropdown(kecValue) {
        const desaSel = document.getElementById('filter-desa');
        desaSel.innerHTML = '';
        if (!kecValue || !desaLoaded) {
            desaSel.disabled = true;
            desaSel.appendChild(new Option('Pilih kecamatan dulu', ''));
            return;
        }
        const features = desaByKecamatan[kecValue.toUpperCase()] || [];
        if (features.length === 0) {
            desaSel.disabled = true;
            desaSel.appendChild(new Option('Data desa belum tersedia', ''));
            return;
        }
        desaSel.disabled = false;
        desaSel.appendChild(new Option('Semua Desa', ''));
        const names = features.map(f => f.properties?.raw_name || f.properties?.name || '').filter(Boolean).sort();
        names.forEach(n => {
            desaSel.appendChild(new Option(n, n));
            desaFeatureByName[`${kecValue.toUpperCase()}||${n.toUpperCase()}`] = features.find(f => (f.properties?.raw_name || f.properties?.name) === n);
        });
    }

    const markers = L.markerClusterGroup({maxClusterRadius:50,showCoverageOnHover:false,
        iconCreateFunction:c=>{const n=c.getChildCount();const d=n>100?50:n>10?40:30;
        return L.divIcon({html:`<div style="background:rgba(239,68,68,0.8);color:#fff;border-radius:50%;width:${d}px;height:${d}px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3)">${n>999?Math.round(n/1000)+'k':n}</div>`,className:'mc',iconSize:L.point(d,d)});}
    });
    // Cluster markers replaced by choropleth + per-kecamatan tooltip (2026-05-19).
    // Layer kept alive (other code still references markers.*) but never
    // attached to the map.
    // map.addLayer(markers);

    // Layer PBG tasks (dari pbg_task_details) — cluster sama kaya satelit biar konsisten
    const pbgLayer = L.markerClusterGroup({
        maxClusterRadius: 50,
        showCoverageOnHover: false,
        iconCreateFunction: c => {
            const n = c.getChildCount();
            const d = n > 100 ? 44 : n > 10 ? 36 : 28;
            return L.divIcon({
                html: `<div style="background:rgba(13,110,253,0.85);color:#fff;border-radius:50%;width:${d}px;height:${d}px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:11px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3)">${n > 999 ? Math.round(n/1000) + 'k' : n}</div>`,
                className: 'mc-pbg',
                iconSize: L.point(d, d)
            });
        }
    });
    // PBG cluster also retired in favour of the choropleth approach.
    // map.addLayer(pbgLayer);
    const PBG_COLORS = {terbit: '#0d6efd', proses: '#f59e0b', ditolak: '#6b7280'};

    // ======================================================================
    // Phase 10 — polygon footprint layer.
    // Reads vector tiles from /api/tiles/buildings/{z}/{x}/{y}.pbf (Phase 8
    // proxy → Martin → PostGIS function). Activates only above zoom 14;
    // below that the existing cluster layer is the right level of detail.
    // The zoom-driven layer-switch logic (hide clusters at z>=14, show
    // polygons; reverse below) lands in Phase 11.
    // ======================================================================
    const VECTOR_TILES_MIN_ZOOM = 14;
    // Phase 16 — gate the polygon layer on PBB clearance. level_1 ('user')
    // gets only the cluster view because polygon footprints reveal
    // individual buildings (PII concern under UU PDP). Backend already
    // enforces this with pbb.clearance:level_2; this frontend gate stops
    // us from rendering the mode pill / firing 403 requests for users
    // who'd never see polygons anyway.
    const USER_CLEARANCE = document.querySelector('meta[name="user-clearance"]')?.getAttribute('content') || 'level_1';
    const CLEARANCE_RANK = { level_1: 1, level_2: 2, level_3: 3 };
    const POLYGON_ALLOWED = (CLEARANCE_RANK[USER_CLEARANCE] || 0) >= 2;
    let polygonLayer = null;
    let _highlightedId = null;

    // Build the tile-URL template with filter query string. Only the filters
    // that the PostGIS tile function understands (Phase 7) are forwarded:
    // district + min_area. filter-data-source and filter-pbg-status carry
    // semantics the tile function does not yet model (usage category vs.
    // PBG match-status); they remain stats-only filters for now.
    function buildPolygonTileUrl() {
        const params = new URLSearchParams();
        const dist = document.getElementById('filter-district')?.value;
        const area = document.getElementById('filter-min-area')?.value;
        const pbg  = document.getElementById('filter-pbg-status')?.value;
        const cat  = document.getElementById('filter-usage-category')?.value;
        // 2026-05-19: Microsoft polygons turned out to be uniform rectangles
        // ("kotak approx") — useless for visual overlay. Exclude them from
        // the tile layer. OSM (hand-drawn, ~85k) + Google Open Buildings
        // (some real outlines) carry the visible polygons. Microsoft is
        // still used for the kecamatan_stats density counter — counts are
        // reasonable even though shapes aren't.
        params.set('exclude_source', 'microsoft_footprints');
        if (dist) params.set('district', dist);
        if (area) params.set('min_area', area);
        if (pbg) params.set('permit_state', pbg);
        if (cat) params.set('usage_category', cat);
        return '/api/tiles/buildings/{z}/{x}/{y}.pbf?' + params.toString();
    }

    function createPolygonLayer() {
        const layer = L.vectorGrid.protobuf(buildPolygonTileUrl(), {
            rendererFactory: L.canvas.tile,
            interactive: true,
            getFeatureId: f => f.properties.id,
            minZoom: VECTOR_TILES_MIN_ZOOM,
            // Vector tiles served natively up to z18; Leaflet upscales to z21.
            maxZoom: 21,
            maxNativeZoom: 18,
            bounds: BS_BOUNDS,
            attribution: 'Building footprints (Sibedas + Google Open Buildings + OSM)',
            fetchOptions: {
                credentials: 'same-origin',
                headers: TOKEN ? { 'Authorization': `Bearer ${TOKEN}` } : {},
            },
            vectorTileLayerStyles: {
                // Semua polygon biru. Usaha dibedain dengan strip-strip
                // (dashed border tebal); non-usaha solid. Confirmed PBG match
                // = opacity penuh, predicted = opacity setengah.
                buildings: (props) => {
                    const isUsaha = props.usage_category === 'usaha';
                    const isConfirmed = props.category_confidence === 'confirmed';
                    return {
                        fill: true,
                        fillColor: '#2563eb',
                        fillOpacity: isConfirmed ? 0.55 : 0.3,
                        color: isUsaha ? '#1e3a8a' : '#1d4ed8',
                        weight: isUsaha ? 2 : 1,
                        opacity: isConfirmed ? 1 : 0.7,
                        dashArray: isUsaha ? '3,3' : null,
                    };
                },
            },
        });
        layer.on('tileerror', e => console.warn('[polygon-tile] error loading tile', e?.coords));

        // Phase 12 — click a polygon → open the existing verify panel.
        layer.on('click', async function (e) {
            const props = e?.layer?.properties || e?.propagatedFrom?.properties;
            if (!props || !props.id) return;
            const id = props.id;

            if (_highlightedId && _highlightedId !== id) {
                try { layer.resetFeatureStyle(_highlightedId); } catch (_) {}
            }
            layer.setFeatureStyle(id, {
                fill: true,
                fillColor: props.status_color || '#ef4444',
                fillOpacity: 0.85,
                color: '#0d6efd',
                weight: 2.5,
                opacity: 1,
            });
            _highlightedId = id;

            selId = id;
            document.getElementById('verify-id').textContent = '#' + id;
            document.getElementById('verify-area').textContent = '...';
            document.getElementById('verify-district').textContent = '...';
            document.getElementById('verify-panel').style.display = 'block';

            try {
                const r = await fetch(`${API}/${id}`, { headers: H });
                if (!r.ok) throw new Error(`detail HTTP ${r.status}`);
                const detail = await r.json();
                document.getElementById('verify-area').textContent =
                    detail.estimated_area_m2 != null ? Number(detail.estimated_area_m2).toLocaleString('id-ID') : '—';
                document.getElementById('verify-district').textContent =
                    detail.building_district_name || detail.district || '—';
            } catch (err) {
                console.warn('[polygon-click] fetch detail failed', err);
                document.getElementById('verify-area').textContent = '—';
                document.getElementById('verify-district').textContent = '—';
            }
        });
        return layer;
    }

    if (!POLYGON_ALLOWED) {
        // Hide the cluster ↔ polygon mode pill — it's misleading for a user
        // who'll never see polygons. Counter footer stays as-is.
        document.getElementById('vt-mode-pill')?.remove();
        console.info('[vector-tiles] User clearance ' + USER_CLEARANCE + ' — polygon layer hidden, cluster view only.');
    } else if (typeof L.vectorGrid !== 'undefined' && window.__vectorGridReady !== false) {
        polygonLayer = createPolygonLayer();
        polygonLayer.addTo(map);
        window.__sibedas_polygonLayer = polygonLayer;
    } else {
        console.warn('[vector-tiles] L.vectorGrid unavailable — polygon layer disabled.');
    }

    // Phase 13 — recreate the polygon layer when district / min_area change.
    // VectorGrid stores its URL template at construction time, so swapping
    // filters means tearing down the layer and rebuilding with a new URL.
    // Debounced 300 ms so flipping through dropdowns doesn't fire bursts of
    // tile requests.
    let _polygonRefreshTimer = null;
    // Phase 2 — show spinner during polygon-layer rebuild so user knows the
    // map is reloading after a filter change (especially when switching to
    // a dense kecamatan like Baleendah where the tile fetch takes ~1-2s).
    const _mapLoadingEl = document.getElementById('map-loading');
    let _pendingTiles = 0;
    function _setLoading(on) {
        if (!_mapLoadingEl) return;
        _mapLoadingEl.classList.toggle('active', !!on);
    }
    function refreshPolygonLayer() {
        if (!POLYGON_ALLOWED) return;
        if (!L || !L.vectorGrid || window.__vectorGridReady === false) return;
        clearTimeout(_polygonRefreshTimer);
        _setLoading(true);
        _polygonRefreshTimer = setTimeout(() => {
            const wasOnMap = polygonLayer && map.hasLayer(polygonLayer);
            if (polygonLayer) {
                try { map.removeLayer(polygonLayer); } catch (_) {}
            }
            _highlightedId = null;
            _pendingTiles = 0;
            polygonLayer = createPolygonLayer();
            // Track tile-load lifecycle so we can hide the spinner only once
            // every requested tile has either returned or failed.
            polygonLayer.on('tileloadstart', () => { _pendingTiles++; _setLoading(true); });
            const _done = () => { _pendingTiles = Math.max(0, _pendingTiles - 1); if (_pendingTiles === 0) _setLoading(false); };
            polygonLayer.on('tileload', _done);
            polygonLayer.on('tileerror', _done);
            window.__sibedas_polygonLayer = polygonLayer;
            if (wasOnMap || _vtMode === 'polygon') polygonLayer.addTo(map);
            // Safety net: hide spinner after 8s no matter what (in case events misfire)
            setTimeout(() => { if (_pendingTiles === 0) _setLoading(false); }, 8000);
        }, 300);
    }
    ['filter-district', 'filter-desa', 'filter-min-area', 'filter-pbg-status', 'filter-usage-category'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', refreshPolygonLayer);
    });
    // ======================================================================

    let selId = null;
    const colors = {unverified:'#ef4444',confirmed_illegal:'#dc2626',confirmed_legal:'#22c55e',under_review:'#f59e0b',false_positive:'#6b7280'};
    const FUNCTION_LABELS = {hunian:'Hunian / Tempat Tinggal', usaha:'Usaha / UMKM', sosial:'Sosial Budaya', prasarana:'Prasarana', ibadah:'Ibadah / Keagamaan', pendidikan:'Pendidikan / Kebudayaan', multifungsi:'Multifungsi'};

    // Populate district dropdown
    const distSel = document.getElementById('filter-district');
    DISTRICT_NAMES.forEach(n => { const o = document.createElement('option'); o.value = n; o.textContent = n; distSel.appendChild(o); });

    // When district changes, auto fitBounds + reload stats. Map reload triggered via moveend.
    distSel.addEventListener('change', function() {
        if (!this.value) { map.fitBounds(BS_BOUNDS); }
        else {
            const poly = kecPolygonByName[this.value];
            if (poly) map.fitBounds(poly.getBounds());
            else if (DISTRICT_BOUNDS[this.value]) map.fitBounds(DISTRICT_BOUNDS[this.value]);
        }
        populateDesaDropdown(this.value);
        renderDesaForCurrentFilter();
        loadStats();
    });

    // Desa change → fitBounds ke polygon desa + re-render highlight
    const desaSel = document.getElementById('filter-desa');
    desaSel.addEventListener('change', function() {
        const kec = document.getElementById('filter-district')?.value || '';
        if (this.value && kec) {
            const feat = desaFeatureByName[`${kec.toUpperCase()}||${this.value.toUpperCase()}`];
            if (feat) {
                const layer = L.geoJSON(feat);
                map.fitBounds(layer.getBounds(), { padding: [20, 20] });
            }
        } else if (kec) {
            // Desa di-clear: zoom out ke kecamatan
            const poly = kecPolygonByName[kec];
            if (poly) map.fitBounds(poly.getBounds());
            else if (DISTRICT_BOUNDS[kec]) map.fitBounds(DISTRICT_BOUNDS[kec]);
        }
        renderDesaForCurrentFilter();
    });

    // Init desa dropdown once desa geojson loaded (in case kecamatan was pre-selected)
    const _desaWatcher = setInterval(() => {
        if (desaLoaded) {
            clearInterval(_desaWatcher);
            const initKec = distSel.value;
            if (initKec) populateDesaDropdown(initKec);
        }
    }, 200);

    // Konversi pilihan dropdown jadi param API. Value yg diawali `ft_` artinya filter
    // PBG function_type (sub-jenis bangunan), selain itu adalah data_source (sumber Luar Sistem).
    function dataSourceParams(rawValue) {
        if (!rawValue) return {};
        if (rawValue.startsWith('ft_')) return { function_type: rawValue.substring(3) };
        return { data_source: rawValue };
    }

    async function loadStats() {
        try {
            const p = new URLSearchParams();
            const area = document.getElementById('filter-min-area').value;
            const dsrc = document.getElementById('filter-data-source').value;
            const pbgStatus = document.getElementById('filter-pbg-status').value;
            if (area) p.set('min_area', area);
            Object.entries(dataSourceParams(dsrc)).forEach(([k, v]) => p.set(k, v));
            if (pbgStatus) p.set('pbg_status', pbgStatus);
            const qs = p.toString() ? `?${p}` : '';
            const r = await fetch(`${API}/stats${qs}`, {headers: H});
            if (!r.ok) throw new Error(`stats HTTP ${r.status}`);
            const d = await r.json();
            const fmt = n => Number(n || 0).toLocaleString('id-ID');
            document.getElementById('stat-total').textContent = fmt(d.total_detected);
            document.getElementById('stat-permit-valid').textContent = fmt(d.permit_valid);
            document.getElementById('stat-without-permit').textContent = fmt(d.without_permit);
            document.getElementById('stat-rate').textContent = (d.permit_rate ?? 0) + '%';
            const br = `${fmt(d.unmatched)} tak match · ${fmt(d.match_orphan)} orphan · ${fmt(d.permit_rejected)} ditolak`;
            const el = document.getElementById('stat-without-breakdown');
            if (el) el.textContent = br;

            const ts = d.snapshot_refreshed_at;
            const tsEl = document.getElementById('snapshot-ts');
            if (tsEl && ts) {
                const t = new Date(ts);
                tsEl.textContent = `Snapshot: ${t.toLocaleString('id-ID')}`;
            } else if (tsEl) {
                tsEl.textContent = '';
            }
            // Phase 1 — update badge "Update terakhir" di map header
            const refreshedVal = document.getElementById('stat-refreshed-val');
            if (refreshedVal) {
                refreshedVal.textContent = ts
                    ? new Date(ts).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                    : '—';
            }

            // Tier 1 — feed choropleth from stats (raw unmatched, before PBG-status mask).
            kecChoroplethData = d.unmatched_by_district || {};
            kecPbgData = d.pbg_by_district || {};
            applyChoroplethStyle();

            if (d.unmatched_by_district) {
                const tb = document.getElementById('district-tbody');
                const tf = document.getElementById('district-total-row');
                // Mask per-segment data based on Status PBG filter so 100% stacked bar
                // reflects exactly apa yang user pilih (otherwise pbg_by_district yang
                // datang dari pbg_task_details unfiltered nampilin segmen yang harusnya
                // di-hide).
                const pbgStatus = document.getElementById('filter-pbg-status').value;
                const unmatched = {};
                const pbg = {};
                Object.entries(d.unmatched_by_district).forEach(([k, v]) => {
                    unmatched[k] = (pbgStatus === '' || pbgStatus === 'luar_sistem') ? v : 0;
                });
                Object.entries(d.pbg_by_district || {}).forEach(([k, p]) => {
                    pbg[k] = {
                        terbit:  (pbgStatus === '' || pbgStatus === 'terbit')  ? (p.terbit  || 0) : 0,
                        proses:  (pbgStatus === '' || pbgStatus === 'proses')  ? (p.proses  || 0) : 0,
                        ditolak: (pbgStatus === '' || pbgStatus === 'ditolak') ? (p.ditolak || 0) : 0,
                    };
                });
                const allKc = Array.from(new Set([...Object.keys(unmatched), ...Object.keys(pbg)]));
                if (allKc.length === 0) {
                    tb.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>';
                    if (tf) tf.innerHTML = '<td colspan="3" class="text-muted">—</td>';
                } else {
                    const rows = allKc.map(n => {
                        const tanpaIzin = unmatched[n] || 0;
                        const p = pbg[n] || {terbit:0, proses:0, ditolak:0};
                        const terbit = p.terbit || 0;
                        const proses = p.proses || 0;
                        // Total = tanpa izin (deteksi satelit) + pbg terbit + pbg proses (ditolak nggak masuk hitung "aktif")
                        const total = tanpaIzin + terbit + proses;
                        return {n, tanpaIzin, terbit, proses, total};
                    }).sort((a,b) => b.total - a.total);
                    // Grand totals
                    const gTanpa = rows.reduce((s, r) => s + r.tanpaIzin, 0);
                    const gTerbit = rows.reduce((s, r) => s + r.terbit, 0);
                    const gProses = rows.reduce((s, r) => s + r.proses, 0);
                    const gTotal = gTanpa + gTerbit + gProses;
                    tb.innerHTML = rows.map(r => {
                        const pctTanpa = r.total ? (r.tanpaIzin / r.total * 100) : 0;
                        const pctTerbit = r.total ? (r.terbit / r.total * 100) : 0;
                        const pctProses = r.total ? (r.proses / r.total * 100) : 0;
                        const seg = (pct, bg, title, count) => pct > 0 ? `<div class="progress-bar ${bg}" style="width:${pct.toFixed(2)}%" title="${title}: ${count.toLocaleString('id-ID')} (${pct.toFixed(1)}%)"></div>` : '';
                        return `<tr class="district-row" data-d="${r.n}" style="cursor:pointer">
                            <td><b>${r.n}</b></td>
                            <td>
                                <div class="progress position-relative" style="height:22px">
                                    ${seg(pctTanpa, 'bg-danger', 'Tanpa Izin', r.tanpaIzin)}
                                    ${seg(pctTerbit, 'bg-primary', 'SK Terbit', r.terbit)}
                                    ${seg(pctProses, 'bg-warning', 'Proses', r.proses)}
                                    <small class="position-absolute top-50 start-50 translate-middle fw-bold text-dark" style="text-shadow:0 0 2px #fff,0 0 2px #fff,0 0 2px #fff">${r.total.toLocaleString('id-ID')}</small>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span>🔴 ${r.tanpaIzin.toLocaleString('id-ID')}</span>
                                    <span>🔵 ${r.terbit.toLocaleString('id-ID')}</span>
                                    <span>🟡 ${r.proses.toLocaleString('id-ID')}</span>
                                </div>
                            </td>
                            <td class="text-end fw-semibold">${r.total.toLocaleString('id-ID')}</td>
                        </tr>`;
                    }).join('');
                    if (tf) {
                        const pctTanpa = gTotal ? (gTanpa / gTotal * 100) : 0;
                        const pctTerbit = gTotal ? (gTerbit / gTotal * 100) : 0;
                        const pctProses = gTotal ? (gProses / gTotal * 100) : 0;
                        const seg = (pct, bg) => pct > 0 ? `<div class="progress-bar ${bg}" style="width:${pct.toFixed(2)}%"></div>` : '';
                        tf.innerHTML = `
                            <td class="fw-bold">TOTAL BS</td>
                            <td>
                                <div class="progress position-relative" style="height:22px">
                                    ${seg(pctTanpa, 'bg-danger')}${seg(pctTerbit, 'bg-primary')}${seg(pctProses, 'bg-warning')}
                                    <small class="position-absolute top-50 start-50 translate-middle fw-bold text-dark" style="text-shadow:0 0 2px #fff,0 0 2px #fff,0 0 2px #fff">${gTotal.toLocaleString('id-ID')}</small>
                                </div>
                                <div class="d-flex justify-content-between small fw-semibold mt-1">
                                    <span class="text-danger">🔴 ${gTanpa.toLocaleString('id-ID')}</span>
                                    <span class="text-primary">🔵 ${gTerbit.toLocaleString('id-ID')}</span>
                                    <span class="text-warning">🟡 ${gProses.toLocaleString('id-ID')}</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold fs-6">${gTotal.toLocaleString('id-ID')}</td>`;
                    }
                    tb.querySelectorAll('.district-row').forEach(r => r.addEventListener('click', () => {
                        const d = r.dataset.d;
                        distSel.value = d;
                        const poly = kecPolygonByName[d];
                        if (poly) map.fitBounds(poly.getBounds());
                        else if (DISTRICT_BOUNDS[d]) map.fitBounds(DISTRICT_BOUNDS[d]);
                        applyFilters();
                    }));
                }
            }

            if (d.pbg_by_status_category) {
                const p = d.pbg_by_status_category;
                document.getElementById('pbg-total').textContent = Number(d.pbg_total || 0).toLocaleString('id-ID');
                document.getElementById('pbg-terbit').textContent = Number(p.terbit || 0).toLocaleString('id-ID');
                document.getElementById('pbg-proses').textContent = Number(p.proses || 0).toLocaleString('id-ID');
                document.getElementById('pbg-ditolak').textContent = Number(p.ditolak || 0).toLocaleString('id-ID');
            }

            if (d.by_function_type) {
                const host = document.getElementById('function-type-cards');
                const entries = Object.entries(d.by_function_type);
                const total = entries.reduce((s, [, v]) => s + v, 0);
                if (total === 0) {
                    host.innerHTML = '<div class="col-12 text-center text-muted">Belum ada bangunan ber-izin PBG yang ter-match</div>';
                } else {
                    host.innerHTML = entries.map(([k, v]) => `
                        <div class="col-md-6 col-lg-4 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                <span>${FUNCTION_LABELS[k] || k}</span>
                                <span class="badge bg-primary">${Number(v).toLocaleString('id-ID')}</span>
                            </div>
                        </div>`).join('');
                }
            }
        } catch (e) { console.error('[stats]', e); }
    }

    const loadingEl = document.getElementById('map-loading');
    function setLoading(on) { loadingEl.classList.toggle('active', on); }

    let mapAbort = null;
    let loadSeq = 0;

    function readFilters() {
        // pbgStatus = 'terbit' | 'proses' | 'ditolak' | 'luar_sistem' | ''
        // 'luar_sistem' = ex-"Tanpa Izin Sah" yg dulu di filter-permit (unmatched + orphan + rejected)
        return {
            dist: document.getElementById('filter-district').value,
            pbgStatus: document.getElementById('filter-pbg-status').value,
            area: document.getElementById('filter-min-area').value,
            dsrc: document.getElementById('filter-data-source').value,
            showSat: document.getElementById('toggle-sat-layer').checked,
            showPbg: document.getElementById('toggle-pbg-layer').checked,
        };
    }

    let lastShown = {sat: 0, pbg: 0, limit: 0};
    // Rank 1 optimization: at low zoom (≤13), fetch pre-aggregated clusters
    // from the server instead of up to 5K individual markers. Reduces payload
    // ~100× and skips client-side MarkerCluster entirely.
    const CLUSTER_MAX_ZOOM = 13;
    async function fetchSatelliteClusters(b, z, f, signal) {
        const useBbox = f.dist
            ? {sw: BS_BOUNDS[0], ne: BS_BOUNDS[1]}
            : {sw: [b.getSouth(), b.getWest()], ne: [b.getNorth(), b.getEast()]};
        const p = new URLSearchParams({zoom: String(z), sw_lat: useBbox.sw[0], sw_lng: useBbox.sw[1], ne_lat: useBbox.ne[0], ne_lng: useBbox.ne[1]});
        if (f.dist) p.set('district', f.dist);
        if (f.area) p.set('min_area', f.area);
        Object.entries(dataSourceParams(f.dsrc)).forEach(([k, v]) => p.set(k, v));

        const r = await fetch(`${API}/clusters?${p}`, {headers: H, signal});
        if (!r.ok) throw new Error(`clusters HTTP ${r.status}`);
        const gj = await r.json();
        markers.clearLayers();
        if (!gj.features) return;
        const STATE_COLOR = {terbit:'#22c55e', proses:'#f59e0b', ditolak:'#6b7280', tanpa_izin:'#ef4444'};
        gj.features.forEach(ft => {
            const [lng, lat] = ft.geometry.coordinates;
            const pr = ft.properties;
            const cnt = pr.count || 1;
            const color = STATE_COLOR[pr.permit_state] || '#ef4444';
            const d = cnt > 500 ? 44 : cnt > 100 ? 36 : cnt > 20 ? 28 : 22;
            const icon = L.divIcon({
                html: `<div style="background:${color};color:#fff;border-radius:50%;width:${d}px;height:${d}px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:11px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);opacity:0.9">${cnt > 999 ? Math.round(cnt/1000) + 'k' : cnt}</div>`,
                className: 'mc-srv', iconSize: L.point(d, d)
            });
            const m = L.marker([lat, lng], {icon});
            m.bindPopup(`<b>Cluster: ${cnt.toLocaleString('id-ID')} bangunan</b><br>Status: ${pr.permit_state}<br>Rata-rata luas: ${pr.area_avg_m2 || '-'} m²<br><small>Zoom in untuk lihat individual</small>`);
            markers.addLayer(m);
        });
        lastShown.sat = gj.features.length;
    }
    async function fetchSatellite(b, z, f, signal) {
        // 2026-05-19: cluster pins di-retire. Detail bangunan sekarang lewat
        // polygon layer di z14+; per-kecamatan totals lewat choropleth tooltip.
        markers.clearLayers();
        lastShown.sat = 0;
        return;
        // eslint-disable-next-line no-unreachable -- old cluster path kept for ref
        if (!f.showSat) { markers.clearLayers(); lastShown.sat = 0; return; }
        if (!f.dist && !f.pbgStatus && z <= CLUSTER_MAX_ZOOM) {
            markers.clearLayers();
            lastShown.sat = 0;
            return;
        }
        // Server-side cluster mode for low zoom — skip individual marker fetch.
        if (z <= CLUSTER_MAX_ZOOM && !f.pbgStatus) {
            return fetchSatelliteClusters(b, z, f, signal);
        }
        // Limit: district filter aktif = ambil lebih banyak (karena pool-nya udah kecil).
        const limit = f.dist ? 10000 : (z <= 11 ? 800 : z <= 13 ? 1500 : z <= 15 ? 3000 : 5000);
        lastShown.limit = limit;
        // Kalau filter kecamatan aktif, pakai BS_BOUNDS (luas) biar semua bangunan di kecamatan itu ke-load
        // meski user zoom ke level yg viewport-nya lebih kecil dari area kecamatan.
        const useBbox = f.dist
            ? {sw: BS_BOUNDS[0], ne: BS_BOUNDS[1]}
            : {sw: [b.getSouth(), b.getWest()], ne: [b.getNorth(), b.getEast()]};
        const p = new URLSearchParams({sw_lat: useBbox.sw[0], sw_lng: useBbox.sw[1], ne_lat: useBbox.ne[0], ne_lng: useBbox.ne[1], limit: String(limit)});
        if (f.dist) p.set('district', f.dist);
        if (f.area) p.set('min_area', f.area);
        // Status PBG → backend params
        // 'luar_sistem' (Tanpa Izin Sah) = unmatched_only=1 (ex-filter-permit)
        // 'terbit' / 'proses' / 'ditolak' (Dalam Sistem) = pbg_status=<value>
        if (f.pbgStatus === 'luar_sistem') p.set('unmatched_only', '1');
        else if (f.pbgStatus) p.set('pbg_status', f.pbgStatus);
        Object.entries(dataSourceParams(f.dsrc)).forEach(([k, v]) => p.set(k, v));

        const r = await fetch(`${API}/geojson?${p}`, {headers: H, signal});
        if (!r.ok) throw new Error(`geojson HTTP ${r.status}`);
        const gj = await r.json();
        markers.clearLayers();
        if (!gj.features) return;
        const STATE_COLOR = {terbit:'#22c55e', proses:'#f59e0b', ditolak:'#6b7280', tanpa_izin:'#ef4444'};
        const STATE_LABEL = {terbit:'Berizin Sah', proses:'PBG Proses', ditolak:'PBG Ditolak', tanpa_izin:'Tanpa Izin'};
        const STATE_BADGE = {terbit:'bg-success', proses:'bg-warning', ditolak:'bg-secondary', tanpa_izin:'bg-danger'};
        gj.features.forEach(ft => {
            const [lng, lat] = ft.geometry.coordinates;
            const pr = ft.properties;
            const state = pr.permit_state || 'tanpa_izin';
            const color = STATE_COLOR[state];
            const m = L.circleMarker([lat, lng], {radius: Math.min(4 + (pr.area_m2 || 0) / 200, 12), fillColor: color, color: '#fff', weight: 1, fillOpacity: 0.8});
            const typeLine = pr.function_type ? `<br>Jenis: <span class="badge bg-info">${pr.function_type}</span>` : '';
            const ownerLine = pr.owner_name ? `<br>Pemilik: ${pr.owner_name}` : '';
            const regLine = pr.registration_number ? `<br>No. Reg: ${pr.registration_number}` : '';
            const pbgStatusLine = pr.pbg_status_name ? `<br>Status PBG: ${pr.pbg_status_name}` : '';
            m.bindPopup(`<b>Satelit #${pr.id}</b><br>Luas: ${pr.area_m2 ? Number(pr.area_m2).toLocaleString('id-ID') + ' m²' : 'N/A'}<br>Status: <span class="badge ${STATE_BADGE[state]}">${STATE_LABEL[state]}</span>${pbgStatusLine}${typeLine}<br>Kecamatan: ${pr.district || '-'}${ownerLine}${regLine}<br><button class="btn btn-sm btn-outline-primary mt-1 w-100 bv" data-id="${pr.id}" data-area="${pr.area_m2 || 0}" data-d="${pr.district || ''}">Verifikasi</button>`);
            m.on('popupopen', () => document.querySelectorAll('.bv').forEach(btn => btn.addEventListener('click', function() {
                selId = this.dataset.id;
                document.getElementById('verify-id').textContent = '#' + this.dataset.id;
                document.getElementById('verify-area').textContent = Number(this.dataset.area).toLocaleString('id-ID');
                document.getElementById('verify-district').textContent = this.dataset.d;
                document.getElementById('verify-panel').style.display = 'block';
            })));
            markers.addLayer(m);
        });
        lastShown.sat = gj.features.length;
    }

    async function fetchPbg(b, f, signal) {
        // 2026-05-19: PBG cluster pins di-retire bareng cluster satelit.
        // Detail PBG sekarang lewat polygon click (di-link ke verify panel).
        pbgLayer.clearLayers();
        lastShown.pbg = 0;
        return;
        // eslint-disable-next-line no-unreachable -- old PBG path kept for ref
        if (!f.showPbg) return;
        const z = map.getZoom();
        if (!f.dist && !f.pbgStatus && z <= CLUSTER_MAX_ZOOM) {
            lastShown.pbg = 0;
            return;
        }
        // Status "Luar Sistem" = bangunan tanpa PBG record. PBG layer (titik-titik dari
        // database PBG) inherently nggak punya entri buat ini — skip fetch biar nggak
        // tampilin titik yg keliru.
        if (f.pbgStatus === 'luar_sistem') return;
        const useBbox = f.dist
            ? {sw: BS_BOUNDS[0], ne: BS_BOUNDS[1]}
            : {sw: [b.getSouth(), b.getWest()], ne: [b.getNorth(), b.getEast()]};
        const p = new URLSearchParams({sw_lat: useBbox.sw[0], sw_lng: useBbox.sw[1], ne_lat: useBbox.ne[0], ne_lng: useBbox.ne[1], limit: '3000'});
        if (f.dist) p.set('district', f.dist);
        if (f.pbgStatus) p.set('pbg_status', f.pbgStatus);
        Object.entries(dataSourceParams(f.dsrc)).forEach(([k, v]) => p.set(k, v));

        const r = await fetch(`${API}/pbg-geojson?${p}`, {headers: H, signal});
        if (!r.ok) throw new Error(`pbg-geojson HTTP ${r.status}`);
        const gj = await r.json();
        if (!gj.features) return;
        gj.features.forEach(ft => {
            const [lng, lat] = ft.geometry.coordinates;
            const pr = ft.properties;
            const color = PBG_COLORS[pr.category] || '#0d6efd';
            const m = L.circleMarker([lat, lng], {radius: 6, fillColor: color, color: '#fff', weight: 2, fillOpacity: 0.95});
            const areaLine = pr.total_area ? `<br>Luas: ${Number(pr.total_area).toLocaleString('id-ID')} m²` : '';
            const fnLine = pr.function_type ? `<br>Jenis: ${pr.function_type}` : '';
            const ownerLine = pr.owner_name ? `<br>Pemilik: ${pr.owner_name}` : '';
            const regLine = pr.registration_number ? `<br>No. Reg: ${pr.registration_number}` : '';
            const nameLine = pr.name ? `<br>Nama: ${pr.name}` : '';
            m.bindPopup(`<b>PBG #${pr.id}</b><br>Status: <span class="badge" style="background:${color}">${pr.status_name || pr.category}</span>${nameLine}${regLine}${ownerLine}${fnLine}${areaLine}<br>Kecamatan: ${pr.district || '-'}`);
            m.addTo(pbgLayer);
        });
        lastShown.pbg = gj.features.length;
    }

    function updateMapCounter() {
        const el = document.getElementById('map-counter');
        if (!el) return;
        const f = readFilters();
        const z = map.getZoom();
        // Tier 1 filter-gated mode — kalau belum pilih kecamatan & low zoom, kasih hint.
        if (!f.dist && !f.pbgStatus && z <= CLUSTER_MAX_ZOOM && f.showSat) {
            el.innerHTML = '<strong>Mode overview kabupaten</strong> — warna kecamatan = density bangunan tanpa izin. <span class="fw-semibold">Klik kecamatan</span> atau pilih dari dropdown filter untuk lihat detail bangunan.';
            return;
        }
        const dist = f.dist || 'area ini';
        const parts = [];
        if (f.showSat) parts.push(`${Number(lastShown.sat).toLocaleString('id-ID')} deteksi satelit`);
        if (f.showPbg) parts.push(`${Number(lastShown.pbg).toLocaleString('id-ID')} titik PBG`);
        let msg = `Peta menampilkan ${parts.join(' + ')} di ${dist}. Cluster angka merah = jumlah bangunan di area tersebut; zoom-out buat liat total kecamatan.`;
        if (lastShown.sat >= lastShown.limit) msg += ` <span class="text-warning fw-semibold">Dibatasi ${lastShown.limit} marker — naikkan filter Luas Minimum biar semua muncul.</span>`;
        el.innerHTML = msg;
    }

    async function doLoadMap() {
        const seq = ++loadSeq;
        if (mapAbort) mapAbort.abort();
        mapAbort = new AbortController();
        const signal = mapAbort.signal;

        const b = map.getBounds();
        const z = map.getZoom();
        const f = readFilters();

        setLoading(true);
        try {
            await Promise.all([
                fetchSatellite(b, z, f, signal).catch(e => { if (e.name !== 'AbortError') console.error('[sat]', e); }),
                fetchPbg(b, f, signal).catch(e => { if (e.name !== 'AbortError') console.error('[pbg]', e); }),
            ]);
            if (seq !== loadSeq) return;
            updateMapCounter();
        } finally {
            if (seq === loadSeq) setLoading(false);
        }
    }

    // Tier 1 — pan/zoom debounce 300ms (bumped from 250ms).
    let loadMapTimer = null;
    function loadMap() {
        clearTimeout(loadMapTimer);
        loadMapTimer = setTimeout(doLoadMap, 300);
    }

    document.querySelectorAll('.verify-btn').forEach(b => b.addEventListener('click', async function() {
        if (!selId) return;
        const newStatus = this.dataset.status;
        try {
            const r = await fetch(`${API}/${selId}/status`, {method: 'PUT', headers: H, body: JSON.stringify({verification_status: newStatus})});
            if (!r.ok) throw new Error(`status HTTP ${r.status}`);

            // Phase 12 — if we're in polygon mode the cluster refresh is a no-op
            // (Phase 11 short-circuits fetchSatellite), so repaint the affected
            // polygon locally so the colour change is immediate. The next
            // PostGIS sync (Phase 4 cron) will make this permanent in the tile.
            if (_vtMode === 'polygon' && polygonLayer && typeof polygonLayer.setFeatureStyle === 'function') {
                const newColor = colors[newStatus] || '#ef4444';
                try {
                    polygonLayer.setFeatureStyle(Number(selId), {
                        fill: true, fillColor: newColor, fillOpacity: 0.55,
                        color: '#1f2937', weight: 0.4, opacity: 0.7,
                    });
                } catch (_) { /* setFeatureStyle throws if tile unloaded; ignore */ }
            }
        } catch (e) { console.error('[updateStatus]', e); }
        document.getElementById('verify-panel').style.display = 'none';
        loadMap(); loadStats();
    }));

    function applyFilters() { loadStats(); loadMap(); }
    document.getElementById('filter-min-area').addEventListener('change', applyFilters);

    document.getElementById('filter-data-source').addEventListener('change', applyFilters);
    const refreshBtn = document.getElementById('btn-refresh-stats');
    if (refreshBtn) refreshBtn.addEventListener('click', async function () {
        const prev = this.innerHTML; this.disabled = true; this.innerHTML = 'Refreshing...';
        try {
            const r = await fetch(`${API}/refresh-stats`, {method: 'POST', headers: H});
            if (!r.ok) throw new Error(`refresh HTTP ${r.status}`);
            await loadStats();
        } catch (e) { console.error('[refresh]', e); alert('Gagal refresh: ' + e.message); }
        finally { this.disabled = false; this.innerHTML = prev; }
    });
    document.getElementById('btn-reset-view').addEventListener('click', function() {
        distSel.value = '';
        document.getElementById('filter-data-source').value = '';
        document.getElementById('filter-pbg-status').value = '';
        document.getElementById('filter-min-area').value = '';
        const usageCat = document.getElementById('filter-usage-category');
        if (usageCat) usageCat.value = '';
        populateDesaDropdown('');
        document.getElementById('toggle-sat-layer').checked = true;
        document.getElementById('toggle-pbg-layer').checked = true;
        map.fitBounds(BS_BOUNDS);
        renderDesaForCurrentFilter();
        loadStats(); loadMap();
    });
    // Phase 3 — KRK PDF export button.
    document.getElementById('btn-export-krk')?.addEventListener('click', async function() {
        const kec = document.getElementById('filter-district')?.value;
        if (!kec) { alert('Pilih kecamatan dulu sebelum cetak KRK.'); return; }
        const prev = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating…';
        try {
            // Direct link triggers download — handles long generation gracefully.
            window.location.href = `/dashboards/krk-export/${encodeURIComponent(kec)}.pdf`;
            // Restore button after a short delay since we navigate to download
            setTimeout(() => { this.disabled = false; this.innerHTML = prev; }, 4000);
        } catch (e) {
            alert('Gagal generate: ' + e.message);
            this.disabled = false; this.innerHTML = prev;
        }
    });
    document.getElementById('toggle-sat-layer').addEventListener('change', loadMap);
    document.getElementById('toggle-pbg-layer').addEventListener('change', loadMap);
    document.getElementById('filter-pbg-status').addEventListener('change', applyFilters);
    map.on('moveend', loadMap);

    // ======================================================================
    // Phase 11 — auto-switch between cluster mode (z<14) and polygon mode
    // (z>=14). Driven by zoomend; safe to call repeatedly.
    // - Cluster mode: the existing markers/pbgLayer cluster groups are the
    //   right level of detail. The polygon canvas pane is faded out.
    // - Polygon mode: cluster of satellite buildings becomes redundant
    //   (each polygon is its own outline now), so it's removed from the map.
    //   PBG points stay — there are only 76 of them and they convey
    //   different info (permit dots, not building outlines).
    // The actual polygon tile requests are already minZoom-gated (Phase 10),
    // so this is mostly a UX layer; the marker fetch is also skipped in
    // polygon mode so we don't waste bandwidth on dots no one sees.
    // ======================================================================
    let _vtMode = null; // 'cluster' | 'polygon'
    const _modePill = document.getElementById('vt-mode-pill');

    function applyZoomMode() {
        // Phase 16 — users without polygon clearance always stay in cluster.
        if (!POLYGON_ALLOWED) { _vtMode = 'cluster'; return; }
        const z = map.getZoom();
        const next = (z >= VECTOR_TILES_MIN_ZOOM) ? 'polygon' : 'cluster';
        if (next === _vtMode) return;
        _vtMode = next;

        if (next === 'polygon') {
            if (map.hasLayer(markers)) map.removeLayer(markers);
            if (polygonLayer && !map.hasLayer(polygonLayer)) map.addLayer(polygonLayer);
            // Hide kecamatan choropleth + desa outline at z>=14. At polygon-
            // mode zoom the user is reading individual buildings; the colored
            // kecamatan/desa fill obscures them. Labels also get re-added
            // when zooming back out below.
            if (map.hasLayer(districtLayer)) map.removeLayer(districtLayer);
            if (map.hasLayer(desaLayer)) map.removeLayer(desaLayer);
            if (_modePill) { _modePill.textContent = 'Polygon'; _modePill.classList.remove('cluster'); _modePill.classList.add('polygon'); _modePill.title = 'Outline tiap bangunan — zoom out untuk kembali ke cluster'; }
        } else {
            // Remove the polygon layer entirely. Crucially NOT by hiding the
            // tile pane (that also holds Esri + Google base imagery and blanks
            // the whole map at zoom-out).
            if (polygonLayer && map.hasLayer(polygonLayer)) map.removeLayer(polygonLayer);
            // Restore kecamatan / desa overlays for overview zoom.
            if (!map.hasLayer(districtLayer)) map.addLayer(districtLayer);
            if (!map.hasLayer(desaLayer)) {
                map.addLayer(desaLayer);
                renderDesaForCurrentFilter();
            }
            const f = readFilters();
            if (f.showSat && !map.hasLayer(markers)) map.addLayer(markers);
            if (_modePill) { _modePill.textContent = 'Cluster'; _modePill.classList.remove('polygon'); _modePill.classList.add('cluster'); _modePill.title = 'Zoom in (≥14) untuk lihat polygon'; }
        }

        const counter = document.getElementById('map-counter');
        if (counter && next === 'polygon') {
            counter.innerHTML = '<strong>Polygon mode</strong> — outline tiap bangunan dari deteksi satelit. Warna mengikuti status PBG. Zoom out untuk kembali ke cluster.';
        }
    }
    map.on('zoomend', applyZoomMode);
    // Show a brief "zoom in for polygons" hint on first load if the user
    // has clearance and we're below z14.
    if (POLYGON_ALLOWED) {
        setTimeout(() => {
            if (map.getZoom() < VECTOR_TILES_MIN_ZOOM) {
                const h = document.getElementById('vt-hint');
                if (h) { h.classList.add('show'); setTimeout(() => h.classList.remove('show'), 5000); }
            }
        }, 1500);
    }
    // Skip the satellite cluster fetch in polygon mode (network savings).
    const _origFetchSatellite = fetchSatellite;
    fetchSatellite = async function (b, z, f, signal) {
        if (_vtMode === 'polygon') { markers.clearLayers(); lastShown.sat = 0; return; }
        return _origFetchSatellite(b, z, f, signal);
    };
    applyZoomMode(); // initial sync — runs at map's starting zoom (11)
    // ======================================================================

    loadStats(); loadMap();
});
</script>
@endsection
