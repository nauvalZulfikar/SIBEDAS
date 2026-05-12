<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pbb_kelurahan_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('djp_kec_code', 4);
            $table->string('djp_desa_code', 4);
            $table->string('kelurahan_name', 64)->index();
            $table->string('bps_village_code', 16)->nullable()->index();
            $table->unsignedInteger('nop_count')->default(0);
            $table->unsignedInteger('terbangun_count')->default(0);
            $table->unsignedBigInteger('sum_luas_bangunan_m2')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['djp_kec_code', 'djp_desa_code'], 'pbb_kel_djp_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbb_kelurahan_lookup');
    }
};
