@extends('layouts.vertical', ['subtitle' => 'General'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'General'])

<div class="row">
  <div class="card w-full">
    <div class="card-body">
      <div class="d-flex justify-content-end pb-3">
        <a href="{{ route('general.create') }}" class="btn btn-success width-lg">Create</a>
      </div>
      <div id="general-setting-table"></div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/settings/general/general-settings.js'])
@endsection