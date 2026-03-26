@extends('layouts.vertical', ['subtitle' => 'Users'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Users', 'subtitle' => 'Create'])

<div class="row d-flex justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="user-name">Name</label>
                    <input type="name" id="user-name" name="user-name"
                            class="form-control" placeholder="Enter your name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-email">Email</label>
                    <input type="email" id="user-email" name="user-email"
                            class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-password">Password</label>
                    <input type="text" id="user-password" class="form-control"
                            placeholder="Enter your password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-firstname">Firstname</label>
                    <input type="text" id="user-firstname" class="form-control"
                            placeholder="Enter your firstname" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-lastname">Lastname</label>
                    <input type="text" id="user-lastname" class="form-control"
                            placeholder="Enter your lastname" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-position">Position</label>
                    <input type="text" id="user-position" class="form-control"
                            placeholder="Enter your position" required>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@endsection