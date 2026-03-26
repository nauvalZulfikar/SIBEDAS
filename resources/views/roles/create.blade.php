@extends('layouts.vertical', ['subtitle' => 'Role'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'Role'])

<x-toast-notification />
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('roles.index', ['menu_id' => $menuId]) }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
            <div class="card-body">
                <form action="{{route("api.roles.store")}}" method="post" id="formCreateRole" data-redirect="{{route("roles.index")}}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name"
                                class="form-control" placeholder="Enter role name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <input type="text" id="description" name="description"
                                class="form-control" placeholder="Enter description">
                    </div>
                    <button class="btn btn-primary me-1" type="button" id="btnCreateRole">
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
@vite(['resources/js/roles/create.js'])
@endsection
