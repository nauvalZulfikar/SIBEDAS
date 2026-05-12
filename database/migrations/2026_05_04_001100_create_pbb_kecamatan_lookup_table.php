<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pbb_kecamatan_lookup', function (Blueprint $table) {
            $table->string('djp_code', 4)->primary();
            $table->string('kecamatan_name', 64)->index();
            $table->unsignedInteger('bps_district_code')->nullable()->index();
            $table->unsignedInteger('nop_count')->default(0);
            $table->unsignedInteger('terbangun_count')->default(0);
            $table->unsignedBigInteger('sum_luas_bumi_m2')->default(0);
            $table->unsignedBigInteger('sum_luas_bangunan_m2')->default(0);
            $table->unsignedSmallInteger('kelurahan_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbb_kecamatan_lookup');
    }
};
