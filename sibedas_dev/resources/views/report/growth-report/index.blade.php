@extends('layouts.vertical', ['subtitle' => 'Laporan Pertumbuhan'])

@section('css')
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Laporan', 'subtitle' => 'Laporan Pertumbuhan'])

<div class="card">
    <div class="card-body">
        <div class="row">
            <div id="chart-growth-report" data-url="{{ route('api.growth') }}"></div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/report/growth-report/index.js'])
@endsection