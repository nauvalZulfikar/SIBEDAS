@extends('layouts.vertical', ['subtitle' => 'Rekonsiliasi PBB'])

@section('css')
<style>
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .badge-pending { background: #fff3cd; color: #856404; font-size: 11px; }
    .badge-covered { background: #d1e7dd; color: #0f5132; font-size: 11px; }
    .gap-pos { color: #d63384; font-weight: 600; }   /* sat > terbangun = ilegal */
    .gap-neg { color: #6c757d; }                    /* sat < terbangun = undercount */
    #recon-bar-chart { min-height: 360px; }
    #recon-table tr { cursor: pointer; }
    #recon-table tr:hover td { background: #f8f9fa; }
    .modal-kelurahan-tbl td, .modal-kelurahan-tbl th { vertical-align: middle; font-size: 13px; }
    .audit-tbl td, .audit-tbl th { font-size: 12px; }
    .nav-tabs .nav-link { font-weight: 500; }
    .stale-warn { font-size: 12px; color: #856404; background: #fff3cd; padding: 6px 12px; border-radius: 6px; }
</style>
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Dashboard', 'subtitle' => 'Rekonsiliasi PBB ↔ Satelit ↔ PBG'])

<div class="row mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-primary rounded me-3"><iconify-icon icon="solar:document-text-broken" class="fs-32 avatar-title text-primary"></iconify-icon></div>
            <div><p class="text-muted mb-0">PBB Terbangun</p><h4 class="mb-0" id="kpi-pbb-terbangun">-</h4>
                <small class="text-muted">dari total <span id="kpi-pbb-total">-</span> NOP</small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-info rounded me-3"><iconify-icon icon="solar:buildings-broken" class="fs-32 avatar-title text-info"></iconify-icon></div>
            <div><p class="text-muted mb-0">Bangunan Satelit</p><h4 class="mb-0 text-info" id="kpi-sat">-</h4>
                <small class="text-muted">total terdeteksi</small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-danger rounded me-3"><iconify-icon icon="solar:danger-triangle-broken" class="fs-32 avatar-title text-danger"></iconify-icon></div>
            <div><p class="text-muted mb-0">Gap (Sat − Terbangun)</p><h4 class="mb-0" id="kpi-gap">-</h4>
                <small class="text-muted"><span id="kpi-gap-pct">-</span></small></div>
        </div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card"><div class="card-body"><div class="d-flex align-items-center">
            <div class="avatar-md bg-soft-success rounded me-3"><iconify-icon icon="solar:check-circle-broken" class="fs-32 avatar-title text-success"></iconify-icon></div>
            <div><p class="text-muted mb-0">PBG Terbit</p><h4 class="mb-0 text-success" id="kpi-pbg">-</h4>
                <small class="text-muted">SK Terbit (kab)</small></div>
        </div></div></div>
    </div>
</div>

@php
    $rank = ['level_1' => 1, 'level_2' => 2, 'level_3' => 3];
    $myLvl = $rank[$pbbClearance ?? 'level_1'];
@endphp
<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="stale-warn" id="last-computed">Memuat data…</span>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                <iconify-icon icon="solar:download-broken" class="me-1"></iconify-icon>Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" data-export="pdf">
                    <iconify-icon icon="solar:document-broken" class="me-1"></iconify-icon>PDF — Laporan Eksekutif
                </a></li>
                @if ($myLvl >= 2)
                <li><a class="dropdown-item" href="#" data-export="excel">
                    <iconify-icon icon="solar:notebook-broken" class="me-1"></iconify-icon>Excel — Multi-sheet
                    @if ($myLvl == 2)<span class="badge bg-warning text-dark ms-1" style="font-size:9px">PII Masked</span>@endif
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-export="csv" data-scope="kab">CSV — Kab Summary</a></li>
                <li><a class="dropdown-item" href="#" data-export="csv" data-scope="kec">CSV — Per Kecamatan</a></li>
                <li><a class="dropdown-item" href="#" data-export="csv" data-scope="kelurahan">CSV — Per Kelurahan</a></li>
                @endif
            </ul>
        </div>
        @if (($pbbClearance ?? 'level_1') === 'level_3')
        <button class="btn btn-sm btn-outline-primary" id="btn-recompute" title="Superadmin only — full recompute (~5s)">
            <iconify-icon icon="solar:refresh-broken" class="me-1"></iconify-icon>Recompute
        </button>
        @endif
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="reconTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview Per-Kecamatan</button></li>
    @if ($myLvl >= 2)
    <li class="nav-item"><button class="nav-link" id="tab-audit-btn" data-bs-toggle="tab" data-bs-target="#tab-audit" type="button">
        Audit List
        @if ($myLvl == 2)<span class="badge bg-warning text-dark ms-1" style="font-size:10px">PII Masked</span>@endif
    </button></li>
    @endif
</ul>
@if ($myLvl == 1)
<div class="alert alert-info py-2 small mb-3">
    <iconify-icon icon="solar:info-circle-broken" class="me-1"></iconify-icon>
    Akses Anda saat ini di level 1 (aggregate kab + kec). Drill-down per-kelurahan & audit list memerlukan clearance lebih tinggi (admin).
</div>
@endif

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
        <div class="card mb-3">
            <div class="card-header py-2"><h5 class="card-title mb-0">Gap Per Kecamatan (Sat − PBB Terbangun)</h5></div>
            <div class="card-body py-2"><div id="recon-bar-chart"></div></div>
        </div>

        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detail Per Kecamatan</h5>
                <small class="text-muted">Klik baris untuk drill-down ke kelurahan</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="recon-table">
                        <thead class="table-light"><tr>
                            <th>Kecamatan</th>
                            <th class="text-end">PBB Total</th>
                            <th class="text-end">PBB Terbangun</th>
                            <th class="text-end">Sat Count</th>
                            <th class="text-end">Gap</th>
                            <th class="text-end">Gap %</th>
                            <th></th>
                        </tr></thead>
                        <tbody id="recon-tbody"><tr><td colspan="7" class="text-center text-muted py-4">Memuat…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-audit" role="tabpanel">
        <div class="row">
            <div class="col-xl-6 mb-3">
                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">PBB Terbangun Tanpa Match Satelit</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:520px">
                            <table class="table table-sm audit-tbl mb-0">
                                <thead class="table-light sticky-top"><tr>
                                    <th>NOP</th><th>Nama WP</th><th>Kec</th><th>Kel</th><th class="text-end">L.Bgn</th>
                                </tr></thead>
                                <tbody id="audit-no-sat"><tr><td colspan="5" class="text-center text-muted py-3">-</td></tr></tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2 small text-muted" id="audit-no-sat-meta">-</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-3">
                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">Bangunan Satelit Tanpa NOP (Kandidat Ilegal)</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:520px">
                            <table class="table table-sm audit-tbl mb-0">
                                <thead class="table-light sticky-top"><tr>
                                    <th>ID</th><th>Lat</th><th>Lng</th><th>Kec</th><th class="text-end">Luas (m²)</th>
                                </tr></thead>
                                <tbody id="audit-no-nop"><tr><td colspan="5" class="text-center text-muted py-3">-</td></tr></tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2 small text-muted" id="audit-no-nop-meta">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Drill-down kelurahan modal -->
<div class="modal fade" id="kelurahanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="kel-modal-title">Detail Kelurahan</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-2 small text-muted" id="kel-modal-coverage-note"></div>
                <div class="table-responsive" style="max-height:60vh">
                    <table class="table table-sm modal-kelurahan-tbl mb-0">
                        <thead class="table-light sticky-top"><tr>
                            <th>Kelurahan</th>
                            <th class="text-end">PBB Terbangun</th>
                            <th class="text-end">Sat Count</th>
                            <th class="text-end">Gap</th>
                            <th class="text-end">Gap %</th>
                            <th>Coverage</th>
                        </tr></thead>
                        <tbody id="kel-modal-tbody"><tr><td colspan="6" class="text-center py-3 text-muted">Memuat…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.RECON_TOKEN = "{{ session('api_token') }}";
    window.RECON_CLEARANCE = "{{ $pbbClearance ?? 'level_1' }}";
</script>
@vite(['resources/js/dashboards/reconciliation.js'])
@endsection
