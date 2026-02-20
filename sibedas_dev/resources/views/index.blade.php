@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Darkone', 'subtitle' => 'Dashboard PBG'])

<div class="row">
    <!-- Card 1 -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="text-muted mb-0 text-truncate">Target PAD</p>
                        <h4 class="text-dark mt-2 mb-0">33.200.000.000</h4>
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

    <!-- Card 2 -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="text-muted mb-0 text-truncate">Total Potensi Berkas</p>
                        <h4 class="text-dark mt-2 mb-0">10.080.738.057</h4>
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

    <!-- Card 3 -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="text-muted mb-0 text-truncate">Total Berkas Terverifikasi</p>
                        <h4 class="text-dark mt-2 mb-0">1.555.064.633</h4>
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

    <!-- Card 4 -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="text-muted mb-0 text-truncate">Kekurangan Potensi</p>
                        <h4 class="text-dark mt-2 mb-0">23.113.261.943</h4>
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
</div>



<div class="row">
    <div class="col-lg-4">
        <div class="card card-height-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h4 class=" mb-0 flex-grow-1 mb-0">Pemenuhan Target PAD</h4>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-light">ALL</button>
                    <button type="button" class="btn btn-sm btn-outline-light">1M</button>
                    <button type="button" class="btn btn-sm btn-outline-light">6M</button>
                    <button type="button" class="btn btn-sm btn-outline-light active">1Y</button>
                </div>
            </div>

            <div class="card-body pt-0">
                <div dir="ltr">
                    <div id="dash-performance-chart" class="apex-charts"></div>
                </div>
            </div>

        </div> <!-- end card-->
    </div> <!-- end col -->

    <div class="col-lg-4">
        <div class="card card-height-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h4 class="card-title flex-grow-1 mb-0">Berkas Belum Lengkap</h4>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-light">ALL</button>
                    <button type="button" class="btn btn-sm btn-outline-light">1M</button>
                    <button type="button" class="btn btn-sm btn-outline-light">6M</button>
                    <button type="button" class="btn btn-sm btn-outline-light active">1Y</button>
                </div>
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
                                <td>187,232</td>
                                <td>
                                    48.63%
                                </td>
                            </tr>
                            <tr>
                                <td>USAHA</td>
                                <td>126,874</td>
                                <td>
                                    36.08%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- end table-responsive-->
            </div>

        </div> <!-- end card-->
    </div> <!-- end col -->

    <div class="col-lg-4">
        <div class="card">
            <div
                class="d-flex card-header justify-content-between align-items-center border-bottom border-dashed">
                <h4 class="card-title mb-0">SEBARAN DATA</h4>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        View Data
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item">Download</a>
                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item">Export</a>
                        <!-- item-->
                        <a href="javascript:void(0);" class="dropdown-item">Import</a>
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                <div id="world-map-markers" class="mt-3" style="height: 309px">
                </div>
            </div> <!-- end card-body-->


        </div> <!-- end card-->
    </div> <!-- end col-->

</div> <!-- End row -->

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Baru Di Update</h4>
                <a href="#!" class="btn btn-sm btn-light">
                    View All
                </a>
            </div>
            <!-- end card-header-->

            <div class="card-body pb-1">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-centered">
                        <thead>
                            <th class="py-1">No. Registrasi</th>
                            <th class="py-1">Tanggal</th>
                            <th class="py-1">Pemohon</th>
                            <th class="py-1">Status</th>
                            <th class="py-1">Pemilik</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#US523</td>
                                <td>24 April, 2024</td>
                                <td>
                                    <img src="/images/users/avatar-2.jpg" alt="avatar-2"
                                        class="img-fluid avatar-xs rounded-circle" />
                                    <span class="align-middle ms-1">Dan Adrick</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success">Verified</span>
                                </td>
                                <td>@omions</td>
                            </tr>
                            <tr>
                                <td>#US652</td>
                                <td>24 April, 2024</td>
                                <td>
                                    <img src="/images/users/avatar-3.jpg" alt="avatar-2"
                                        class="img-fluid avatar-xs rounded-circle" />
                                    <span class="align-middle ms-1">Daniel Olsen</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success">Verified</span>
                                </td>
                                <td>@alliates</td>
                            </tr>
                            <tr>
                                <td>#US862</td>
                                <td>20 April, 2024</td>
                                <td>
                                    <img src="/images/users/avatar-4.jpg" alt="avatar-2"
                                        class="img-fluid avatar-xs rounded-circle" />
                                    <span class="align-middle ms-1">Jack Roldan</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-warning">Pending</span>
                                </td>
                                <td>@griys</td>
                            </tr>
                            <tr>
                                <td>#US756</td>
                                <td>18 April, 2024</td>
                                <td>
                                    <img src="/images/users/avatar-5.jpg" alt="avatar-2"
                                        class="img-fluid avatar-xs rounded-circle" />
                                    <span class="align-middle ms-1">Betty Cox</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success">Verified</span>
                                </td>
                                <td>@reffon</td>
                            </tr>
                            <tr>
                                <td>#US420</td>
                                <td>18 April, 2024</td>
                                <td>
                                    <img src="/images/users/avatar-6.jpg" alt="avatar-2"
                                        class="img-fluid avatar-xs rounded-circle" />
                                    <span class="align-middle ms-1">Carlos
                                        Johnson</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-danger">Blocked</span>
                                </td>
                                <td>@bebo</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end card body -->
        </div>
        <!-- end card-->
    </div>
    <!-- end col -->

    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    Pending Lama
                </h4>

                <a href="#!" class="btn btn-sm btn-light">
                    View All
                </a>
            </div>
            <!-- end card-header-->

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-centered">
                        <thead>
                            <th class="py-1">No. Registrasi</th>
                            <th class="py-1">Tanggal</th>
                            <th class="py-1">Retribusi</th>
                            <th class="py-1">Status</th>
                            <th class="py-1">
                                Catatan
                            </th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#98521</td>
                                <td>24 April, 2024</td>
                                <td>9.000.120.55</td>
                                <td>
                                    <span class="badge bg-success">Cr</span>
                                </td>
                                <td>Commisions</td>
                            </tr>
                            <tr>
                                <td>#20158</td>
                                <td>24 April, 2024</td>
                                <td>6.000.009.68</td>
                                <td>
                                    <span class="badge bg-success">Cr</span>
                                </td>
                                <td>Affiliates</td>
                            </tr>
                            <tr>
                                <td>#36589</td>
                                <td>20 April, 2024</td>
                                <td>700.105.22</td>
                                <td>
                                    <span class="badge bg-danger">Dr</span>
                                </td>
                                <td>Grocery</td>
                            </tr>
                            <tr>
                                <td>#95362</td>
                                <td>18 April, 2024</td>
                                <td>3.236.580.59</td>
                                <td>
                                    <span class="badge bg-success">Cr</span>
                                </td>
                                <td>Refunds</td>
                            </tr>
                            <tr>
                                <td>#75214</td>
                                <td>18 April, 2024</td>
                                <td>555.750.95</td>
                                <td>
                                    <span class="badge bg-danger">Dr</span>
                                </td>
                                <td>Bill Payments</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end card body -->
        </div>
        <!-- end card-->
    </div>
    <!-- end col -->
</div>
<!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/pages/dashboard.js'])
@endsection