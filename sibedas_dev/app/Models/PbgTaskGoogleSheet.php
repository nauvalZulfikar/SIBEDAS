<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbgTaskGoogleSheet extends Model
{
    protected $table = "pbg_task_google_sheet";

    protected $fillable = [
        'jenis_konsultasi',
        'no_registrasi',
        'nama_pemilik',
        'lokasi_bg',
        'fungsi_bg',
        'nama_bangunan',
        'tgl_permohonan',
        'status_verifikasi',
        'status_permohonan',
        'alamat_pemilik',
        'no_hp',
        'email',
        'tanggal_catatan',
        'catatan_kekurangan_dokumen',
        'gambar',
        'krk_kkpr',
        'no_krk',
        'lh',
        'ska',
        'keterangan',
        'helpdesk',
        'pj',
        'kepemilikan',
        'potensi_taru',
        'validasi_dinas',
        'kategori_retribusi',
        'no_urut_ba_tpt',
        'tanggal_ba_tpt',
        'no_urut_ba_tpa',
        'tanggal_ba_tpa',
        'no_urut_skrd',
        'tanggal_skrd',
        'ptsp',
        'selesai_terbit',
        'tanggal_pembayaran',
        'format_sts',
        'tahun_terbit',
        'tahun_berjalan',
        'kelurahan',
        'kecamatan',
        'lb',
        'tb',
        'jlb',
        'unit',
        'usulan_retribusi',
        'nilai_retribusi_keseluruhan_simbg',
        'nilai_retribusi_keseluruhan_pad',
        'denda',
        'latitude',
        'longitude',
        'nik_nib',
        'dok_tanah',
        'temuan',
    ];


}
