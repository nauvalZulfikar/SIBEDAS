<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kecamatan_stats', function (Blueprint $table) {
            // Realisasi retribusi (dari pbg_task_retributions) untuk analisis gap potensi vs realisasi.
            $table->decimal('actual_retribution_paid', 20, 2)->default(0)->after('without_permit_enriched_retribution');     // SK PBG Terbit (status 20)
            $table->decimal('actual_retribution_pending', 20, 2)->default(0)->after('actual_retribution_paid');             // SKRD keluar, belum terbit
            $table->decimal('total_potensi_combined', 20, 2)->default(0)->after('actual_retribution_pending');              // potensi tanpa-izin + pending
        });
    }

    public function down(): void
    {
        Schema::table('kecamatan_stats', function (Blueprint $table) {
            $table->dropColumn(['actual_retribution_paid', 'actual_retribution_pending', 'total_potensi_combined']);
        });
    }
};
