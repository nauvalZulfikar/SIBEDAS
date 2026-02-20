@extends('layouts.vertical', ['subtitle' => 'Role'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'Role'])

<x-toast-notification/>
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('roles.index', ['menu_id' => $menuId]) }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
            <div class="card-body">
                <form id="formUpdateRole" action="{{route("api.roles.update", $role->id)}}" method="post" >
                    @csrf
                    @method("put")
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name"
                                class="form-control" placeholder="Enter role name" value="{{$role->name}}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <input type="text" id="description" name="description"
                                class="form-control" placeholder="Enter description" value="{{$role->description}}">
                    </div>
                    <button class="btn btn-primary me-1" type="button" id="btnUpdateRole">
                        <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Update
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
@vite(['resources/js/roles/update.js'])
@endsection
