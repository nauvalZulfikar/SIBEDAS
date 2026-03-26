@extends('layouts.vertical', ['subtitle' => 'Menu'])

@section('content')

@include('layouts.partials/page-title', ['title' => 'Settings', 'subtitle' => 'Menu'])

<x-toast-notification />
<div class="row">
    <div class="card">
        <div class="card-header d-flex justify-content-end">
            <a href="{{ route('google-sheets') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form id="formCreateGoogleSheet" action="{{route("pbg-task-google-sheet.store")}}" method="post">
            @csrf
            <div class="row">
                <div class="col-6">
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="jenis_konsultasi">Jenis Konsultasi</label>
                        <input type="text" id="jenis_konsultasi" name="jenis_konsultasi"
                                class="form-control" placeholder="Jenis Konsultasi" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="no_registrasi">No Registrasi</label>
                        <input type="text" id="no_registrasi" name="no_registrasi"
                                class="form-control" placeholder="Nomor Registrasi" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="nama_pemilik">Nama Pemilik</label>
                        <input type="text" id="nama_pemilik" name="nama_pemilik"
                                class="form-control" placeholder="Nama Pemilik" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="lokasi_bg">Lokasi BG</label>
                        <input type="text" id="lokasi_bg" name="lokasi_bg"
                                class="form-control" placeholder="Lokasi BG" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="fungsi_bg">Fungsi BG</label>
                        <input type="text" id="fungsi_bg" name="fungsi_bg"
                                class="form-control" placeholder="Fungsi BG" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="nama_bangunan">Nama Bangunan</label>
                        <input type="text" id="nama_bangunan" name="nama_bangunan"
                                class="form-control" placeholder="Nama Bangunan" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="tgl_permohonan">Tanggal Permohonan</label>
                        <input type="text" id="tgl_permohonan" name="tgl_permohonan"
                                class="form-control" placeholder="Tanggal Permohonan" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="status_verifikasi">Status Verifikasi</label>
                        <input type="text" id="status_verifikasi" name="status_verifikasi"
                                class="form-control" placeholder="Status Verifikasi" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="status_permohonan">Status Permohonan</label>
                        <input type="text" id="status_permohonan" name="status_permohonan"
                                class="form-control" placeholder="Status Permohonan" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="status_permohonan">Status Permohonan</label>
                        <input type="text" id="status_permohonan" name="status_permohonan"
                                class="form-control" placeholder="Status Permohonan" >
                    </div>
                    <div class="mb-3 mr-3">
                        <label class="form-label" for="alamat_pemilik">Alamat Pemilik</label>
                        <input type="text" id="alamat_pemilik" name="alamat_pemilik"
                                class="form-control" placeholder="Alamat Pemilik" >
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-3">
                        <label class="form-label" for="no_hp">No Hp</label>
                        <input type="text" id="no_hp" name="no_hp"
                                class="form-control" placeholder="No Hp" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="text" id="email" name="email"
                                class="form-control" placeholder="Email" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="tanggal_catatan">Tanggal Catatan</label>
                        <input type="text" id="tanggal_catatan" name="tanggal_catatan"
                                class="form-control" placeholder="Tanggal Catatan" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="catatan_kekurangan_dokumen">Catatan Kekurangan Dokumen</label>
                        <input type="text" id="catatan_kekurangan_dokumen" name="catatan_kekurangan_dokumen"
                                class="form-control" placeholder="Catatan Kekurangan Dokumen" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="gambar">Gambar</label>
                        <input type="text" id="gambar" name="gambar"
                                class="form-control" placeholder="Gambar" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="krk_kkpr">KRK KKPR</label>
                        <input type="text" id="krk_kkpr" name="krk_kkpr"
                                class="form-control" placeholder="KRK KKPR" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="no_krk">NO KRK</label>
                        <input type="text" id="no_krk" name="no_krk"
                                class="form-control" placeholder="NO KRK" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lh">LH</label>
                        <input type="text" id="lh" name="lh"
                                class="form-control" placeholder="LH" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="ska">SKA</label>
                        <input type="text" id="ska" name="ska"
                                class="form-control" placeholder="SKA" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <input type="text" id="keterangan" name="keterangan"
                                class="form-control" placeholder="Keterangan" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="helpdesk">Help Desk</label>
                        <input type="text" id="helpdesk" name="helpdesk"
                                class="form-control" placeholder="Help Desk" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pj">PJ</label>
                        <input type="text" id="pj" name="pj" class="form-control" placeholder="PJ" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="kepemilikan">Kepemilikan</label>
                        <input type="text" id="kepemilikan" name="kepemilikan" class="form-control" placeholder="Kepemilikan" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="potensi_taru">Potensi Taru</label>
                        <input type="text" id="potensi_taru" name="potensi_taru" class="form-control" placeholder="Potensi Taru" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="validasi_dinas">Validasi Dinas</label>
                        <input type="text" id="validasi_dinas" name="validasi_dinas" class="form-control" placeholder="Validasi Dinas" >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="kategori_retribusi">Kategori Retribusi</label>
                        <input type="text" id="kategori_retribusi" name="kategori_retribusi" class="form-control" placeholder="Kategori Retribusi" >
                    </div>
                </div>
            </div>
            <button class="btn btn-primary me-1" type="button" id="btnCreateGoogleSheet">
                <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                Create
            </button>
        </form>
    </div>
</div>

@endsection

@section('scripts')
@endsection