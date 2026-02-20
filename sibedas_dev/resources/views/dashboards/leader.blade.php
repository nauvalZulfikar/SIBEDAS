@extends('layouts.vertical', ['subtitle' => 'Dashboards'])

@section('css')
@vite(['resources/scss/dashboards/_bigdata.scss'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Dashboards', 'subtitle' => 'Dashboard Pimpinan'])

<div id="dashboard-fixed-wrapper" class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mt-3 ms-2">
            <h2 class="text-danger m-0">
                ANALISA BIG DATA PROSES PBG <br> 
                MELALUI APLIKASI SIBEDAS PBG (LEADER)
            </h2>
            <div class="text-black text-end d-flex flex-column align-items-end me-3">
                <span class="fs-5">Terakhir di update - {{$latest_created}}</span>
                <input type="text" class="form-control mt-2" style="max-width: 125px;" id="datepicker-dashboard-bigdata" placeholder="Filter Date" />
            </div>
        </div>
    </div>
    <div id="dashboard-fixed-container" class="row" style="width:1110px;height:770px;position:relative;margin:auto;">
        @component('components.circle', [
            'document_title' => 'Kekurangan Potensi',
            'document_color' => '#ff5757',
            'document_type' => '',
            'document_id' => 'chart-kekurangan-potensi',
            'visible_small_circle' => true,
            'style' => 'top:150px;'
        ])
        @endcomponent

        @component('components.circle', [
            'document_title' => 'Target PAD',
            'document_color' => '#204f6b',
            'document_type' => '',
            'document_id' => 'chart-target-pad',
            'visible_small_circle' => true,
            'style' => 'left:200px;',
            'document_url' => route('data-settings.index', ['menu_id' => $menus->where('url','data-settings.index')->first()->id])
        ])
        @endcomponent

         <div class="square dia-top-left-bottom-right" style="top:150px;left:350px;">
        </div>

        <div class="square dia-top-right-bottom-left" style="top:150px;left:150px;">
        </div>



        @component('components.circle', [
            'document_title' => 'Total Potensi Berkas',
            'document_color' => '#0097b3',
            'document_type' => 'Pemohon',
            'document_id' => 'chart-total-potensi',
            'visible_small_circle' => true,
            'style' => 'left:400px;top:150px;',
            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'all'])
        ])
        @endcomponent

        <div class="square dia-top-right-bottom-left" style="top:300px;left:350px;">
        </div>

        <div class="square dia-top-left-bottom-right" style="top:300px;left:550px;">
        </div>

        @component('components.circle', [
            'document_title' => 'Perkiraan Potensi PBG Dari Tata Ruang',
            'document_color' => '#ed9d2e',
            'document_type' => '',
            'document_id' => 'chart-potensi-tata-ruang',
            'visible_small_circle' => true,
            'style' => 'left:600px;',
            'document_url' => route('web-spatial-plannings.index', ['menu_id' => $menus->where('url','web-spatial-plannings.index')->first()->id])
        ])
        @endcomponent

        <div class="square dia-top-right-bottom-left" style="top:150px;left:550px;">
        </div>

        @component('components.circle', [
            'document_title' => 'Non Usaha',
            'document_color' => '#399787',
            'document_type' => 'Berkas',
            'document_id' => 'chart-non-business',
            'visible_small_circle' => true,
            'style' => 'left:900px;top:150px;',
            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'non-business'])
        ])
        @endcomponent

        @component('components.circle', [
            'document_title' => 'Usaha',
            'document_color' => '#5e7c89',
            'document_type' => 'Berkas',
            'document_id' => 'chart-business',
            'visible_small_circle' => true,
            'style' => 'left:900px;top:400px;',
            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'business'])
        ])
        @endcomponent

        @component('components.circle', [
            'document_title' => 'Berkas Terverifikasi',
            'document_color' => '#5170ff',
            'document_type' => 'Berkas',
            'document_id' => 'chart-berkas-terverifikasi',
            'visible_small_circle' => true,
            'style' => 'top:300px;left:200px;',
            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'verified'])
        ])
        @endcomponent

        <div class="square dia-top-right-bottom-left" style="top:500px;left:200px;width:50px">
        </div>

        <div class="square dia-top-left-bottom-right" style="top:450px;left:350px;width:500px;height:200px;">
        </div>

        @component('components.circle', [
            'document_title' => 'Berkas Belum Terverifikasi',
            'document_color' => '#5170ff',
            'document_type' => 'Berkas',
            'document_id' => 'chart-berkas-belum-terverifikasi',
            'visible_small_circle' => true,
            'style' => 'top:300px;left:600px;',
            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'non-verified'])
        ])
        @endcomponent

        <div class="square dia-top-right-bottom-left" style="top:200px;left:750px;width:250px;height:150px;">
        </div>

        <div class="square dia-top-left-bottom-right" style="top:400px;left:750px;width:250px;height:150px;">
        </div>


        @component('components.circle',[
            'document_title' => 'Realisasi Terbit PBG',
            'document_color' => '#8cc540',
            'document_type' => 'Berkas',
            'document_id' => 'chart-realisasi-tebit-pbg',
            'visible_small_circle' => true,
            'style' => 'top:550px;left:100px;',
            'document_url' => 'https://docs.google.com/spreadsheets/d/1QoXzuLdEX3MK70Yrfigz0Qj5rAt4T819jX85vubBNdY/edit?gid=1514195399#gid=1514195399'
            ])
        @endcomponent

        <div class="square" style="top:650px;left:200px;width:250px;height:2px;background-color:black;">
        </div>

        @component('components.circle',[
            'document_title' => 'Menunggu Klik DPMPTSP',
            'document_color' => '#00bf61',
            'document_type' => 'Berkas',
            'document_id' => 'chart-menunggu-klik-dpmptsp',
            'visible_small_circle' => true,
            'style' => 'top:550px;left:400px',
            'document_url' => 'https://docs.google.com/spreadsheets/d/1QoXzuLdEX3MK70Yrfigz0Qj5rAt4T819jX85vubBNdY/edit?gid=1514195399#gid=1514195399'
            ])
        @endcomponent

        <div class="square" style="top:650px;left:600px;width:250px;height:2px;background-color:black;">
        </div>

        @component('components.circle',[
            'document_title' => 'Berproses Di Dinas Teknis',
            'document_color' => '#737373',
            'document_type' => 'Berkas',
            'document_id' => 'chart-proses-dinas-teknis',
            'visible_small_circle' => true,
            'style' => 'top:550px;left:700px',
            'document_url' => 'https://docs.google.com/spreadsheets/d/1QoXzuLdEX3MK70Yrfigz0Qj5rAt4T819jX85vubBNdY/edit?gid=1514195399#gid=1514195399'
            ])
        @endcomponent
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/dashboards/leader.js'])
@endsection