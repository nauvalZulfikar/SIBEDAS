<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_summary', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 16);                   // 'kab' | 'kec' | 'kelurahan'
            $table->string('kecamatan_name', 64)->nullable()->index();
            $table->string('kelurahan_name', 64)->nullable()->index();
            $table->unsignedInteger('pbb_total')->default(0);
            $table->unsignedInteger('pbb_terbangun')->default(0);
            $table->unsignedInteger('pbb_lahan_kosong')->default(0);
            $table->unsignedInteger('sat_count')->default(0);
            $table->unsignedInteger('pbg_terbit_count')->default(0);
            $table->bigInteger('gap_sat_minus_terbangun')->default(0);
            $table->decimal('gap_pct', 6, 2)->nullable();
            $table->unsignedBigInteger('pbb_lb_m2')->default(0);
            $table->unsignedBigInteger('sat_area_m2')->default(0);
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['scope', 'kecamatan_name', 'kelurahan_name'], 'reconcil_scope_uniq');
            $table->index(['scope', 'gap_sat_minus_terbangun'], 'reconcil_scope_gap_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_summary');
    }
};
