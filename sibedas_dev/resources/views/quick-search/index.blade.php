@extends('layouts.base', ['subtitle' => 'Quick Search'])

@section('css')
@vite(['resources/scss/pages/quick-search/index.scss'])
@endsection

@section('body-attribuet')
class="gsp-body"
@endsection

@section('content')
<div class="position-absolute top-0 end-0 p-3">
    <a href="{{ route('login') }}" class="btn btn-md btn-secondary">
        Login
    </a>
</div>
<div class="container min-vh-100 d-flex justify-content-center align-items-center gsp-body">
    <div class="w-100" style="max-width: 700px;">
        <div class="text-center mb-4">
            <img src="{{ asset('images/simbg-dputr.png') }}" alt="PBG Icon" class="img-fluid gsp-icon mb-3">
            <h1 class="gsp-title">SIBEDAS PBG</h1>
        </div>
        <div class="d-flex flex-column flex-sm-row align-items-stretch gap-2">
            <div class="flex-fill">
                <input type="text" class="gsp-input" id="searchInput" placeholder="Cari..." autocomplete="off" />
            </div>
            <button class="gsp-btn" id="searchBtn">Cari</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/quick-search/index.js'])
@endsection 