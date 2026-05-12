<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pbb_records', function (Blueprint $table) {
            $table->id();
            $table->string('nop', 32)->unique();
            $table->string('nama_wp', 255)->nullable();
            $table->string('alamat', 500)->nullable();
            $table->string('terbangun_flag', 64)->nullable();
            $table->string('nama_bangunan', 128)->nullable();
            $table->unsignedInteger('luas_bumi')->default(0);
            $table->unsignedInteger('luas_bangunan')->default(0);
            $table->string('kecamatan_djp_code', 4)->index();
            $table->string('desa_djp_code', 4)->index();
            $table->string('kecamatan_name', 64)->index();
            $table->string('kelurahan_name', 64)->index();
            $table->string('source_sheet', 16)->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamps();

            $table->index(['kecamatan_djp_code', 'desa_djp_code'], 'pbb_kec_desa_idx');
            $table->index(['kecamatan_name', 'luas_bangunan'], 'pbb_kec_lb_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbb_records');
    }
};
