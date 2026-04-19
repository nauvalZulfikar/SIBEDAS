@extends('layouts.vertical', ['subtitle' => 'Monitoring Satelit'])

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<style>
    #satellite-map { height: 550px; border-radius: 8px; }
    .legend-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 6px; }
    .stat-card { transition: transform 0.2s; cursor: pointer; }
    .stat-card:hover { transform: translateY(-2px); }
</style>
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Monitoring', 'subtitle' => 'Deteksi Bangunan Satelit'])

<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-primary rounded me-3"><iconify-icon icon="solar:buildings-broken" class="fs-32 avatar-title text-primary"></iconify-icon></div>
            <div><p class="text-muted mb-0">Total Terdeteksi</p><h4 class="mb-0" id="stat-total">-</h4></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-success rounded me-3"><iconify-icon icon="solar:verified-check-broken" class="fs-32 avatar-title text-success"></iconify-icon></div>
            <div><p class="text-muted mb-0">Ber-izin PBG</p><h4 class="mb-0 text-success" id="stat-matched">-</h4></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-danger rounded me-3"><iconify-icon icon="solar:danger-triangle-broken" class="fs-32 avatar-title text-danger"></iconify-icon></div>
            <div><p class="text-muted mb-0">Suspect Tanpa Izin</p><h4 class="mb-0 text-danger" id="stat-unmatched">-</h4></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-warning rounded me-3"><iconify-icon icon="solar:chart-broken" class="fs-32 avatar-title text-warning"></iconify-icon></div>
            <div><p class="text-muted mb-0">Match Rate</p><h4 class="mb-0 text-warning" id="stat-rate">-</h4></div>
        </div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Peta Deteksi Bangunan — Kab. Bandung</h5>
                <div class="d-flex gap-2"><span><span class="legend-dot" style="background:#ef4444"></span>Tanpa Izin</span><span><span class="legend-dot" style="background:#22c55e"></span>Ber-izin</span><span><span class="legend-dot" style="background:#f59e0b"></span>Under Review</span></div>
            </div>
            <div class="card-body p-0"><div id="satellite-map"></div></div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Bangunan Tanpa Izin per Kecamatan</h5></div>
            <div class="card-body"><div class="table-responsive">
                <table class="table table-hover mb-0"><thead class="table-light"><tr><th>Kecamatan</th><th class="text-end">Jumlah</th><th style="width:50%">Distribusi</th></tr></thead>
                <tbody id="district-tbody"><tr><td colspan="3" class="text-center text-muted">Memuat data...</td></tr></tbody></table>
            </div></div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card"><div class="card-header"><h5 class="card-title mb-0">Filter</h5></div><div class="card-body">
            <div class="mb-3"><label class="form-label">Kecamatan</label><select id="filter-district" class="form-select form-select-sm"><option value="">Semua</option></select></div>
            <div class="mb-3"><label class="form-label">Status</label><select id="filter-status" class="form-select form-select-sm"><option value="">Semua</option><option value="unverified">Belum Diverifikasi</option><option value="confirmed_illegal">Confirmed Illegal</option><option value="confirmed_legal">Confirmed Legal</option><option value="under_review">Under Review</option></select></div>
            <div class="mb-3"><label class="form-label">Luas Minimum</label><select id="filter-min-area" class="form-select form-select-sm"><option value="">Semua</option><option value="50">50+ m²</option><option value="200" selected>200+ m²</option><option value="500">500+ m²</option><option value="1000">1000+ m²</option></select></div>
            <div class="mb-3"><label class="form-label">Tampilkan</label><select id="filter-permit" class="form-select form-select-sm"><option value="unmatched">Tanpa Izin Saja</option><option value="">Semua</option></select></div>
            <button class="btn btn-primary btn-sm w-100" id="btn-apply-filter">Terapkan Filter</button>
        </div></div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API = '/api/detected-buildings';
    const TOKEN = localStorage.getItem('token') || '';
    const H = {'Accept':'application/json','Content-Type':'application/json','Authorization':TOKEN?`Bearer ${TOKEN}`:''};

    const map = L.map('satellite-map').setView([-6.98, 107.55], 11);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{attribution:'Esri',maxZoom:19}).addTo(map);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png',{maxZoom:19,pane:'overlayPane'}).addTo(map);

    const markers = L.markerClusterGroup({maxClusterRadius:50,showCoverageOnHover:false,
        iconCreateFunction:c=>{const n=c.getChildCount();const d=n>100?50:n>10?40:30;
        return L.divIcon({html:`<div style="background:rgba(239,68,68,0.8);color:#fff;border-radius:50%;width:${d}px;height:${d}px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3)">${n>999?Math.round(n/1000)+'k':n}</div>`,className:'mc',iconSize:L.point(d,d)});}
    });
    map.addLayer(markers);

    let selId = null;
    const colors = {unverified:'#ef4444',confirmed_illegal:'#dc2626',confirmed_legal:'#22c55e',under_review:'#f59e0b',false_positive:'#6b7280'};

    async function loadStats() {
        try {
            const r = await fetch(`${API}/stats`,{headers:H});
            const d = await r.json();
            document.getElementById('stat-total').textContent = Number(d.total_detected).toLocaleString('id-ID');
            document.getElementById('stat-matched').textContent = Number(d.matched_with_permit).toLocaleString('id-ID');
            document.getElementById('stat-unmatched').textContent = Number(d.unmatched_suspect).toLocaleString('id-ID');
            document.getElementById('stat-rate').textContent = d.match_rate+'%';
            if(d.unmatched_by_district) {
                const sel = document.getElementById('filter-district');
                Object.keys(d.unmatched_by_district).sort().forEach(n=>{if(n&&n!='null'){const o=document.createElement('option');o.value=n;o.textContent=`${n} (${Number(d.unmatched_by_district[n]).toLocaleString('id-ID')})`;sel.appendChild(o);}});
                const tb=document.getElementById('district-tbody');const entries=Object.entries(d.unmatched_by_district);const mx=Math.max(...entries.map(([,v])=>v));
                tb.innerHTML=entries.map(([n,c])=>`<tr class="district-row" data-d="${n}" style="cursor:pointer"><td>${n}</td><td class="text-end">${Number(c).toLocaleString('id-ID')}</td><td><div class="progress" style="height:8px"><div class="progress-bar bg-danger" style="width:${(c/mx*100).toFixed(0)}%"></div></div></td></tr>`).join('');
                tb.querySelectorAll('.district-row').forEach(r=>r.addEventListener('click',()=>{document.getElementById('filter-district').value=r.dataset.d;loadMap();}));
            }
        } catch(e) { console.error(e); }
    }

    async function loadMap() {
        const b=map.getBounds();const p=new URLSearchParams({sw_lat:b.getSouth(),sw_lng:b.getWest(),ne_lat:b.getNorth(),ne_lng:b.getEast()});
        const dist=document.getElementById('filter-district').value;
        const status=document.getElementById('filter-status').value;
        const area=document.getElementById('filter-min-area').value;
        const permit=document.getElementById('filter-permit').value;
        if(dist)p.set('district',dist);if(status)p.set('status',status);if(area)p.set('min_area',area);if(permit==='unmatched')p.set('unmatched_only','1');
        try {
            const r=await fetch(`${API}/geojson?${p}`,{headers:H});const gj=await r.json();
            markers.clearLayers();
            if(gj.features) gj.features.forEach(f=>{
                const[lng,lat]=f.geometry.coordinates;const pr=f.properties;
                const color=pr.has_permit?'#22c55e':(colors[pr.status]||'#ef4444');
                const m=L.circleMarker([lat,lng],{radius:Math.min(4+(pr.area_m2||0)/200,12),fillColor:color,color:'#fff',weight:1,fillOpacity:0.8});
                m.bindPopup(`<b>#${pr.id}</b><br>Luas: ${pr.area_m2?Number(pr.area_m2).toLocaleString('id-ID')+' m²':'N/A'}<br>Status: <span class="badge ${pr.has_permit?'bg-success':'bg-danger'}">${pr.has_permit?'Ber-izin':'Tanpa Izin'}</span><br>Kecamatan: ${pr.district||'-'}<br><button class="btn btn-sm btn-outline-primary mt-1 w-100 bv" data-id="${pr.id}" data-area="${pr.area_m2}" data-d="${pr.district||''}">Verifikasi</button>`);
                m.on('popupopen',()=>document.querySelectorAll('.bv').forEach(b=>b.addEventListener('click',function(){selId=this.dataset.id;document.getElementById('verify-id').textContent='#'+this.dataset.id;document.getElementById('verify-area').textContent=Number(this.dataset.area).toLocaleString('id-ID');document.getElementById('verify-district').textContent=this.dataset.d;document.getElementById('verify-panel').style.display='block';})));
                markers.addLayer(m);
            });
        } catch(e) { console.error(e); }
    }

    document.querySelectorAll('.verify-btn').forEach(b=>b.addEventListener('click',async function(){
        if(!selId)return;
        await fetch(`${API}/${selId}/status`,{method:'PUT',headers:H,body:JSON.stringify({verification_status:this.dataset.status})});
        document.getElementById('verify-panel').style.display='none';loadMap();loadStats();
    }));

    document.getElementById('btn-apply-filter').addEventListener('click',loadMap);
    map.on('moveend',loadMap);
    loadStats();loadMap();
});
</script>
@endsection
