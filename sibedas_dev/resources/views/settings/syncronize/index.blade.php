@extends('layouts.vertical', ['subtitle' => 'Syncronize'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'Syncronize'])
<x-toast-notification />
<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-end gap-2">
                    @if ($creator)
                        <button type="button" class="btn btn-success btn-sm d-block d-sm-inline w-auto" id="btn-sync-submit">
                            <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            Sync SIMBG
                        </button>
                    @endif
                </div>
                <div>
                    <div id="table-import-datasources"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/settings/syncronize/syncronize.js'])
@endsection