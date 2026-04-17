@extends('layouts.vertical', ['subtitle' => 'Detail'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@vite(['resources/scss/pages/pbg-task/show.scss'])
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'PBG'])
<x-toast-notification />
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('api.pbg-task.update', ['task_uuid' => $data->uuid]) }}" id="formUpdatePbgTask">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{$data->name}}">
                            </div>
                            <div class="mb-3">
                                <label for="owner_name" class="form-label">Owner Name</label>
                                <input type="text" class="form-control" id="owner_name" name="owner_name" value="{{$data->owner_name}}">
                            </div>
                            <div class="mb-3">
                                <label for="application_type" class="form-label">Application Type Name</label>
                                <select name="application_type" class="form-select">
                                    @foreach($applicationTypes as $key => $value)
                                        <option value="{{ $key }}" 
                                            {{ (old('application_type', $data->application_type ?? '') == $key) ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="condition" class="form-label">Condition</label>
                                <input type="text" class="form-control" id="condition" name="condition" value="{{$data->condition}}">
                            </div>
                            <div class="mb-3">
                                <label for="registration_number" class="form-label">Registration Number</label>
                                <input type="text" class="form-control" id="registration_number" name="registration_number" value="{{$data->registration_number}}">
                            </div>
                            <div class="mb-3">
                                <label for="document_number" class="form-label">Document Number</label>
                                <input type="text" class="form-control" id="document_number" name="document_number" value="{{$data->document_number}}">
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status Name</label>
                                <select name="status" class="form-select">
                                    @foreach($statusOptions as $key => $value)
                                        <option value="{{ $key }}" {{ old('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="{{$data->address}}">
                            </div>
                            <div class="mb-3">
                                <label for="slf_status_name" class="form-label">SLF Status Name</label>
                                <input type="text" class="form-control" id="slf_status_name" name="slf_status_name" value="{{$data->slf_status_name}}">
                            </div>
                            <div class="mb-3">
                                <label for="function_type" class="form-label">Function Type</label>
                                <input type="text" class="form-control" id="function_type" name="function_type" value="{{$data->function_type}}">
                            </div>
                            <div class="mb-3">
                                <label for="consultation_type" class="form-label">Consultation Type</label>
                                <input type="text" class="form-control" id="consultation_type" name="consultation_type" value="{{$data->consultation_type}}">
                            </div>
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="text" class="form-control" id="datepicker_due_date" name="due_date" value="{{$data->due_date}}">
                            </div>
                            <div class="mb-3">
                                <label for="task_created_at" class="form-label">Task Created At</label>
                                <input type="datetime-local" class="form-control" id="task_created_at" name="task_created_at" value="{{$data->task_created_at}}" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status Validasi Data</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_valid" name="is_valid" value="1" {{ $data->is_valid ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_valid">
                                        <span class="status-text">{{ $data->is_valid ? 'Data Valid' : 'Data Tidak Valid' }}</span>
                                        <small class="text-muted d-block">
                                            {{ $data->is_valid ? 'Data telah diverifikasi dan sesuai' : 'Data perlu diverifikasi atau diperbaiki' }}
                                        </small>
                                    </label>
                                </div>
                                <!-- Hidden input to ensure false value is sent when unchecked -->
                                <input type="hidden" name="is_valid" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-end">
                            <button type="button" id="btnUpdatePbgTask" class="btn btn-warning">
                                <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                Update
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($data->pbg_status)
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Keterangan</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="note" class="form-label">Note</label>
                                <p class="form-control-plaintext mb-0">{{$data->pbg_status->note}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs nav-justified">
                    <li class="nav-item">
                        <a href="#pbgTaskRetributions" data-bs-toggle="tab" aria-expanded="false"
                            class="nav-link active">
                            <span class="d-none d-sm-block">PBG Task Retributions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#pbgTaskIntegration" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                            <span class="d-none d-sm-block">PBG Task Index Integrations</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#pbgTaskPrasarana" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                            <span class="d-none d-sm-block">PBG Task Prasarana</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#pbgTaskAssignments" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                            <span class="d-none d-sm-block">Penugasan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#pbgTaskDetail" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                            <span class="d-none d-sm-block">Data Bangunan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#pbgTaskDataLists" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                            <span class="d-none d-sm-block">Data Lists</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="pbgTaskRetributions">
                        @if ($data->pbg_task_retributions)                    
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <dt>Luas Bangunan</dt>
                                        <dd>{{$data->pbg_task_retributions->luas_bangunan}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Lokalitas</dt>
                                        <dd>{{$data->pbg_task_retributions->indeks_lokalitas}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Wilayah SHST</dt>
                                        <dd>{{$data->pbg_task_retributions->wilayah_shst}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Kegiatan Name</dt>
                                        <dd>{{$data->pbg_task_retributions->kegiatan_name}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Nilai SHST</dt>
                                        <dd>{{ number_format($data->pbg_task_retributions->nilai_shst, 2, ',', '.') }}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Integrasi</dt>
                                        <dd>{{$data->pbg_task_retributions->indeks_terintegrasi}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Bg Terbangun</dt>
                                        <dd>{{$data->pbg_task_retributions->indeks_bg_terbangun}}</dd>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <dt>Nilai Retribusi Bangunan</dt>
                                        <dd>{{ number_format($data->pbg_task_retributions->nilai_retribusi_bangunan, 2, ',', '.') }}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Nilai Prasarana</dt>
                                        <dd>{{$data->pbg_task_retributions->nilai_prasarana}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>PBG Dokumen</dt>
                                        <dd>{{$data->pbg_task_retributions->pbg_document}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Underpayment</dt>
                                        <dd>{{$data->pbg_task_retributions->underpayment}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>SKRD Amount</dt>
                                        <dd>{{ number_format($data->pbg_task_retributions->skrd_amount, 2, ',', '.') }}</dd>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-folder-open empty-icon"></i>
                                <h5 class="empty-title">Data Tidak Tersedia</h5>
                                <p class="empty-text">Tidak ada data yang terkait dengan PBG task ini.</p>
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane" id="pbgTaskIntegration">
                        @if ($data->pbg_task_index_integrations)
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <dt>Indeks Fungsi Bangunan</dt>
                                        <dd>{{$data->pbg_task_index_integrations->indeks_fungsi_bangunan}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Parameter Kompleksitas</dt>
                                        <dd>{{$data->pbg_task_index_integrations->indeks_parameter_kompleksitas}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Parameter Permanensi</dt>
                                        <dd>{{$data->pbg_task_index_integrations->indeks_parameter_permanensi}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Paramter Ketinggian</dt>
                                        <dd>{{$data->pbg_task_index_integrations->indeks_parameter_ketinggian}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Faktor Kepemilikan</dt>
                                        <dd>{{$data->pbg_task_index_integrations->faktor_kepemilikan}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Indeks Terintegrasi</dt>
                                        <dd>{{$data->pbg_task_index_integrations->indeks_terintegrasi}}</dd>
                                    </div>
                                    <div class="mb-3">
                                        <dt>Total</dt>
                                        <dd>{{$data->pbg_task_index_integrations->total}}</dd>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-folder-open empty-icon"></i>
                                <h5 class="empty-title">Data Tidak Tersedia</h5>
                                <p class="empty-text">Tidak ada data yang terkait dengan PBG task ini.</p>
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane" id="pbgTaskPrasarana">
                        <div class="row d-flex flex-warp gap-3 justify-content-center">
                            @if ($data->pbg_task_retributions && $data->pbg_task_retributions->pbg_task_prasarana)
                                @foreach ($data->pbg_task_retributions->pbg_task_prasarana as $prasarana)
                                    <div class="border p-3 rounded shadow-sm col-md-4">
                                        <div class="mb-3">
                                            <dt>Prasarana Type</dt>
                                            <dd>{{$prasarana->prasarana_type}}</dd>
                                        </div>
                                        <div class="mb-3">
                                            <dt>Building Type</dt>
                                            <dd>{{$prasarana->building_type}}</dd>
                                        </div>
                                        <div class="mb-3">
                                            <dt>Total</dt>
                                            <dd>{{$prasarana->total}}</dd>
                                        </div>
                                        <div class="mb-3">
                                            <dt>Quantity</dt>
                                            <dd>{{$prasarana->quantity}}</dd>
                                        </div>
                                        <div class="mb-3">
                                            <dt>Unit</dt>
                                            <dd>{{$prasarana->unit}}</dd>
                                        </div>
                                        <div class="mb-3">
                                            <dt>Index Prasarana</dt>
                                            <dd>{{$prasarana->index_prasarana}}</dd>
                                        </div>
                                        <div class="mb-3">
                                            <dt>Created At</dt>
                                            <dd>{{$prasarana->created_at}}</dd>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-folder-open empty-icon"></i>
                                    <h5 class="empty-title">Data Tidak Tersedia</h5>
                                    <p class="empty-text">Tidak ada data yang terkait dengan PBG task ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="tab-pane" id="pbgTaskAssignments">
                        <input type="hidden" id="uuid" value="{{ $data->uuid }}" />
                        <div id="table-pbg-task-assignments"></div>
                    </div>
                    <div class="tab-pane" id="pbgTaskDetail">
                        @if ($data->pbg_task_detail)
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <dt>Fungsi Bangunan</dt>
                                    <dd>{{$data->pbg_task_detail->building_purpose}}</dd>
                                    <dt>Jenis Prototipe</dt>
                                    <dd>{{$data->pbg_task_detail->prototype ?? '-'}}</dd>
                                </div>
                                <div class="col-md-4">
                                    <dt>Sub Fungsi Bangunan</dt>
                                    <dd>{{$data->pbg_task_detail->building_use}}</dd>
                                </div>
                                <div class="col-md-4">
                                    <dt>Nama Bangunan</dt>
                                    <dd>{{$data->pbg_task_detail->name_building}}</dd>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <dt>Spesifikasi Bangunan</dt>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <dt>Luas Bangunan</dt>
                                                    <dd>{{$data->pbg_task_detail->total_area ?? '-'}} m<sup>2</sup></dd>
                                                    <dt>Jumlah Lapis Basemen</dt>
                                                    <dd>{{$data->pbg_task_detail->basement ?? '-'}}</dd>
                                                    <dt>Estimasi Jumlah Penghuni</dt>
                                                    <dd>{{$data->pbg_task_detail->occupancy ?? '-'}}</dd>
                                                </div>
                                                <div class="col-md-4">
                                                    <dt>Ketinggian Bangunan</dt>
                                                    <dd>{{$data->pbg_task_detail->height ?? '-'}} m</dd>
                                                    <dt>Luas Basemen</dt>
                                                    <dd>{{$data->pbg_task_detail->basement_area ?? '-'}}</dd>
                                                </div>
                                                <div class="col-md-4">
                                                    <dt>Jumlah Lantai</dt>
                                                    <dd>{{$data->pbg_task_detail->floor ?? '-'}}</dd>
                                                    <dt>Jumlah Unit</dt>
                                                    <dd>{{$data->pbg_task_detail->unit ?? '-'}}</dd>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <dt>Intensitas Bangunan</dt>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <dt>Nomor KKPR / KRK</dt>
                                                    <dd>{{$data->pbg_task_detail->kkr_number ?? '-'}}</dd>
                                                    <dt>Koefisien Lantai Bangunan (KLB)</dt>
                                                    <dd>{{$data->pbg_task_detail->koefisien_lantai_bangunan ?? '-'}}</dd>
                                                </div>
                                                <div class="col-md-4">
                                                    <dt>Garis Sempadan Bangunan (GSB)</dt>
                                                    <dd>{{$data->pbg_task_detail->gsb ?? '-'}}</dd>
                                                    <dt>Koefisien Dasar Hijau (KDH)</dt>
                                                    <dd>{{$data->pbg_task_detail->koefisien_dasar_hijau ?? '-'}}</dd>
                                                </div>
                                                <div class="col-md-4">
                                                    <dt>Koefisien Dasar Bangunan (KDB)</dt>
                                                    <dd>{{$data->pbg_task_detail->koefisien_dasar_bangunan ?? '-'}}</dd>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <dt>Lokasi Bangunan</dt>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <dt>Provinsi</dt>
                                                    <dd>{{$data->pbg_task_detail->building_province_name ?? '-'}}</dd>
                                                    <dt>Desa / Kelurahan</dt>
                                                    <dd>{{$data->pbg_task_detail->building_ward_name ?? '-'}}</dd>
                                                </div>
                                                <div class="col-md-4">
                                                    <dt>Kabupaten / Kota</dt>
                                                    <dd>{{$data->pbg_task_detail->building_regency_name ?? '-'}}</dd>
                                                    <dt>Alamat Lengkap</dt>
                                                    <dd>{{$data->pbg_task_detail->building_address ?? '-'}}</dd>
                                                </div>
                                                <div class="col-md-4">
                                                    <dt>Kecamatan</dt>
                                                    <dd>{{$data->pbg_task_detail->building_district_name ?? '-'}}</dd>
                                                    <dt>Koordinat Latitude dan Longitude</dt>
                                                    <dd>{{$data->pbg_task_detail->latitude ?? '-'}}, {{$data->pbg_task_detail->longitude ?? '-'}}</dd>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-folder-open empty-icon"></i>
                                <h5 class="empty-title">Data Tidak Tersedia</h5>
                                <p class="empty-text">Tidak ada data yang terkait dengan PBG task ini.</p>
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane" id="pbgTaskDataLists">
                        @if($data->pbg_status && !empty($data->pbg_status->note))
                            <div class="alert alert-warning d-flex gap-2 align-items-start mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                                <div>
                                    <strong>Keterangan:</strong>
                                    {{ $data->pbg_status->note }}
                                </div>
                            </div>
                        @endif
                        @if($dataListsByType && $dataListsByType->count() > 0)
                            @foreach($dataListsByType as $dataType => $dataLists)
                                @php
                                    $dataTypeName = $dataLists->first()->data_type_name ?? "Data Type {$dataType}";
                                @endphp
                                <div class="data-list-section mb-4">
                                    <div class="section-header mb-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-folder-open section-icon me-2"></i>
                                                <h5 class="section-title mb-0">{{ $dataTypeName }}</h5>
                                            </div>
                                            <span class="badge bg-info section-count">{{ $dataLists->count() }} items</span>
                                        </div>
                                    </div>
                                    
                                    <div class="data-list-container">
                                        @foreach($dataLists as $index => $dataList)
                                            <div class="data-list-item">
                                                <div class="list-item-header">
                                                    <div class="d-flex align-items-start justify-content-between">
                                                        <div class="item-info flex-grow-1">
                                                            <h6 class="item-title mb-1">
                                                                <span class="item-number">{{ $index + 1 }}.</span>
                                                                {{ $dataList->name }}
                                                            </h6>
                                                            @if($dataList->description)
                                                                <p class="item-description text-muted small mb-1">
                                                                    {{ $dataList->description }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                        @if($dataList->status !== null)
                                                            <div class="item-status ms-3">
                                                                @if($dataList->status == 1)
                                                                    <span class="badge bg-success">{{ $dataList->status_name ?? 'Sesuai' }}</span>
                                                                @elseif($dataList->status == 0)
                                                                    <span class="badge bg-danger">{{ $dataList->status_name ?? 'Tidak Sesuai' }}</span>
                                                                @else
                                                                    <span class="badge bg-secondary">{{ $dataList->status_name ?? 'Unknown' }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div class="list-item-meta">
                                                    <div class="row">
                                                        @if($dataList->file)
                                                            <div class="col-md-6">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-paperclip me-1"></i>
                                                                    File: 
                                                                    <span class="text-dark">{{ $dataList->file_name }}</span>
                                                                    @if($dataList->file_extension)
                                                                        <span class="badge bg-light text-dark ms-1">{{ $dataList->file_extension }}</span>
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        @endif
                                                        <div class="col-md-6">
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                {{ $dataList->formatted_created_at }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                    @if($dataList->note)
                                                        <div class="row mt-1">
                                                            <div class="col-12">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-sticky-note me-1"></i>
                                                                    Catatan: 
                                                                    <span class="text-dark">{{ $dataList->note }}</span>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <i class="fas fa-folder-open empty-icon"></i>
                                <h5 class="empty-title">Data Tidak Tersedia</h5>
                                <p class="empty-text">Tidak ada data yang terkait dengan PBG task ini.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/pbg-task/show.js'])
@endsection