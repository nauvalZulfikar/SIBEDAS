@extends('layouts.vertical', ['subtitle' => 'Report Pariwisata'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Report', 'subtitle' => 'Report Pariwisata'])

<input id="tourism_based_KBLI"  type="hidden" value="{{ json_encode($tourismBasedKBLI) }}">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Laporan Pariwisata</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-excel" data-url="{{ route('api.report-tourisms.excel') }}">
                <span>.xlsx</span>
                <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
            </button>
            <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-pdf" data-url="{{ route('api.report-tourisms.pdf') }}">
                <span>.pdf</span>
                <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div>
                <div id="tourisms-report-data-table"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/report/tourisms/index.js'])
@endsection