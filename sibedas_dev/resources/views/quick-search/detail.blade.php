@extends('layouts.base', ['subtitle' => 'Quick Search'])

@section('css')
@vite(['resources/scss/pages/quick-search/detail.scss'])
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')
<div class="container qs-detail-container pt-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Informasi Permohonan PBG</h5>
                <a href="javascript:history.back()" class="btn btn-primary">Back</a>
                </div>
                <div class="card-body">
                <div class="row gy-3 gx-4">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Nama Pemohon</dt>
                            <dd class="col-sm-7">{{ $data->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Nama Pemilik</dt>
                            <dd class="col-sm-7">{{ $data->owner_name ?? '-' }}</dd>

                            <dt class="col-sm-5">Jenis Permohonan</dt>
                            <dd class="col-sm-7">{{ isset($data->application_type) ? $applicationTypes[$data->application_type] : '-' }}</dd>

                            <dt class="col-sm-5">Kondisi</dt>
                            <dd class="col-sm-7">{{ $data->condition ?? '-' }}</dd>

                            <dt class="col-sm-5">Nomor Registrasi</dt>
                            <dd class="col-sm-7">{{ $data->registration_number ?? '-'}}</dd>

                            <dt class="col-sm-5">Nomor Dokumen</dt>
                            <dd class="col-sm-7">{{ $data->document_number ?? '-' }}</dd>

                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7">{{ isset($data->status) ? $statusOptions[$data->status] : '-' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Alamat</dt>
                            <dd class="col-sm-7">{{ $data->address ?? '-' }}</dd>

                            <dt class="col-sm-5">Status SLF</dt>
                            <dd class="col-sm-7">{{ $data->slf_status_name ?? '-' }}</dd>

                            <dt class="col-sm-5">Fungsi Bangunan</dt>
                            <dd class="col-sm-7">{{ $data->function_type ?? '-' }}</dd>

                            <dt class="col-sm-5">Jenis Konsultasi</dt>
                            <dd class="col-sm-7">{{ $data->consultation_type ?? '-' }}</dd>

                            <dt class="col-sm-5">Jatuh Tempo</dt>
                            <dd class="col-sm-7">{{$data->due_date ? \Carbon\Carbon::parse($data->due_date)->format('d M Y') : '-' }}</dd>

                            <dt class="col-sm-5">Tanggal Dibuat</dt>
                            <dd class="col-sm-7">{{ \Carbon\Carbon::parse($data->task_created_at)->format('d M Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>
                </div>
            </div>
        </div>
        @if ($data->pbg_status)
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-black">Catatan Kekurangan Dokumen</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="note" class="form-label text-black">Keterangan</label>
                                    <p class="form-control-plaintext mb-0 text-black">{{$data->pbg_status->note}}</p>
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
                                <span class="d-sm-block">PBG Task Retributions</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#pbgTaskIntegration" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <span class="d-sm-block">PBG Task Index Integrations</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#pbgTaskPrasarana" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <span class="d-sm-block">PBG Task Prasarana</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#pbgTaskAssignments" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <span class="d-sm-block">Penugasan</span>
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
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Luas Bangunan</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->luas_bangunan ?? '-'}}</dd>
    
                                            <dt class="col-sm-4">Indeks Lokalitas</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->indeks_lokalitas ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">Wilayah SHST</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->wilayah_shst ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">Nama Kegiatan</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->kegiatan_name ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">Nilai SHST</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->nilai_shst ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">Indeks Integrasi</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->indeks_terintegrasi ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">Indeks Bg Terbangun</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->indeks_bg_terbangun ?? '-'}}</dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Nilai Retribusi Bangunan</dt>
                                            <dd class="col-sm-8">{{ number_format($data->pbg_task_retributions->nilai_retribusi_bangunan, 2, ',', '.') }}</dd>
                                            

                                            <dt class="col-sm-4">Nilai Prasarana</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->nilai_prasarana ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">PBG Dokumen</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->pbg_document ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">Underpayment</dt>
                                            <dd class="col-sm-8">{{$data->pbg_task_retributions->underpayment ?? '-'}}</dd>
                                            
                                            <dt class="col-sm-4">SKRD Amount</dt>
                                            <dd class="col-sm-8">{{ number_format($data->pbg_task_retributions->skrd_amount, 2, ',', '.') }}</dd>
                                        </dl>
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
                                <dl class="row">
                                    <dt class="col-sm-4">Indeks Fungsi Bangunan</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->indeks_fungsi_bangunan ?? '-'}}</dd>

                                    <dt class="col-sm-4">Indeks Parameter Kompleksitas</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->indeks_parameter_kompleksitas ?? '-'}}</dd>

                                    <dt class="col-sm-4">Indeks Parameter Permanensi</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->indeks_parameter_permanensi ?? '-'}}</dd>

                                    <dt class="col-sm-4">Indeks Parameter Ketinggian</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->indeks_parameter_ketinggian ?? '-'}}</dd>

                                    <dt class="col-sm-4">Faktor Kepemilikan</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->faktor_kepemilikan ?? '-'}}</dd>

                                    <dt class="col-sm-4">Indeks Terintegrasi</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->indeks_terintegrasi ?? '-'}}</dd>

                                    <dt class="col-sm-4">Total</dt>
                                    <dd class="col-sm-8">{{$data->pbg_task_index_integrations->total ?? '-'}}</dd>
                                </dl>
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
                                            <dl class="row">
                                                <dt class="col-sm-4">Prasarana Type</dt>
                                                <dd class="col-sm-8">{{$prasarana->prasarana_type ?? '-'}}</dd>

                                                <dt class="col-sm-4">Building Type</dt>
                                                <dd class="col-sm-8">{{$prasarana->building_type ?? '-'}}</dd>
                                                
                                                <dt class="col-sm-4">Total</dt>
                                                <dd class="col-sm-8">{{$prasarana->total ?? '-'}}</dd>
                                                
                                                <dt class="col-sm-4">Quantity</dt>
                                                <dd class="col-sm-8">{{$prasarana->quantity ?? '-'}}</dd>
                                                
                                                <dt class="col-sm-4">Unit</dt>
                                                <dd class="col-sm-8">{{$prasarana->unit ?? '-'}}</dd>
                                                
                                                <dt class="col-sm-4">Index Prasarana</dt>
                                                <dd class="col-sm-8">{{$prasarana->index_prasarana ?? '-'}}</dd>
                                                
                                                <dt class="col-sm-4">Created At</dt>
                                                <dd class="col-sm-8">{{$prasarana->created_at ? \Carbon\Carbon::parse($prasarana->created_at)->format('d M Y') : '-'}}</dd>
                                            </dl>
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
                            <input type="hidden" id="url_task_assignments" value="{{ route('api.quick-search-task-assignments', ['uuid' => $data->uuid]) }}" />
                            <div id="table-pbg-task-assignments"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/quick-search/detail.js'])
@endsection