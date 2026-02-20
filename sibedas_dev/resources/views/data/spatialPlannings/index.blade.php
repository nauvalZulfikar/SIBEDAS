@extends('layouts.vertical', ['subtitle' => 'Rencana Tata Ruang'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'Rencana Tata Ruang'])
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Daftar Rencana Tata Ruang</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="d-flex justify-content-end gap-10 pb-3">
                @if ($creator)
                    <button class="btn btn-success me-2 width-lg btn-create" data-model="data/spatial-plannings" data-menu="{{ $menuId }}">
                        <i class='bx bxs-file-plus'></i>
                        Create</button>
                    <button class="btn btn-primary width-lg btn-bulk-create" data-model="data/spatial-plannings" data-menu="{{ $menuId }}">
                        <i class='bx bx-upload' ></i>
                        Bulk Create
                    </button>
                @endif
            </div>
            <div>
                <div id="spatial-planning-data-table"
                    data-updater="{{ $updater }}"
                    data-destroyer="{{ $destroyer }}">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- <div id="alert-container"></div> --}}

@endsection

@section('scripts')
@vite(['resources/js/data/spatialPlannings/data-spatialPlannings.js'])
@endsection