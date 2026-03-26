@extends('layouts.vertical', ['subtitle' => 'Create User'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Se', 'subtitle' => 'Syncronize'])

<div class="row d-flex justify-content-center">
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
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <form action="{{ route('general.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="key" class="form-label">Key</label>
                <input type="text" id="key" class="form-control" name="key">
            </div>
            <div class="mb-3">
                <label for="value" class="form-label">Value</label>
                <input type="text" id="value" class="form-control" name="value">
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <input type="text" id="type" class="form-control" name="type">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" id="description" class="form-control" name="description">
            </div>
            <button type="submit" class="btn btn-success width-lg">Create</button>
          </form>
        </div>
      </div>
    </div>
</div>

@endsection

@section('scripts')
@endsection