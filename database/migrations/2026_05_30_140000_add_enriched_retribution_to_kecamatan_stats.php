<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Split potensi retribusi tidak berizin menjadi enriched vs unenriched.
 *
 *   enriched   = bangunan tanpa izin yg PUNYA fungsi (status ditolak/batal
 *                3/9/22 → pt.function_type ada) → tarif/m² sesuai fungsi (akurat).
 *   unenriched = sisanya (unmatched/orphan, tanpa fungsi) → default Hunian.
 *
 * Total tetap di without_permit_retribution; kolom enriched ini buat
 * "mulai dari yang enriched dulu" — angka paling bisa dipertanggungjawabkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kecamatan_stats', function (Blueprint $table) {
            $table->decimal('without_permit_enriched_area_m2', 20, 2)->default(0)->after('without_permit_retribution');
            $table->decimal('without_permit_enriched_retribution', 20, 2)->default(0)->after('without_permit_enriched_area_m2');
        });
    }

    public function down(): void
    {
        Schema::table('kecamatan_stats', function (Blueprint $table) {
            $table->dropColumn(['without_permit_enriched_area_m2', 'without_permit_enriched_retribution']);
        });
    }
};
