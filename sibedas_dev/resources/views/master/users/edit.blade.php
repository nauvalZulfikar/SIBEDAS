@extends('layouts.vertical', ['subtitle' => 'Users'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Users', 'subtitle' => 'Create'])

<x-toast-notification />
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
<div class="row d-flex justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-end">
                    <a href="{{ route('users.index', ['menu_id' => $menuId]) }}" class="btn btn-sm btn-secondary me-2">Back</a>
                </div>
            </div>
            <div class="card-body">
                <form id="formUpdateUsers" action="{{ route('users.update', $user->id)}}" method="post">
                    @csrf
                    @method("put")
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="name" id="name" name="name"
                                class="form-control" placeholder="Enter your name" value="{{$user->name}}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email"
                                class="form-control" placeholder="Enter your email" value="{{$user->email}}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="firstname">Firstname</label>
                        <input type="text" id="firstname" class="form-control" name="firstname"
                                placeholder="Enter your firstname" value="{{$user->firstname}}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lastname">Lastname</label>
                        <input type="text" id="lastname" class="form-control" name="lastname"
                                placeholder="Enter your lastname" value="{{$user->lastname}}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="position">Position</label>
                        <input type="text" id="position" class="form-control" name="position"
                                placeholder="Enter your position" value="{{$user->position}}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Role</label>
                        <select name="role_id" id="role" class="form-control">
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ $user->roles->contains($role->id) ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary me-1" type="button" id="btnUpdateUsers">
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
@vite(['resources/js/master/users/update.js'])
@endsection