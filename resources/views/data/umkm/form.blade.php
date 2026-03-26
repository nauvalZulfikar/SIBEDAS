@extends('layouts.vertical', ['subtitle' => $subtitle]) <!-- Menggunakan subtitle dari controller -->

@section('content')

@include('layouts.partials/page-title', ['title' => $title, 'subtitle' => $subtitle]) <!-- Menggunakan title dan subtitle dari controller -->
<input type="hidden" id="menuId" value="{{ $menuId ?? 0 }}">
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

    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-header">
                <button class="btn btn-danger float-end btn-back">
                    <i class='bx bx-arrow-back'></i>
                    Back
                </button>
            </div>
            <div class="card-body">
                <form id="create-update-form" action="{{ isset($modelInstance) && $modelInstance->id ? $apiUrl . '/' . $modelInstance->id : $apiUrl }}" method="POST">
                    @csrf
                    @if(isset($modelInstance))
                        @method('PUT')
                    @endif

                    <div class="row">
                        @foreach($fields as $field => $label)
                            <div class="col-md-6 form-group mb-3">
                                <label for="{{ $field }}">{{ $label }}</label>
                                @php
                                    $fieldType = $fieldTypes[$field] ?? 'text'; // Default text jika tidak ditemukan tipe
                                @endphp

                                @if($fieldType == 'textarea')
                                    <textarea id="{{ $field }}" name="{{ $field }}" class="form-control">{{ old($field, isset($modelInstance) ? $modelInstance->{$field} : '') }}</textarea>
                                @elseif($fieldType == 'select' && isset($dropdownOptions[$field]))
                                    <select id="{{ $field }}" name="{{ $field }}" class="form-control">
                                        @foreach($dropdownOptions[$field] as $code => $name)
                                            @php
                                                // $selectedValue = old($field, $modelInstance->{$field});
                                                $selectedValue = old($field, $modelInstance->$field ?? '');
                                                $isSelected = strval($selectedValue) === strval($code);
                                            @endphp
                                            <option value="{{ $code }}" class="{{ $isSelected ? 'selected' : '' }}" {{ $isSelected ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>                                        
                                        @endforeach
                                    </select>
                                @elseif($fieldType == 'combobox' && isset($dropdownOptions[$field]))
                                    <input class="form-control" list="{{ $field }}Options" id="{{ $field }}" name="{{ $field }}" 
                                        value="{{ old($field, isset($modelInstance) ? $modelInstance->{$field} : '') }}" placeholder="Type to search..." oninput="fetchOptions('{{ $field }}')">
                                    <datalist id="{{ $field }}Options"></datalist>
                                @else
                                    <input type="{{ $fieldType }}" id="{{ $field }}" name="{{ $field }}" class="form-control" value="{{ old($field, isset($modelInstance) ? $modelInstance->{$field} : '') }}">
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn {{ isset($modelInstance) ? 'btn-warning' : 'btn-success' }} width-lg btn-modal" data-bs-toggle="modal" data-bs-target="#confirmationModalCenter">
                            {{ isset($modelInstance) ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="confirmationModalCenter" tabindex="-1"
    aria-labelledby="confirmationModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalCenterTitle">
                  {{ isset($modelInstance) ? 'Update Confirmation' : 'Create Confirmation' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ isset($modelInstance) ? 'Are you sure you want to save the data changes?' : 'Are you sure you want to create new data based on the form contents?' }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary {{ isset($modelInstance) ? 'btn-edit' : 'btn-create' }}" data-bs-dismiss="modal">Save changes</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed end-0 top-0 p-3">
  <div id="toastEditUpdate" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
          <div class="auth-logo me-auto">
          </div>
          <small class="text-muted"></small>
          <button type="button" class="btn-close" data-bs-dismiss="toast"
              aria-label="Close"></button>
      </div>
      <div class="toast-body">
      </div>
  </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/data/umkm/form-create-update.js'])
@endsection