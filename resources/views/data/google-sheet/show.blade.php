@extends('layouts.vertical', ['subtitle' => 'Google Sheets'])

@section('css')
@endsection

@section('content')

@include('layouts.partials/page-title', ['title' => 'Data', 'subtitle' => 'Google Sheets'])

<x-toast-notification />
<div class="card w-100">
  <div class="card-header d-flex justify-content-end">
    <a href="{{ route('google-sheets', ['menu_id' => 32]) }}" class="btn btn-sm btn-secondary">Back</a>
  </div>
  <div class="card-body">
    <div class="row">
      @php
        function displayData($label, $value) {
            echo "<dt>{$label}</dt><dd>" . (!empty($value) ? $value : '-') . "</dd>";
        }
      @endphp

      <div class="col-sm-6 col-md-6 col-lg-6">
          @php
            displayData('Jenis Konsultasi', $data->jenis_konsultasi);
            displayData('No Registrasi', $data->no_registrasi);
            displayData('Nama Pemilik', $data->nama_pemilik);
            displayData('Lokasi Bangunan', $data->lokasi_bg);
            displayData('Fungsi Bangunan', $data->fungsi_bg);
            displayData('Nama Bangunan', $data->nama_bangunan);
            displayData('Tanggal Permohonan', $data->tgl_permohonan);
            displayData('Status Verifikasi', $data->status_verifikasi);
            displayData('Status Permohonan', $data->status_permohonan);
            displayData('Alamat Pemilik', $data->alamat_pemilik);
            displayData('No Hp', $data->no_hp);
            displayData('Email', $data->email);
            displayData('Tanggal Catatan', $data->tanggal_catatan);
            displayData('Catatan Kekurangan Dokumen', $data->catatan_kekurangan_dokumen);
            displayData('Gambar', $data->gambar);
            displayData('KRK KKPR', $data->krk_kkpr);
            displayData('No KRK', $data->no_krk);
            displayData('LH', $data->lh);
            displayData('SKA', $data->ska);
            displayData('Keterangan', $data->keterangan);
            displayData('Helpdesk', $data->helpdesk);
            displayData('PJ', $data->pj);
            displayData('Kepemilikan', $data->Kepemilikan);
            displayData('Potensi Taru', $data->potensi_taru);
            displayData('Validasi Dinas', $data->validasi_dinas);
            displayData('Kategori Retribusi', $data->kategori_retribusi);
          @endphp
      </div>

      <div class="col-sm-6 col-md-6 col-lg-6">
          @php
            displayData('No Urut BA TPT', $data->no_urut_ba_tpt);
            displayData('Tanggal BA TPT', $data->tanggal_ba_tpt);
            displayData('No Urut BA TPA', $data->no_urut_ba_tpa);
            displayData('Tanggal BA TPA', $data->tanggal_ba_tpa);
            displayData('No Urut SKRD', $data->no_urut_skrd);
            displayData('Tanggal SKRD', $data->tanggal_skrd);
            displayData('PTSP', $data->ptsp);
            displayData('Selesai Terbit', $data->selesai_terbit);
            displayData('Tanggal Pembayaran', $data->tanggal_pembayaran);
            displayData('Format STS', $data->format_sts);
            displayData('Tahun Terbit', $data->tahun_terbit);
            displayData('Tahun Berjalan', $data->tahun_berjalan);
            displayData('Kelurahan', $data->kelurahan);
            displayData('Kecamatan', $data->kecamatan);
            displayData('LB', $data->lb);
            displayData('TB', $data->tb);
            displayData('JLB', $data->jlb);
            displayData('Unit', $data->unit);
            displayData('Usulan Retribusi', $data->usulan_retribusi);
            displayData('Nilai Retribusi Keseluruhan SIMBG', $data->nilai_retribusi_keseluruhan_simbg);
            displayData('Nilai Retribusi Keseluruhan PAD', $data->nilai_retribusi_keseluruhan_pad);
            displayData('Denda', $data->denda);
            displayData('Latitude', $data->latitude);
            displayData('Longitude', $data->longitude);
            displayData('NIK NIB', $data->nik_nib);
            displayData('Dokumen Tanah', $data->dok_tanah);
            displayData('Temuan', $data->temuan);
          @endphp
      </div>
    </div>
  </div>
</div>


@endsection