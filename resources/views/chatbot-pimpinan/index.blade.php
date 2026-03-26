@extends('layouts.vertical', ['subtitle' => 'Main Chatbot'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
<style>
#user-message {
    height: 60px; /* Menambah tinggi textarea */
    font-size: 1.1rem; /* Memperbesar font */
    padding: 10px; /* Menambah ruang di dalam textarea */
    resize: none; /* Mencegah resize manual */
}

.loader {
  width: 10px;
  aspect-ratio: 1;
  border-radius: 50%;
  animation: l5 1s infinite linear alternate;
}

@keyframes l5 {
    0%  {box-shadow: 10px 0 #000, -10px 0 #0002; background: #000 }
    33% {box-shadow: 10px 0 #000, -10px 0 #0002; background: #0002}
    66% {box-shadow: 10px 0 #0002, -10px 0 #000; background: #0002}
    100%{box-shadow: 10px 0 #0002, -10px 0 #000; background: #000 }
}
</style>
@endsection

@section('content')
@include('layouts.partials/page-title', ['title' => 'Main Chatbot', 'subtitle' => 'Main Chatbot'])

<div class="card">
    <div class="card-body d-flex flex-column" style="height: 700px;">
        <!-- Conversation Area -->

        <!-- Bot Response -->
        <div class="row flex-grow overflow-auto align-items-start">
            <!-- Avatar -->
            <div class="col-auto alignpe-0">
                <img class="rounded-circle" width="45" src="/images/iconchatbot.jpeg" alt="avatar-3">
            </div>

            <!-- Nama dan Bubble Chat -->
            <div class="col-9 w-auto">
                <!-- Nama Bot -->
                <p class="fw-bolder mb-1">Neng Bedas</p>

                <!-- Bubble Chat -->
                <div class="bot-response p-2 bg-light rounded mb-2 d-inline-block">
                    <p class="mb-0">Halo! Ada yang bisa saya bantu?</p>

                    <!-- Waktu (Tetap di Dalam Bubble Chat) -->
                    <div class="sending-message-time text-end mt-1">
                        <p class="text-muted small mb-0">Now</p>
                    </div>
                </div>
            </div>
        </div>

    
        <!-- Input & Button (Selalu di Bawah) -->
        <div class="row mt-auto">
            <div class="col-xl-12 d-flex align-items-end gap-1">
                <textarea class="form-control" id="user-message"></textarea>
                <button id="send" class="btn btn-primary btn-lg h-100 d-flex align-items-center">
                    <i class='bx bx-send'></i>
                </button>
            </div>
        </div>
    </div>    
</div>

@endsection

@section('scripts')
@vite(['resources/js/chatbot-pimpinan/index.js'])
@endsection