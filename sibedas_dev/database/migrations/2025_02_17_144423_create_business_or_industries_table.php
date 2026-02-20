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
        Schema::create('business_or_industries', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kecamatan');
            $table->string('nama_kelurahan');
            $table->string('nop')->unique();
            $table->string('nama_wajib_pajak');
            $table->text('alamat_wajib_pajak')->nullable();
            $table->text('alamat_objek_pajak');
            $table->decimal('luas_bumi',20,2)->default(0);
            $table->decimal('luas_bangunan',20,2)->default(0);
            $table->decimal('njop_bumi',20,2)->default(0);
            $table->decimal('njop_bangunan',20,2)->default(0);
            $table->decimal('ketetapan',20,2)->default(0);
            $table->integer('tahun_pajak');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_or_industries');
    }
};
