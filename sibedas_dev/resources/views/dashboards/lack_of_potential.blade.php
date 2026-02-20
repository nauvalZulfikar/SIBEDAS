@extends('layouts.vertical', ['subtitle' => 'Dashboards'])

@section('css')
@vite(['resources/scss/dashboards/_lack-of-potential.scss'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Dashboards', 'subtitle' => 'Lack Of Potential'])

<div class="lack-of-potential-wrapper">
    <div class="row" id="lack-of-potential-wrapper">
        <div class="d-flex justify-content-between align-items-center mt-3 ms-2">
            <h2 class="text-danger m-0">
                ANALISA BIG DATA MELALUI APLIKASI SIBEDAS PBG
            </h2>
            <div class="text-black text-end d-flex flex-column align-items-end me-3">
                <input type="text" class="form-control mt-2" style="max-width: 125px;" id="datepicker-lack-of-potential" placeholder="Filter Date" />
            </div>
        </div>
    </div>
    <div class="wrapper">
        <div id="lack-of-potential-fixed-container" class="" style="width:1400px;height:770px;position:relative;margin:auto;z-index:1;">
            <div style="position: absolute; top: 200px; left: 50px;">
                <x-custom-circle title="Restoran" size="small" style="background-color: #0e4753;" visible_data="true" data_id="restoran-count" data_count="0" />
                <div class="square dia-top-left-bottom-right" style="top:30px;left:50px;width:150px;height:120px;"></div>
                <x-custom-circle title="PBB Bangunan" visible_data="true" data_id="pbb-bangunan-count" data_count="0" size="small" style="background-color: #0e4753;" />
                <div class="square" style="width:150px;height:2px;background-color:black;left:50px;top:150px;"></div>
                <x-custom-circle title="Reklame" visible_data="true" data_id="reklame-count" data_count="0" size="small" style="background-color: #0e4753;" />
                <div class="square dia-top-right-bottom-left" style="top:140px;left:50px;width:150px;height:120px;"></div>
            </div>
            
            <div style="position: absolute; top: 300px; left: 200px;">
                <div class="square dia-top-right-bottom-left" style="top:-100px;left:30px;width:150px;height:120px;"></div>
                <div class="square dia-top-left-bottom-right" style="top:-100px;left:120px;width:120px;height:120px;"></div>
                <x-custom-circle title="BAPENDA" size="small" style="float:left;background-color: #234f6c;" />
                <x-custom-circle title="PDAM" visible_data="true" data_id="pdam-count" data_count="0" visible_data_type="true" data_type="Pelanggan" size="small" style="float:left;background-color: #234f6c;" />
                <x-custom-circle title="KECAMATAN" size="small" style="float:left;background-color: #234f6c;" />
                
            </div>
            
            <div  style="position: absolute; top: 0px; left: 270px;">
                <div class="square" style="width:5px;height:600px;background-color:black;left:70px;top:50px;"></div>
                <div class="square dia-top-left-bottom-right" style="top:350px;left:-50px;width:120px;height:120px;"></div>
                <div class="square dia-top-right-bottom-left" style="top:350px;left:70px;width:120px;height:120px;"></div>
                <x-custom-circle title="Rumah Tinggal" size="small" style="background-color: #234f6c;margin:auto;" />
                <x-custom-circle title="Non Usaha" size="large" style="background-color: #3a968b;margin-top:20px;" />
                <x-custom-circle title="USAHA" size="large" style="background-color: #627c8b;margin-top:150px;" />
            </div>
            
            <div  style="position: absolute; top: 650px; left: 110px;">
                <div class="square dia-top-right-bottom-left" style="top:-110px;left:40px;width:200px;height:120px;"></div>
                <div class="square dia-top-right-bottom-left" style="top:-110px;left:90px;width:150px;height:170px;"></div>
                <div class="square dia-top-left-bottom-right" style="top:-110px;left:230px;width:150px;height:170px;"></div>
                <div class="square dia-top-left-bottom-right" style="top:-110px;left:260px;width:200px;height:180px;"></div>
                <x-custom-circle title="Villa" size="small" style="float:left;background-color: #234f6c;" visible_data="true" data_id="villa-count" data_count="0" />
                <x-custom-circle title="Pabrik" size="small" style="float:left;background-color: #234f6c;" />
                <x-custom-circle title="Jalan Protocol" size="small" style="float:left;background-color: #234f6c;" />
                <x-custom-circle title="Ruko" size="small" style="float:left;background-color: #234f6c;" />
                <x-custom-circle title="Pariwisata" size="small" style="float:left;background-color: #234f6c; margin-right: 20px;" visible_data="true" data_id="pariwisata-count" data_count="0" />
                <div class="square" style="width:150px;height:2px;background-color:black;left:350px;top:50px;"></div>
                <x-custom-circle title="DISBUDPAR" size="small" style="background-color: #3a968b;" />
            </div>
            
            <div style="position: absolute; top: 280px; left: 550px;">
                <div class="square dia-top-left-bottom-right" style="top:-110px;left:-150px;width:200px;height:180px;"></div>
                <div class="square dia-top-right-bottom-left" style="top:70px;left:-150px;width:200px;height:130px;"></div>
                <x-custom-circle title="Tim Wasdal Gabungan" size="large" style="background-color: #da6635;float:left" />
                <div class="square" style="width:650px;height:5px;background-color:black;left:100px;top:75px;"></div>
                @component('components.circle', [
                    'document_title' => 'Kekurangan Potensi',
                    'document_color' => '#ff5757',
                    'document_type' => '',
                    'document_id' => 'chart-lack-of-potential',
                    'visible_small_circle' => false,
                    'style' => 'margin-left:180px;top:-20px;'
                    ])
                @endcomponent
                <x-custom-circle title="Tata Ruang" size="large" style="background-color: #da6635;float:left;margin-left:250px;" visible_data="true" data_id="tata-ruang-count" data_count="0" />
            </div>
                
            <div style="position: absolute; top: 310px; left: 1150px;">
                <div class="square dia-top-left-bottom-right" style="top:90px;left:-100px;width:100px;height:100px;"></div>
                <div class="square dia-top-right-bottom-left" style="top:-110px;left:-100px;width:100px;height:100px;"></div>
                <x-custom-circle title="Peta" visible_data_type="true" data_type="1:5000" size="small" style="background-color: #224f6d;float:left;" />
                <x-custom-circle title="Tapak Bangunan" size="small" style="background-color: #2390af;float:left;margin-left:20px;" />
            </div>
                
            <x-custom-circle title="BPN" size="small" style="background-color: #2390af;position:absolute;left:1270px;top:440px;" />
                
            <div style="position: absolute; top: 470px; left: 430px;">
                <div class="square dia-top-right-bottom-left" style="top:-80px;left:20px;width:150px;height:120px;"></div>
                <div class="square dia-top-right-bottom-left" style="top:-50px;left:100px;width:100px;height:100px;"></div>
                <div class="square dia-top-left-bottom-right" style="top:-50px;left:180px;width:100px;height:100px;"></div>
                <div class="square dia-top-left-bottom-right" style="top:-60px;left:240px;width:120px;height:120px;"></div>
                <x-custom-circle title="UPT Wasdal" size="small" style="background-color: #0f4853;float:left;" />
                <x-custom-circle title="Satpol PP" size="small" style="background-color: #0f4853;float:left;" />
                <x-custom-circle title="KEJARI" size="small" style="background-color: #0f4853;float:left;" />
                <x-custom-circle title="TNI & POLRI" size="small" style="background-color: #0f4853;float:left;" />
            </div>

            <x-custom-circle title="UUCK" size="small" style="background-color: #2390af;position:absolute;left:980px;top:500px;" />

            <div  style="position: absolute; top: 50px; left: 1100px;">
                <x-custom-circle title="Non Usaha" size="large" style="background-color: #3a968b;margin-top:20px;" />
                <x-custom-circle title="USAHA" size="large" style="background-color: #627c8b;margin-top:260px;" visible_data="true" data_id="tata-ruang-usaha-count" data_count="0" />
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/dashboards/lack-of-potential.js'])
@endsection

