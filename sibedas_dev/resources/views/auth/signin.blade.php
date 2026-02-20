@extends('layouts.base', ['subtitle' => 'Sign In'])

@section('body-attribuet')
class="authentication-bg"
@endsection

@section('content')
<div class="account-pages py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center">
                            <div class="mx-auto mb-4 text-center auth-logo">
                                <a href="{{ route('dashboard.home') }}" class="logo-dark">
                                    <img src="/images/dputr-kab-bandung.png" height="auto" width="100%" alt="logo dark">
                                </a>

                                <a href="{{ route('dashboard.home') }}" class="logo-light">
                                    <img src="/images/dputr-kab-bandung.png" height="auto" width="100%" alt="logo light">
                                </a>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Selamat Datang!</h4>
                                <p class="text-muted">Masuk kedalam akun untuk melihat lebih lanjut</p>
                        </div>
                        <form method="POST" action="{{ route('login') }}" class="mt-4">

                            @csrf

                            @if (sizeof($errors) > 0)
                            @foreach ($errors->all() as $error)
                            <p class="text-red mb-3">{{ $error }}</p>
                            @endforeach
                            @endif

                            <div class="mb-3">
                                <label for="email" class="form-label">Email </label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-dark btn-lg fw-medium" type="submit">Sign In</button>
                            </div>
                            <div class="d-flex justify-content-start mt-3">
                                <a href="{{ route('search') }}" class="">
                                    Pencarian cepat
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection