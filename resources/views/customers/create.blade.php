@extends('layouts.vertical', ['subtitle' => 'Data'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'PDAM'])

<x-toast-notification />
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('customers', ['menu_id' => $menuId]) }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
            <div class="card-body">
                <form id="formCreateCustomer" action="{{ route('api.customers.store') }}" method="post">
                    @csrf
                    @include('customers.form')
                    <button type="button" class="btn btn-primary" id="btnCreateCustomer">
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
@vite(['resources/js/customers/create.js'])
@endsection
