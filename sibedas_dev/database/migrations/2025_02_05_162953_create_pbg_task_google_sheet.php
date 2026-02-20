<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pbg_task_google_sheet', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_konsultasi')->nullable();
            $table->string('no_registrasi')->nullable()->unique();
            $table->string('nama_pemilik')->nullable();
            $table->text('lokasi_bg')->nullable();
            $table->string('fungsi_bg')->nullable();
            $table->string('nama_bangunan')->nullable();
            $table->date('tgl_permohonan')->nullable();
            $table->string('status_verifikasi')->nullable();
            $table->string('status_permohonan')->nullable();
            $table->text('alamat_pemilik')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable();
            $table->date('tanggal_catatan')->nullable();
            $table->text('catatan_kekurangan_dokumen')->nullable();
            $table->string('gambar')->nullable();
            $table->string('krk_kkpr')->nullable();
            $table->string('no_krk')->nullable();
            $table->string('lh')->nullable();
            $table->string('ska')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('helpdesk')->nullable();
            $table->string('pj')->nullable();
            $table->string('kepemilikan')->nullable();
            $table->string('potensi_taru')->nullable();
            $table->string('validasi_dinas')->nullable();
            $table->string('kategori_retribusi')->nullable();
            $table->string('no_urut_ba_tpt')->nullable();
            $table->date('tanggal_ba_tpt')->nullable();
            $table->string('no_urut_ba_tpa')->nullable();
            $table->date('tanggal_ba_tpa')->nullable();
            $table->string('no_urut_skrd')->nullable();
            $table->date('tanggal_skrd')->nullable();
            $table->string('ptsp')->nullable();
            $table->string('selesai_terbit')->nullable();
            $table->date('tanggal_pembayaran')->nullable();
            $table->string('format_sts')->nullable();
            $table->integer('tahun_terbit')->nullable();
            $table->integer('tahun_berjalan')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->decimal('lb', 20,2)->nullable();
            $table->decimal('tb', 20, 2)->nullable();
            $table->integer('jlb')->nullable();
            $table->integer('unit')->nullable();
            $table->integer('usulan_retribusi')->nullable();
            $table->decimal('nilai_retribusi_keseluruhan_simbg', 20, 2)->nullable();
            $table->decimal('nilai_retribusi_keseluruhan_pad', 20, 2)->nullable();
            $table->decimal('denda', 20, 2)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('nik_nib')->nullable();
            $table->string('dok_tanah')->nullable();
            $table->text('temuan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_google_sheet');
    }
};
