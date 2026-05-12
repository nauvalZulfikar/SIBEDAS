@extends('layouts.vertical', ['subtitle' => 'Dashboards'])

@section('css')
@vite(['resources/scss/dashboards/potentials/_inside_system.scss'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Dashboards', 'subtitle' => 'Luar Sistem'])

{{-- Sinkronisasi Monitoring Satelit — bangunan tanpa izin sah (potensi pendapatan) --}}
{{-- z-index agar tidak ter-overlay oleh .lack-of-potential-wrapper::before (full-viewport white overlay) --}}
<div class="card mb-3 position-relative" id="satelit-sync-luar" style="z-index: 2;">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h5 class="card-title mb-0">
            <iconify-icon icon="solar:satellite-broken" class="me-1 align-middle"></iconify-icon>
            Sinkronisasi Monitoring Satelit — Bangunan Tanpa Izin Sah
        </h5>
        <a href="{{ route('dashboard.satellite-monitoring') }}?filter=unmatched"
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
                            <iconify-icon icon="solar:buildings-broken" class="fs-32 avatar-title text-primary"></iconify-icon>
                        </div>
                        <div><p class="text-muted mb-0 small">Total Terdeteksi Satelit</p>
                            <h4 class="mb-0" id="luar-stat-total">-</h4></div>
                    </div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-danger rounded me-3">
                            <iconify-icon icon="solar:danger-triangle-broken" class="fs-32 avatar-title text-danger"></iconify-icon>
                        </div>
                        <div><p class="text-muted mb-0 small">Tanpa Izin Sah</p>
                            <h4 class="mb-0 text-danger" id="luar-stat-without-permit">-</h4>
                            <small class="text-muted" id="luar-stat-without-breakdown">-</small></div>
                    </div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-warning rounded me-3">
                            <iconify-icon icon="solar:map-point-search-broken" class="fs-32 avatar-title text-warning"></iconify-icon>
                        </div>
                        <div><p class="text-muted mb-0 small">Tidak Ter-Match PBG</p>
                            <h4 class="mb-0 text-warning" id="luar-stat-unmatched">-</h4>
                            <small class="text-muted">tidak ada PBG di radius 50m</small></div>
                    </div></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card mb-0 h-100">
                    <div class="card-body py-2"><div class="d-flex align-items-center">
                        <div class="avatar-md bg-soft-info rounded me-3">
                            <iconify-icon icon="solar:percent-broken" class="fs-32 avatar-title text-info"></iconify-icon>
                        </div>
                        <div><p class="text-muted mb-0 small">Rasio Tanpa Izin</p>
                            <h4 class="mb-0 text-info" id="luar-stat-without-rate">-</h4>
                            <small class="text-muted">terhadap total deteksi</small></div>
                    </div></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6 class="text-muted mb-2">Top 5 Kecamatan dengan Bangunan Tanpa Izin Terbanyak</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Kecamatan</th>
                                <th class="text-end">Tanpa Izin</th>
                                <th class="text-end">% dari total kecamatan</th>
                            </tr>
                        </thead>
                        <tbody id="luar-top-kecamatan">
                            <tr><td colspan="4" class="text-center text-muted small py-3">Memuat…</td></tr>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2" id="luar-snapshot-note"></small>
            </div>
        </div>
    </div>
</div>

<div class="lack-of-potential-wrapper">
    <div class="row" id="lack-of-potential-wrapper">
        <div class="d-flex justify-content-between align-items-center mt-3 ms-2">
            <h2 class="text-danger m-0">
                ANALISA BIG DATA MELALUI APLIKASI <br> SIBEDAS PBG LUAR SISTEM
            </h2>
            <div class="text-black text-end d-flex flex-column align-items-end me-3">
                <input type="date" class="form-control mt-2" style="max-width: 150px;" id="datepicker-lack-of-potential" />
            </div>
        </div>
    </div>
    <div class="wrapper">
        <div id="lack-of-potential-fixed-container" class="" style="width:1400px;height:1300px;position:relative;margin:auto;z-index:1;">
            @component('components.circle', [
                'document_title' => 'Kekurangan Potensi',
                'document_color' => '#ff5757',
                'document_type' => '',
                'document_id' => 'chart-lack-of-potential',
                'visible_small_circle' => false,
                'style' => 'margin-left:180px;top:50px;'
                ])
            @endcomponent

            <div class="line" style="top:480px;left:430px;width:900px;height:2px;background-color:black;"></div>
            <div class="line" style="top:590px;left:300px;width:950px;height:2px;rotate: 90deg; background-color: black;"></div>
            <div style="position: absolute; top: -250px; left: 900px;">
                <x-custom-circle title="PBB ADA BANGUNAN" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="pbb-bangunan-count" data_count="578005" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: -10px; left: 700px;">
                <x-custom-circle title="PDAM" visible_data="true" data_id="pdam-count" data_count="0" visible_data_type="true" data_type="0"
                    size="large" style="background-color: #627c8b;" 
                    document_url="{{ route('customers', ['menu_id' => $menus->where('url','customers')->first()->id]) }}"
                />
            </div>

            <div class="square dia-top-right-bottom-left" style="top:100px;left:820px;width:120px;height:100px;"></div>
            <div style="position: absolute; top: -100px; left: 700px;">
                <x-custom-circle title="NON USAHA" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="non-usaha-count" data_count="0" 
                    document_url=""
                />
            </div>
            
            <div style="position: absolute; top: 100px; left: 650px;">
                <x-custom-circle title="BIG DATA POTENSI" size="very-large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="big-data-potensi-count" data_count="0" 
                    document_url=""
                />
            </div>

            {{-- tim wasdal gabungan --}}
            <div style="position: absolute; top: 150px; left: 1100px;">
                <x-custom-circle title="TIM WASDAL GABUNGAN" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="tim-wasdal-gabungan-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div class="square dia-top-right-bottom-left" style="top:380px;left:1200px;width:120px;height:100px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:480px;left:1200px;width:120px;height:100px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:530px;left:1150px;width:120px;height:100px;"></div>
            <div class="square dia-top-right-bottom-left" style="top:530px;left:1040px;width:120px;height:100px;"></div>
            <div class="square" style="top:600px;left:1100px;width:100px;height:2px;rotate: 100deg; background-color: black;"></div>
            <div style="position: absolute; top: 350px; left: 1000px;">
                <x-custom-circle title="UPT WASDAL" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="upt-wasdal-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 350px; left: 1100px;">
                <x-custom-circle title="SAT POLPP" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="sat-polpp-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 350px; left: 1200px;">
                <x-custom-circle title="KEJARI" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="kejari-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 270px; left: 1300px;">
                <x-custom-circle title="TNI" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="tni-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 170px; left: 1300px;">
                <x-custom-circle title="POLRI" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="polri-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 70px; left: 1300px;">
                <x-custom-circle title="SATGAS" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="satgas-count" data_count="0" 
                    document_url=""
                />
            </div>

            {{-- tata ruang --}}
            {{-- <div style="position: absolute; top: 120px; left: 300px;">
                <x-custom-circle title="TATA RUANG" size="medium" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="tata-ruang-count" data_count="0" 
                    document_url=""
                />
            </div> --}}

            @component('components.circle', [
                'document_title' => 'TATA RUANG',
                'document_color' => '#627c8b',
                'document_type' => '',
                'document_id' => 'chart-tata-ruang',
                'visible_small_circle' => false,
                'style' => 'margin-left:300px;top:370px;'
                ])
            @endcomponent
            <div class="square dia-top-right-bottom-left" style="top:500px;left:220px;width:120px;height:100px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:350px;left:220px;width:120px;height:100px;"></div>
            <div style="position: absolute; top: 50px; left: 150px;">
                <x-custom-circle title="USAHA" size="large" style="background-color: #388fc2;margin-top:260px;" 
                    visible_data="true" data_id="tata-ruang-usaha-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 300px; left: 150px;">
                <x-custom-circle title="NON USAHA" size="large" style="background-color: #388fc2;margin-top:260px;" 
                    visible_data="true" data_id="tata-ruang-non-usaha-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 400px; left: 700px;">
                <x-custom-circle title="USAHA" size="large" style="background-color: #8b6262;margin-top:260px;" 
                    visible_data="false" data_id="usaha-count" data_count="0" 
                    document_url=""
                />
            </div>

            {{-- reklame --}}
            <div style="position: absolute; top: 520px; left: 400px;">
                <x-custom-circle title="REKLAME" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="reklame-count" data_count="0" 
                    visible_data_type="true" data_type="Rp." data_id="reklame-sum"
                    document_url=""
                />
            </div>
            <div class="square dia-top-right-bottom-left" style="top:730px;left:480px;width:250px;height:150px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:750px;left:300px;width:150px;height:100px;"></div>
            <div class="square dia-top-right-bottom-left" style="top:870px;left:300px;width:150px;height:100px;"></div>
            <div style="position: absolute; top: 450px; left: 220px;">
                <x-custom-circle title="SURVEY LAPANGAN" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="survey-lapangan-count" data_count="0"  visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 650px; left: 220px;">
                <x-custom-circle title="PAJAK REKLAME" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="pajak-reklame-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>

            {{-- bapenda --}}
            <div style="position: absolute; top: 600px; left: 700px;">
                <x-custom-circle title="PBB USAHA (BAPENDA)" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="bapenda-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div class="square dia-top-right-bottom-left" style="top:950px;left:650px;width:100px;height:150px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:950px;left:790px;width:100px;height:150px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:930px;left:820px;width:300px;height:180px;"></div>
            <div style="position: absolute; top: 800px; left: 550px;">
                <x-custom-circle title="RESTORAN" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="restoran-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 800px; left: 710px;">
                <x-custom-circle title="HIBURAN" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="hiburan-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 800px; left: 870px;">
                <x-custom-circle title="HOTEL" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="hotel-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 800px; left: 1030px;">
                <x-custom-circle title="PARKIR" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="parkir-count" data_count="0" visible_data_type="true" data_type="0"
                    document_url=""
                />
            </div>

            {{-- disbudpar --}}
            <div class="square dia-top-left-bottom-right" style="top:850px;left:990px;width:100px;height:150px;"></div>
            <div class="square dia-top-left-bottom-right" style="top:700px;left:790px;width:150px;height:100px;"></div>
            <div style="position: absolute; top: 500px; left: 900px;">
                <x-custom-circle title="DISBUDPAR" size="large" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="false" data_id="disbudpar-count" data_count="0" 
                    document_url=""
                />
            </div>
            <div style="position: absolute; top: 680px; left: 1050px;">
                <x-custom-circle title="PARIWISATA" size="small" style="background-color: #627c8b;margin-top:260px;" 
                    visible_data="true" data_id="pariwisata-count" data_count="0" 
                    document_url=""
                />
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script type="module" src="{{ Vite::asset('resources/js/dashboards/potentials/inside_system.js') }}" defer></script>
@endsection

