@extends('layouts.vertical', ['subtitle' => 'Data'])

@section('css')
@vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
<style>
  #dropzoneBuktiBayar .dz-preview{
    display: none;
  }
  #dropzoneBeritaAcara .dz-preview{
    display: none;
  }
    .file-info-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
  }
</style>
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'PBG'])

<x-toast-notification />

<div class="row">
  <div class="col-12">
    <div class="card w-100">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
          <button id="export-excel-btn" class="btn btn-sm btn-success">
            <iconify-icon icon="mingcute:file-excel-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
            Export Excel
          </button>
          <button class="btn btn-sm btn-info btn-send-notification" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
            Kirim Notifikasi
          </button>
          @if ($creator)
            <a href="{{ route('pbg-task.create')}}" class="btn btn-success btn-sm d-block d-sm-inline w-auto">Create</a>
          @endif
        </div>

        <!-- Table or Data Display Area -->
        <div id="table-pbg-tasks"
          data-updater="{{ $updater }}"
          data-destroyer="{{ $destroyer }}">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-labelledby="sendNotificationLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sendNotificationLabel">Kirim Notifikasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="notificationStatus" class="form-label">Pilih Status</label>
        <select class="form-select" id="notificationStatus">
          <option value="ditolak">Permohonan Ditolak</option>
          <option value="draf">Draf</option>
          <option value="verifikasi-kelengkapan">Verifikasi Kelengkapan Dokumen</option>
          <option value="perbaikan-dokumen">Perbaikan Dokumen</option>
          <option value="menunggu-penugasan">Menunggu Penugasan TPT/TPA</option>
          <option value="menunggu-jadwal">Menunggu Jadwal Konsultasi</option>
          <option value="verifikasi-tpt">Verifikasi Data TPT - (SLf Eksisting)</option>
          <option value="perbaikan-verifikasi">Perbaikan Verifikasi Data TPT - (SLf Eksisting)</option>
          <option value="pelaksanaan-konsultasi">Pelaksanaan Konsultasi</option>
          <option value="menunggu-hasil">Menunggu Hasil Konsultasi</option>
          <option value="perbaikan-dokumen-konsultasi">Perbaikan Dokumen Konsultasi</option>
          <option value="perhitungan-retribusi">Perhitungan Retribusi</option>
          <option value="penerbitan-sppst">Penerbitan SPPST</option>
          <option value="perbaikan-retribusi">Perbaikan Data Retribusi</option>
          <option value="penerbitan-skrd">Proses Penerbitan SKRD</option>
          <option value="menunggu-pembayaran">Menunggu Pembayaran Retribusi</option>
          <option value="verifikasi-pembayaran">Verifikasi Pembayaran Retribusi</option>
          <option value="verifikasi-sk-pbg">Verifikasi SK PBG</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="sendNotificationBtn">Kirim</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalBuktiBayar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Bukti Bayar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                  <form action="/upload-bukti-bayar" method="POST" class="dropzone" id="dropzoneBuktiBayar">
                    <div class="dz-message needsclick">
                      <i class="h1 bx bx-cloud-upload"></i>
                      <h3>Drop file here or click to upload.</h3>
                      <span class="text-muted fs-13">
                        (Only one file allowed. Selected file will be uploaded upon clicking submit.)
                      </span>
                    </div>
                    <!-- File info inside dropzone -->
                    <div id="fileInfoBuktiBayar" class="file-info-overlay d-none">
                      <span id="uploadedFileNameBuktiBayar" class="text-muted me-3"></span>
                      <button type="button" id="removeFileBtnBuktiBayar" class="btn btn-sm btn-danger">Hapus</button>
                    </div>
                  </form>
              </div>

              <!-- Submit Button -->
              <div class="d-flex justify-content-end">
                  <button type="button" id="submitBuktiBayar" class="btn btn-success">
                      <i class="bx bx-upload"></i> Upload
                  </button>
              </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalBeritaAcara" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Upload Berita Acara</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <form action="/upload-berita-acara" method="POST" class="dropzone" id="dropzoneBeritaAcara">
                  <div class="dz-message needsclick">
                    <i class="h1 bx bx-cloud-upload"></i>
                    <h3>Drop file here or click to upload.</h3>
                    <span class="text-muted fs-13">
                      (Only one file allowed. Selected file will be uploaded upon clicking submit.)
                    </span>
                  </div>
                  <!-- File info inside dropzone -->
                  <div id="fileInfoBeritaAcara" class="file-info-overlay d-none">
                    <span id="uploadedFileNameBeritaAcara" class="text-muted me-3"></span>
                    <button type="button" id="removeFileBtnBeritaAcara" class="btn btn-sm btn-danger">Hapus</button>
                  </div>
                </form>
              </div>

              <!-- Submit Button -->
              <div class="d-flex justify-content-end">
                <button type="button" id="submitBeritaAcara" class="btn btn-success">
                  <i class="bx bx-upload"></i> Upload
                </button>
              </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/pbg-task/index.js'])
@endsection