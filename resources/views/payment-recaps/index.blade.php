@extends('layouts.vertical', ['subtitle' => 'Rekap Pembayaran'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Laporan', 'subtitle' => 'Rekap Pembayaran'])

<x-toast-notification />

<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Rekap Pembayaran</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-excel" data-url="{{ route('api.payment-recaps.excel') }}">
                        <span>.xlsx</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-pdf" data-url="{{ route('api.payment-recaps.pdf') }}">
                        <span>.pdf</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12 d-flex justify-content-end align-items-center flex-wrap gap-2">
                        <input type="text" id="datepicker-payment-recap" class="form-control w-auto" placeholder="Filter Tanggal" />
                        <button class="btn btn-info btn-sm" id="btnFilterData">Filter</button>
                    </div>
                </div>
                <div id="table-payment-recaps"></div>
            </div>
        </div>
    </div>
</div>


@endsection

@section('scripts')
@vite(['resources/js/payment-recaps/index.js'])
@endsection