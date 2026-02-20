@extends('layouts.vertical', ['subtitle' => 'Google Maps'])

@section('css')
<style>
    .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Latar belakang gelap transparan */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.loading-text {
    background: white;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 18px;
    font-weight: bold;
}
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Maps', 'subtitle' => 'Google Maps'])
<!-- Elemen loading -->
<div id="loading" class="loading-overlay">
    <div class="loading-text">Loading data...</div>
</div>

<!-- Peta -->
<div id="map" style="width: 100%; height: 90vh;"></div>
@endsection

@section('scripts')
@vite(['resources/js/maps/maps-kml.js'])
@endsection