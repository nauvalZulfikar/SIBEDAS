<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retribution_estimates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent ID untuk hierarki');
            $table->tinyInteger('no')->nullable()->comment('Nomor urut kategori utama');
            $table->string('fungsi_bg', 255)->comment('Nama fungsi bangunan gedung');
            $table->decimal('usulan_retribusi_per_m2', 15, 2)->nullable()->comment('Usulan retribusi per m2 (Rp)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('retribution_estimates')->onDelete('cascade');
            $table->index(['parent_id', 'no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retribution_estimates');
    }
};
