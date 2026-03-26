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
        Schema::create('pbg_task_retributions', function (Blueprint $table) {
            $table->id();
            $table->integer('detail_id')->nullable();
            $table->timestamp('detail_created_at')->nullable();
            $table->timestamp('detail_updated_at')->nullable();
            $table->string('detail_uid')->nullable();
            $table->decimal('luas_bangunan')->nullable();
            $table->decimal('indeks_lokalitas', 10,4)->nullable();
            $table->string('wilayah_shst')->nullable();
            $table->integer('kegiatan_id')->nullable();
            $table->string('kegiatan_name')->nullable();
            $table->decimal('nilai_shst')->nullable();
            $table->decimal('indeks_terintegrasi')->nullable();
            $table->decimal('indeks_bg_terbangun')->nullable();
            $table->decimal('nilai_retribusi_bangunan')->nullable();
            $table->decimal('nilai_prasarana')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('pbg_document')->nullable();
            $table->integer('underpayment')->nullable();
            $table->decimal('skrd_amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_retributions');
    }
};
