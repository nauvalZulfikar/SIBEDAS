@extends('layouts.vertical', ['subtitle' => 'Satelit ↔ PBG ↔ PBB'])

@section('css')
<style>
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-2px); }
    #spp-bar { min-height: 380px; }
    #spp-table th, #spp-table td { font-size: 13px; vertical-align: middle; }
    .progress-3 { height: 18px; border-radius: 3px; overflow: hidden; }
    .legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; }
</style>
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Dashboard', 'subtitle' => 'Satelit ↔ PBG ↔ PBB (3-layer cross-reference)'])

<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-info rounded me-3"><iconify-icon icon="solar:buildings-broken" class="fs-32 avatar-title text-info"></iconify-icon></div>
            <div><p class="text-muted mb-0">Bangunan Satelit</p><h4 class="mb-0 text-info" id="kpi-sat">-</h4>
                <small class="text-muted">31 kec Bandung Selatan</small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-primary rounded me-3"><iconify-icon icon="solar:document-text-broken" class="fs-32 avatar-title text-primary"></iconify-icon></div>
            <div><p class="text-muted mb-0">PBB Terbangun</p><h4 class="mb-0" id="kpi-pbb">-</h4>
                <small class="text-muted">NOP terdaftar bangunan</small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-success rounded me-3"><iconify-icon icon="solar:verified-check-broken" class="fs-32 avatar-title text-success"></iconify-icon></div>
            <div><p class="text-muted mb-0">PBG Terbit (Berizin)</p><h4 class="mb-0 text-success" id="kpi-pbg">-</h4>
                <small class="text-muted"><span id="kpi-rasio">-</span>% rasio berizin</small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-danger rounded me-3"><iconify-icon icon="solar:danger-triangle-broken" class="fs-32 avatar-title text-danger"></iconify-icon></div>
            <div><p class="text-muted mb-0">Tidak Berizin</p><h4 class="mb-0 text-danger" id="kpi-tidak">-</h4>
                <small class="text-muted">satelit tanpa SK PBG</small></div>
        </div></div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0">Rincian per Kecamatan</h5>
            <small class="text-muted">Sumber: <code>detected_buildings</code> + <code>pbg_task</code> + <code>pbb_kecamatan_lookup</code></small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <label class="m-0 small">Min luas bangunan (m²):</label>
            <select id="filter-min-area" class="form-select form-select-sm" style="width:auto">
                <option value="0" selected>Semua</option>
                <option value="50">≥ 50</option>
                <option value="100">≥ 100</option>
                <option value="200">≥ 200</option>
                <option value="500">≥ 500</option>
                <option value="1000">≥ 1.000</option>
            </select>
            <span class="ms-2 small text-muted" id="last-computed">Memuat…</span>
        </div>
    </div>
    <div class="card-body">
        <div id="spp-bar"></div>
        <div class="text-end small mt-1">
            <span><span class="legend-dot" style="background:#22c55e"></span>PBG Terbit</span>
            <span class="ms-3"><span class="legend-dot" style="background:#f59e0b"></span>PBG Proses</span>
            <span class="ms-3"><span class="legend-dot" style="background:#ef4444"></span>Tidak Berizin</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Tabel Cross-reference per Kecamatan</h5></div>
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover" id="spp-table">
            <thead class="table-light">
                <tr>
                    <th>Kecamatan</th>
                    <th class="text-end">Satelit</th>
                    <th class="text-end">PBB Terbangun</th>
                    <th class="text-end text-success">PBG Terbit</th>
                    <th class="text-end text-warning">PBG Proses</th>
                    <th class="text-end text-danger">Tidak Berizin</th>
                    <th class="text-end">Rasio Berizin</th>
                </tr>
            </thead>
            <tbody id="spp-tbody">
                <tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr id="spp-foot">
                    <td>TOTAL</td><td colspan="6"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
@vite(['resources/js/dashboards/satelit-pbg-pbb.js'])
@endsection
