@extends('layouts.vertical', ['subtitle' => 'Menu'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'Menu'])

<x-toast-notification />
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-end">
                <a href="{{ route('menus.index', ['menu_id' => $menuId ?? 0]) }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
            <div class="card-body">
                <form id="formCreateMenus" action="{{route("api.menus.store")}}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name"
                                class="form-control" placeholder="Enter menu name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="url">URL</label>
                        <input type="text" id="url" name="url"
                                class="form-control" placeholder="Enter menu url" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="icon">Icon</label>
                        <input type="text" id="icon" name="icon"
                                class="form-control" placeholder="Enter menu icon" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="parent_id">Parent Menu</label>
                        <select name="parent_id" class="form-control">
                            <option value="">Select parent menu</option>
                            @foreach($parent_menus as $menu)
                                <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order"
                                class="form-control" placeholder="Enter sort order" required>
                    </div>
                    <button class="btn btn-primary me-1" type="button" id="btnCreateMenus">
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
@vite(['resources/js/menus/create.js'])
@endsection