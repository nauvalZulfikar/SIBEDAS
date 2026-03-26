@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection
@section('content')

@include('layouts.partials/page-title', ['title' => 'Dasboard', 'subtitle' => 'Dashboard PBG'])


<div class="card mb-3 mb-xl-0">
    <div class="card-title mt-3">
        <div class="d-flex flex-sm-nowrap flex-wrap justify-content-end gap-2 me-3">
            <input type="text" class="form-control mt-2" style="max-width: 125px;" id="datepicker-dashboard-pbg" placeholder="Filter Date" />
        </div>
    </div>
    
    <div class="card-body">
        <div class="row">
            <!-- Card 1 -->
            <div class="col-md-12 col-lg-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Kekurangan Potensi</p>
                                <h4 class="text-dark mt-2 mb-0" id="total-kekurangan-potensi">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:pie-chart-2-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart04"></div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-md-12 col-lg-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Target PAD</p>
                                <h4 class="text-dark mt-2 mb-0" id="target-pad">Loading...</h4>
                            </div>
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:globus-outline"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart01"></div>
                </div>
            </div>
        
            <!-- Card 3 -->
            <div class="col-md-12 col-lg-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Total Potensi Berkas</p>
                                <h4 class="text-dark mt-2 mb-0" id="total-potensi-berkas">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:users-group-two-rounded-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart02"></div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col-md-12 col-lg-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Perkiraan Potensi PBG dari Tata Ruang</p>
                                <h4 class="text-dark mt-2 mb-0" id="total-potensi-pbd-tata-ruang">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:globus-outline"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart01"></div>
                </div>
            </div>
        
        </div>

        <div class="row">
            <!-- Card 1 -->
            <div class="col-md-12 col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Total Berkas Belum Terverifikasi</p>
                                <h4 class="text-dark mt-2 mb-0" id="total-berkas-belum-terverifikasi">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:users-group-two-rounded-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart02"></div>
                </div>
            </div>
            
            <!-- Card 2 -->
            <div class="col-md-12 col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Total Berkas Terverifikasi</p>
                                <h4 class="text-dark mt-2 mb-0" id="total-berkas-terverifikasi">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:cart-5-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart03"></div>
                </div>
            </div>
        </div>

        <div class="row">

            <div class="col-lg-6">
                <div class="card card-height-100">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h4 class="card-title flex-grow-1 mb-0">Berkas Belum Terverifikasi</h4>
                    </div>
        
                    <div class="card-body">
                        <div dir="ltr">
                            <div id="conversions" class="apex-charts"></div>
                        </div>
                        <div class="table-responsive mb-n1 mt-2">
                            <table class="table table-nowrap table-borderless table-sm table-centered mb-0">
                                <thead class="bg-light bg-opacity-50 thead-sm">
                                    <tr>
                                        <th class="py-1">
                                            KETEGORI
                                        </th>
                                        <th class="py-1">NILAI</th>
                                        <th class="py-1">PERSENTASE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>NON USAHA</td>
                                        <td data-category="non-usaha">-</td>
                                        <td data-category="non-usaha-percentage">-</td>
                                    </tr>
                                    <tr>
                                        <td>USAHA</td>
                                        <td data-category="usaha">-</td>
                                        <td data-category="usaha-percentage">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- end table-responsive-->
                    </div>
        
                </div> <!-- end card-->
            </div> <!-- end col -->
        
            <div class="col-lg-6">
                <div class="card">
                    <div
                        class="d-flex card-header justify-content-between align-items-center border-bottom border-dashed">
                        <h4 class="card-title flex-grow-1 mb-0">SEBARAN DATA</h4>
                    </div>
        
                    <div class="card-body pt-0">
                        <div id="map" style="height: 400px; width: 100%;"></div>
                    </div> <!-- end card-body-->
        
        
                </div> <!-- end card-->
            </div> <!-- end col-->
        </div>

        <div class="row">
            <!-- Card 1 -->
            <div class="col-md-12 col-lg-12 col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Realisasi Terbit PBG</p>
                                <h4 class="text-dark mt-2 mb-0" id="realisasi-terbit-pbg">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:users-group-two-rounded-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart02"></div>
                </div>
            </div>
            
            <!-- Card 2 -->
            <div class="col-md-12 col-lg-12 col-xl-4 d-none">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Menunggu Klik DPMPTSP</p>
                                <h4 class="text-dark mt-2 mb-0" id="waiting-click-dpmptsp">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:cart-5-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart03"></div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-md-12 col-lg-12 col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted mb-0 text-truncate">Berproses Di Dinas Teknis</p>
                                <h4 class="text-dark mt-2 mb-0" id="processing-technical-services">Loading...</h4>
                            </div>
        
                            <div class="col-6">
                                <div class="ms-auto avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:cart-5-broken"
                                        class="fs-32 avatar-title text-primary"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="chart03"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-lg-12 col-xl-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Baru Di Update</h4>
                        <a href="{{ route('pbg-task.index') }}" class="btn btn-sm btn-info">
                            View All
                        </a>
                    </div>
                    <div class="card-body h-100">
                        <div id="pbg-filter-by-updated-at" class="h-100"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-lg-12 col-xl-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar SK PBG Terbit</h4>
                        <a href="{{ route('pbg-task.index') }}" class="btn btn-sm btn-info">
                            View All
                        </a>
                    </div>
                    <div class="card-body h-100">
                        <div id="pbg-filter-by-status" class="h-100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end card body -->
</div> <!-- end card -->

@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
@vite(['resources/js/dashboards/pbg.js'])
@endsection