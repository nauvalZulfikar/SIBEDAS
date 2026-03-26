@extends('layouts.vertical', ['subtitle' => 'Pariwisata'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'Pariwisata'])

<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Daftar Pariwisata</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="d-flex justify-content-end gap-10 pb-3">
                @if ($creator)
                    <button class="btn btn-success me-2 width-lg btn-create" data-model="data/tourisms" data-menu="{{ $menuId }}">
                        <i class='bx bxs-file-plus'></i>
                        Create</button>
                    <button class="btn btn-primary width-lg btn-bulk-create" data-model="data/tourisms" data-menu="{{ $menuId }}">
                        <i class='bx bx-upload' ></i>
                        Bulk Create
                    </button>
                @endif
            </div>
            <div>
                <div id="tourisms-data-table"
                    data-updater="{{ $updater }}"
                    data-destroyer="{{ $destroyer }}">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade modalGMaps" tabindex="-1"
    aria-labelledby="confirmationModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg  modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <div id="loading" style="display: none; text-align: center; padding: 20px;">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="map" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/data/tourisms/data-tourisms.js'])
@endsection