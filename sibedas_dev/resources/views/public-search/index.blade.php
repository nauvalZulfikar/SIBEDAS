@extends('layouts.base', ['subtitle' => 'Quick Search'])

@section('css')
@vite(['resources/scss/pages/public-search/index.scss'])
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')
<input type="hidden" value="{{ route('public-search-datatable') }}" id="base_url_datatable" />

<div class="qs-wrapper">
  <div class="qs-toolbar d-flex justify-content-between align-items-center pt-4 pb-4">
    <!-- Search Area (no form action) -->
    <div class="qs-search-form d-flex align-items-center">
      <input
        type="text"
        id="search_input"
        class="gsp-input me-2"
        placeholder="Cari data..."
        required
      />
      <button type="button" id="search_button" class="gsp-btn">Cari</button>
    </div>
  </div>

  <div class="qs-header mb-3" id="search-header" style="display: none;">
    <h2>Hasil Pencarian</h2>
    <p>Berikut adalah data hasil pencarian berdasarkan kata kunci yang Anda masukkan.</p>
  </div>

  <div class="qs-table-wrapper" id="table-wrapper" style="display: none;">
    <div class="p-3" id="datatable-public-search"></div>
  </div>

  <div class="qs-empty-state text-center py-5" id="empty-state">
    <div class="empty-icon mb-3">
      <i class="fas fa-search fa-3x text-muted"></i>
    </div>
    <h4 class="text-muted mb-2">Mulai Pencarian</h4>
    <p class="text-muted">Masukkan kata kunci minimal 3 karakter untuk mencari data PBG</p>
  </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/public-search/index.js'])
@endsection
