@extends('layouts.vertical', ['subtitle' => 'Create User'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Se', 'subtitle' => 'Syncronize'])

<div class="row">
    @if (session('error'))
      <div class="alert alert-danger">
        {{ session('error') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    <div class="columns-md">
      <div class="card">
        <div class="card-body">
          <form action="{{ route('general.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="key" class="form-label">Key</label>
                <input type="text" id="key" class="form-control" name="key" value="{{ $data->key }}" readonly>
            </div>
            <div class="mb-3">
                <label for="value" class="form-label">Value</label>
                <input type="text" id="value" class="form-control" name="value" value="{{ $data->value }}">
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <input type="text" id="type" class="form-control" name="type" value="{{ $data->type }}">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" id="description" class="form-control" name="description" value="{{ $data->description }}">
            </div>
            <button type="submit" class="btn btn-success width-lg">Update</button>
          </form>
        </div>
      </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/tables/common-table.js'])
@endsection