@extends('layouts.vertical', ['subtitle' => 'Create'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data Settings', 'subtitle' => 'Setting Dashboard'])

<x-toast-notification />
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-end">
        <a href="{{ route('data-settings.index', ['menu_id' => $menuId]) }}" class="btn btn-sm btn-secondary">Back</a>
      </div>
      <div class="card-body">
        <form id="formDataSettings" action="{{ route('api.data-settings.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label for="key" class="form-label">Key</label>
            <input type="text" id="key" class="form-control" name="key">
          </div>
          <div class="mb-3">
            <label for="value" class="form-label">Value</label>
            <input type="text" id="value" class="form-control" name="value">
          </div>
          <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <input type="text" id="type" class="form-control" name="type">
          </div>
          <button class="btn btn-primary me-1" type="button" id="btnCreateDataSettings">
              <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
              Create
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/data-settings/create.js'])
@endsection