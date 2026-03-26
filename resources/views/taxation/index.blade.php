@extends('layouts.vertical', ['subtitle' => 'Pajak'])
@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection
@section('content')

@include('layouts.partials/page-title', ['title' => 'Pajak', 'subtitle' => 'Data Pajak'])

<x-toast-notification />

<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-header d-flex justify-content-end align-items-center">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm bg-black text-white d-flex align-items-center content-center gap-2" id="btn-export-excel" data-url="{{ route('api.taxs.export', ['menu_id' => $menuId]) }}">
                        <span>.xlsx</span>
                        <iconify-icon icon="mingcute:file-export-line" width="20" height="20" class="d-flex align-items-center"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-end align-items-center mb-2">
                    @if ($creator)
                        <a href="{{ route('taxation.upload', ['menu_id' => $menuId]) }}" class="btn btn-primary btn-sm d-block d-sm-inline w-auto">Upload</a>
                    @endif
                </div>
                <div>
                    <div id="table-taxation" data-updater="{{ $updater }}"
                    data-destroyer="{{ $destroyer }}"
                    data-menuId="{{ $menuId }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/taxation/index.js'])
@endsection