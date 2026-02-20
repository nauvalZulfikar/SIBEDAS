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
                <form id="formCreateUsers" action="{{route('api.users.store')}}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input type="name" id="name" name="name"
                                class="form-control" placeholder="Enter your name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email"
                                class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" class="form-control" name="password"
                                placeholder="Enter your password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Password Confirmation</label>
                        <input type="password" id="password_confirmation" class="form-control" name="password_confirmation"
                                placeholder="Enter your password confirmation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="firstname">Firstname</label>
                        <input type="text" id="firstname" class="form-control" name="firstname"
                                placeholder="Enter your firstname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lastname">Lastname</label>
                        <input type="text" id="lastname" class="form-control" name="lastname"
                                placeholder="Enter your lastname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="position">Position</label>
                        <input type="text" id="position" class="form-control" name="position"
                                placeholder="Enter your position" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Role</label>
                        <select name="role_id" id="role" class="form-control">
                            <option value="">Select Role</option>
                            @foreach ($roles as $role)
                                <option value="{{$role->id}}">{{$role->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary me-1" type="button" id="btnCreateUsers">
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
@vite(['resources/js/master/users/create.js'])
@endsection