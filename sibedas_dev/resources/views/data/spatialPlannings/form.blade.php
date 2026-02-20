@extends('layouts.vertical', ['subtitle' => $subtitle]) <!-- Menggunakan subtitle dari controller -->

@section('css')
<style>
.form-check.form-switch {
    padding-left: 0;
    
    .form-check-input {
        width: 3.25rem;
        height: 1.75rem;
        border-radius: 1rem;
        background-color: #dc3545; // Default to red (belum terbit)
        border: none;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
        margin-left: 0;
        
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;

        &:checked {
            background-color: #28a745; // Green when checked (sudah terbit)
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        &:focus {
            box-shadow: 0 0 0 0.2rem rgba(93, 135, 255, 0.25);
            outline: none;
        }

        &::before {
            content: "";
            position: absolute;
            top: 0.125rem;
            left: 0.125rem;
            width: 1.5rem;
            height: 1.5rem;
            background-color: #fff;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            z-index: 1;
        }

        &:checked::before {
            transform: translateX(1.5rem);
        }
    }

    .form-check-label {
        margin-left: 1rem;
        cursor: pointer;
        padding-left: 0;
        
        .status-text {
            font-weight: 600;
            font-size: 1rem;
            color: #2c3e50;
            transition: color 0.3s ease;
        }

        small {
            font-size: 0.85rem;
            margin-top: 0.25rem;
            line-height: 1.3;
        }
    }
}
</style>
@endsection

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
                            @if($field == 'is_terbit')
                                <!-- Special handling for is_terbit toggle -->
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label">{{ $label }}</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_terbit" name="is_terbit" value="1" 
                                            {{ old('is_terbit', isset($modelInstance) && $modelInstance->is_terbit ? 'checked' : '') }}>
                                        <label class="form-check-label" for="is_terbit">
                                            <span class="status-text">{{ old('is_terbit', isset($modelInstance) && $modelInstance->is_terbit) ? 'Sudah Terbit' : 'Belum Terbit' }}</span>
                                            <small class="text-muted d-block">
                                                {{ old('is_terbit', isset($modelInstance) && $modelInstance->is_terbit) ? 'Status PBG sudah diterbitkan' : 'Status PBG belum diterbitkan' }}
                                            </small>
                                        </label>
                                    </div>
                                    <!-- Hidden input to ensure false value is sent when unchecked -->
                                    <input type="hidden" name="is_terbit" value="0">
                                </div>
                            @elseif($field == 'calculated_retribution' && isset($modelInstance))
                                <!-- Special handling for calculated retribution (read-only) -->
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label">{{ $label }}</label>
                                    <div class="form-control bg-light" readonly>
                                        <strong class="text-success">{{ $modelInstance->formatted_retribution ?? '0' }}</strong>
                                    </div>
                                    <small class="text-muted">
                                        Rumus: LUAS LAHAN × BCR (decimal) × HARGA SATUAN<br>
                                        Detail: {{ $modelInstance->land_area ?? 0 }} m² × {{ ($modelInstance->site_bcr ?? 0) / 100 }} × 
                                        <strong class="{{ $modelInstance->is_business_type ? 'text-success' : 'text-primary' }}">
                                            {{ $modelInstance->is_business_type ? 'Rp 44.300 (USAHA)' : 'Rp 16.000 (NON USAHA)' }}
                                        </strong>
                                    </small>
                                </div>
                            @elseif($field == 'business_type_info' && isset($modelInstance))
                                <!-- Business type information (read-only) -->
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label">Jenis Usaha</label>
                                    <div class="form-control bg-light" readonly>
                                        <span class="badge {{ $modelInstance->is_business_type ? 'bg-success' : 'bg-primary' }}">
                                            {{ $modelInstance->is_business_type ? 'USAHA' : 'NON USAHA' }}
                                        </span>
                                    </div>
                                    <small class="text-muted">Terdeteksi otomatis dari fungsi bangunan: "{{ $modelInstance->building_function ?? $modelInstance->activities ?? 'N/A' }}"</small>
                                </div>
                            @else
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
                                        @elseif($fieldType == 'date')
                                        <input type="date" id="{{ $field }}" name="{{ $field }}" class="form-control" 
                                            value="{{ old($field, isset($modelInstance) && $modelInstance->{$field} ? \Carbon\Carbon::parse($modelInstance->{$field})->format('Y-m-d') : '') }}">
                                    @else
                                        <input type="{{ $fieldType }}" id="{{ $field }}" name="{{ $field }}" class="form-control" value="{{ old($field, isset($modelInstance) ? $modelInstance->{$field} : '') }}">
                                    @endif
                                </div>
                            @endif
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
@vite(['resources/js/data/spatialPlannings/form-create-update.js'])
@endsection