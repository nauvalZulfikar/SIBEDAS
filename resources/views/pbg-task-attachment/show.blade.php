@extends('layouts.vertical', ['subtitle' => $title])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Data', 'subtitle' => 'PBG'])

<div class="row mb-4">
  <div class="col-sm-12">
    <div class="card border shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">{{ $title }}</h5>
        <p><strong>Document Number:</strong> {{ $pbg->document_number }}</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <div class="card border shadow-sm">
      <div class="card-body">
        @php
            $extension = strtolower(pathinfo($data->file_name, PATHINFO_EXTENSION));
        @endphp

        @if (in_array($extension, ['jpg', 'jpeg', 'png']))
            <div class="text-center">
              <img 
                src="{{ asset('storage/' . $data->file_path) }}" 
                alt="{{ $data->file_name }}" 
                class="img-fluid border rounded" 
                style="max-height: 600px;"
              >
            </div>
        @elseif ($extension === 'pdf')
            <iframe 
              src="{{ asset('storage/' . $data->file_path) }}" 
              width="100%" 
              height="700px" 
              style="border: none;"
            ></iframe>
        @else
            <div class="alert alert-warning">
              Unsupported file type: <strong>{{ $extension }}</strong>
            </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
