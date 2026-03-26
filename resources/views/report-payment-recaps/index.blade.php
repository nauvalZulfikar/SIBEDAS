@extends('layouts.vertical', ['subtitle' => 'Laporan Rekap Data Pembayaran'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Laporan', 'subtitle' => 'Lap Rekap Data Pembayaran'])

<x-toast-notification />

<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Laporan Rekap Data Pembayaran</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-excel" data-url="{{ route('api.district-payment-report.excel') }}">
                        <span>.xlsx</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-pdf" data-url="{{ route('api.district-payment-report.pdf') }}">
                        <span>.pdf</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="table-report-payment-recaps"></div>
            </div>
        </div>
    </div>
</div>


@endsection

@section('scripts')
@vite(['resources/js/report-payment-recaps/index.js'])
@endsection