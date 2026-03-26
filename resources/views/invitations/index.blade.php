@extends('layouts.vertical', ['subtitle' => 'Undangan'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Tools', 'subtitle' => 'Undangan'])

<x-toast-notification />

<div class="container">
    <div class="row justify-content-center">
        <!-- Bagian Kirim Undangan -->
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Kirim Undangan</h5>
                    <label for="email-textarea" class="form-label">Alamat Email</label>
                    <textarea class="form-control mb-3" id="email-textarea" rows="4" placeholder="Masukkan email, pisahkan dengan koma..."></textarea>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-info btn-sm px-4 btn-send-invitations">Kirim</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Tabel Undangan -->
        <div class="col-lg-10 col-md-12 mt-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Log Undangan</h5>
                    <div id="table-invitations"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/invitations/index.js'])
@endsection