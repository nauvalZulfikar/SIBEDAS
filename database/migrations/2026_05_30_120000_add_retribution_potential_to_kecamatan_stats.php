<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Potensi retribusi bangunan tidak berizin, di-precompute per kecamatan
 * per area-bucket bareng kecamatan_stats lainnya (RefreshKecamatanStats).
 *
 *   without_permit_area_m2   = SUM(estimated_area_m2) bangunan tanpa izin
 *   without_permit_retribution = area × tarif/m² (default fungsi Hunian,
 *                                dari retribution_estimates)
 *
 * Catatan akurasi: estimated_area_m2 untuk footprint Microsoft tersimpan
 * sebagai bounding-box → cenderung over-estimate; fungsi diasumsikan Hunian
 * (batas bawah) karena detected_buildings belum punya label fungsi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kecamatan_stats', function (Blueprint $table) {
            $table->decimal('without_permit_area_m2', 20, 2)->default(0)->after('without_permit_total');
            $table->decimal('without_permit_retribution', 20, 2)->default(0)->after('without_permit_area_m2');
        });
    }

    public function down(): void
    {
        Schema::table('kecamatan_stats', function (Blueprint $table) {
            $table->dropColumn(['without_permit_area_m2', 'without_permit_retribution']);
        });
    }
};
