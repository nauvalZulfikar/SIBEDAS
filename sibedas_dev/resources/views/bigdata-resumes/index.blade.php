@extends('layouts.vertical', ['subtitle' => 'Laporan Pimpinan'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Laporan', 'subtitle' => 'Laporan Pimpinan'])

<x-toast-notification />

<div class="row">
    <div class="col-12">
        <div class="card w-100 h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Laporan Pimpinan</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-excel" data-url="{{ route('api.report-director.excel') }}">
                        <span>.xlsx</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-pdf" data-url="{{ route('api.report-director.pdf') }}">
                        <span>.pdf</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="table-bigdata-resumes"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/bigdata-resumes/index.js'])
@endsection