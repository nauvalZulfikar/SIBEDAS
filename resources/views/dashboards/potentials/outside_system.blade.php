<!-- base layout -->
@extends('layouts.vertical', ['subtitle' => 'Dashboards'])
<!-- style -->
@section('css')
@vite(['resources/scss/dashboards/potentials/_outside_system.scss'])
@endsection
<!-- content -->
@section('content')
@include('layouts.partials.page-title', ['title' => 'Dashboards', 'subtitle' => 'Dalam Sistem'])

{{-- Sinkronisasi Monitoring Satelit — PBG existing yang ter-konfirmasi citra satelit --}}
{{-- z-index agar tidak ter-overlay oleh .outside-system-wrapper::before (full-viewport white overlay) --}}
<div class="card mb-3 position-relative" id="satelit-sync-dalam" style="z-index: 2;">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h5 class="card-title mb-0">
            <iconify-icon icon="solar:satellite-broken" class="me-1 align-middle"></iconify-icon>
            Sinkronisasi Monitoring Satelit — PBG Ter-Validasi Citra Satelit
        </h5>
        <a href="{{ route('dashboard.satellite-monitoring') }}"
           class="btn btn-sm btn-outline-primary">
            Lihat detail di Monitoring Satelit
            <iconify-icon icon="solar:arrow-right-broken" class="ms-1"></iconify-icon>
        </a>
    </div>
    <div class="card-body py-3">
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-primary rounded me-3">
                            <iconify-icon icon="solar:document-broken" class="fs-32 avatar-title text-primary"></iconify-icon>
                        </div>
                        <div><p class="text-muted mb-0 small">Total PBG Tercatat</p>
                            <h4 class="mb-0" id="dalam-pbg-total">-</h4></div>
                    </div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-success rounded me-3"><iconify-icon icon="solar:verified-check-broken" class="fs-32 avatar-title text-success"></iconify-icon></div>
                        <div><p class="text-muted mb-0 small">Ter-Validasi Satelit (SK Terbit + cocok citra)</p>
                            <h4 class="mb-0 text-success" id="dalam-permit-valid">-</h4>
                            <small class="text-muted" id="dalam-permit-valid-rate">-</small></div>
                    </div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-warning rounded me-3"><iconify-icon icon="solar:hourglass-broken" class="fs-32 avatar-title text-warning"></iconify-icon></div>
                        <div><p class="text-muted mb-0 small">Dalam Proses (cocok citra)</p>
                            <h4 class="mb-0 text-warning" id="dalam-permit-process">-</h4></div>
                    </div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-danger rounded me-3"><iconify-icon icon="solar:close-circle-broken" class="fs-32 avatar-title text-danger"></iconify-icon></div>
                        <div><p class="text-muted mb-0 small">Ditolak / Batal</p>
                            <h4 class="mb-0 text-danger" id="dalam-permit-rejected">-</h4></div>
                    </div></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6 class="text-muted mb-2">Top 5 Kecamatan dengan PBG Ter-Validasi Satelit Terbanyak</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Kecamatan</th>
                                <th class="text-end">SK Terbit</th>
                                <th class="text-end">Proses</th>
                                <th class="text-end">Ditolak</th>
                            </tr>
                        </thead>
                        <tbody id="dalam-top-kecamatan">
                            <tr><td colspan="5" class="text-center text-muted small py-3">Memuat…</td></tr>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2" id="dalam-snapshot-note"></small>
            </div>
        </div>
    </div>
</div>

<div class="outside-system-wrapper" id="outside-system-wrapper">
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mt-3 ms-2">
            <h2 class="text-danger m-0">
                ANALISA BIG DATA MELALUI APLIKASI <br>
                SIBEDAS PBG DALAM SISTEM
            </h2>
            <div class="text-black text-end d-flex flex-column align-items-end me-3">
                <input type="text" class="form-control mt-2" style="max-width: 125px;" id="datepicker-outside-system" placeholder="Filter Date" />
            </div>
        </div>
    </div>
    <div id="outside-system-fixed-container" class="" style="width:880px;height:770px;position:relative;margin:auto;z-index:1;">
        <div style="position: absolute; top: 70px; left: 50px; width: 200px; height: 500px;">
            @component('components.circle', [
                'document_title' => 'Non Usaha',
                'document_color' => '#399787',
                'document_type' => 'Berkas',
                'document_id' => 'outside-system-non-business',
                'visible_small_circle' => true,
                'style' => 'top:10px;',
                'document_url' => route('pbg-task.index', ['menu_id' => 13, 'filter' => 'non-business'])
            ])
            @endcomponent
            <div class="square dia-top-right-bottom-left" style="top:10px;left:180px;width:230px;height:120px;"></div>
            @component('components.circle', [
                'document_title' => 'Usaha',
                'document_color' => '#5e7c89',
                'document_type' => 'Berkas',
                'document_id' => 'outside-system-business',
                'visible_small_circle' => true,
                'style' => 'top:300px;',
                'document_url' => route('pbg-task.index', ['menu_id' => 13, 'filter' => 'business'])
                ])
            @endcomponent
            <div class="square dia-top-right-bottom-left" style="top:320px;left:170px;width:200px;height:100px;"></div>
            
            <div class="square dia-top-left-bottom-right" style="top:120px;left:180px;width:500px;height:120px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:410px;left:180px;width:500px;height:160px;"></div>
        </div>
        <div style="position: absolute; top: 50px; left: 350px; width: 200px; height: 550px;">
            <div class="square" style="width:200px;height:2px;background-color:black;left:100px;top:70px;"></div>
            <x-custom-circle title="Keterangan Rencana Kota (KRK)" size="large" style="background-color: #306364;position: absolute;" />
            <x-custom-circle title="Keterangan Rencana Kota (KRK)" size="large" style="background-color: #38b64b;position: absolute; top: 320px;" />
            <div class="square" style="width:200px;height:2px;background-color:black;left:100px;top:390px;"></div>
        </div>
        <div style="position: absolute; top: 50px; left: 600px; width: 200px; height: 650px;">
            <x-custom-circle title="Samirindu DPMPTSP" size="large" style="background-color: #0e4753;position: absolute; top: 0px;" />
            <x-custom-circle title="RAB dan Gambar" size="large" style="background-color: #f0195b;position: absolute; top: 160px;" />
            <x-custom-circle title="OSS RBA (Nasional)" size="large" style="background-color: #38b64b;position: absolute; top: 320px;" />
            <x-custom-circle title="Dokumen Lingkungan (DLH)" size="large" style="background-color: #393536;position: absolute; top: 480px;" />
            <div class="square dia-top-left-bottom-right" style="top:50px;left:120px;width:250px;height:250px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:230px;left:120px;width:220px;height:100px;"></div>
            <div class="square dia-top-right-bottom-left" style="top:320px;left:120px;width:200px;height:100px;"></div>
            <div class="square dia-top-right-bottom-left" style="top:350px;left:120px;width:250px;height:200px;"></div>
        </div>
        <div style="position: absolute; top: 50px; left: 900px; width: 200px; height: 550px;">
            <x-custom-circle title="Pemohon" size="large" style="background-color: #393536;position: absolute; top: 250px;" />
        </div>
    </div>
</div>
@endsection
<!-- javascripts -->
@section('scripts')
@vite(['resources/js/dashboards/potentials/outside_system.js'])
@endsection