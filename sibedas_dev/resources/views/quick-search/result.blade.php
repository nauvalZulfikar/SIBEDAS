@extends('layouts.base', ['subtitle' => 'Quick Search'])

@section('css')
@vite(['resources/scss/pages/quick-search/result.scss'])
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')
<input type="hidden" value="{{ route('quick-search-datatable', ['search' => $keyword]) }}" id="base_url_datatable" />

<div class="container qs-wrapper">
  <div class="qs-toolbar d-flex justify-content-between align-items-center pt-4 pb-4">
    <!-- Back Button -->
    <a href="{{ route('search') }}" class="btn btn-light border me-3">
       Kembali
    </a>
    <!-- Search Area (no form action) -->
    <div class="qs-search-form d-flex align-items-center">
      <input
        type="text"
        id="search_input"
        class="gsp-input me-2"
        value="{{ $keyword }}"
        placeholder="Cari data..."
        required
      />
      <button type="button" id="search_button" class="gsp-btn">Cari</button>
    </div>
  </div>

  <div class="qs-header mb-3">
    <h2>Hasil Pencarian: <em>{{ $keyword }}</em></h2>
    <p>Berikut adalah data hasil pencarian berdasarkan kata kunci yang Anda masukkan.</p>
  </div>

  <div class="qs-table-wrapper">
    <div class="p-3" id="datatable-quick-search-result"></div>
  </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/quick-search/result.js'])
@endsection
