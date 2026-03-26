@extends('layouts.vertical', ['subtitle' => 'TPA TPT'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'TPA TPT'])

<x-toast-notification />

<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-body">
                <div id="table-tpa-tpt"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/tpa-tpt/index.js'])
@endsection