@extends('layouts.vertical', ['subtitle' => 'Data'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'PBG'])

<div class="row">
  <div class="d-flex justify-content-end pb-3">
		<a href="{{ route('data-settings.create')}}" class="btn btn-success width-lg">Create</a>
	</div>
  <div>
    <div id="table-request-assignment"></div>
  </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/request-assignment/request-assignment.js'])
@endsection