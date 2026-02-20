@extends('layouts.vertical', ['subtitle' => 'Approval'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Approval', 'subtitle' => 'Approval Pejabat'])

<x-toast-notification />

<div class="row">
  <div class="col-12">
    <div class="card w-100">
      <div class="card-body">
        <div id="table-approvals"></div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/approval/index.js'])
@endsection