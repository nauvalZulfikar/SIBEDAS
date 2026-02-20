@extends('layouts.vertical', ['subtitle' => 'Data'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'PDAM'])

<x-toast-notification/>

<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-end align-items-center mb-2">
                    @if ($creator)
                        <a href="{{ route('customers.create', ['menu_id' => $menuId]) }}" class="btn btn-success btn-sm d-block d-sm-inline w-auto me-3">Create</a>
                        <a href="{{ route('customers.upload', ['menu_id' => $menuId]) }}" class="btn btn-success btn-sm d-block d-sm-inline w-auto">Upload</a>
                    @endif
                </div>
                <div id="table-customers"
                     data-updater="{{ $updater }}"
                     data-destroyer="{{ $destroyer }}"
                     data-menuId="{{ $menuId }}">
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/customers/index.js'])
@endsection