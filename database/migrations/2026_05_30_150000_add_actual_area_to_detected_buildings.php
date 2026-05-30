<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detected_buildings', function (Blueprint $table) {
            // Luas estimasi yang lebih realistis daripada estimated_area_m2 (bounding box).
            // Footprint Microsoft cuma punya bbox → dikoreksi pakai fill-factor empiris (0.62).
            // OSM dgn polygon nyata (>5 titik) → ST_Area sebenarnya dari PostGIS.
            // NULL = belum di-recompute → query fallback COALESCE ke estimated_area_m2 (legacy).
            $table->decimal('actual_area_m2', 10, 2)->nullable()->after('estimated_area_m2');

            // Flag outlier: bbox raksasa yang mustahil (mis. blob ke-merge), tetap perlu
            // review manual karena fill-factor seragam pun gak bisa membenarkannya.
            $table->boolean('area_suspect')->default(false)->after('actual_area_m2');

            $table->index('area_suspect');
        });
    }

    public function down(): void
    {
        Schema::table('detected_buildings', function (Blueprint $table) {
            $table->dropIndex(['area_suspect']);
            $table->dropColumn(['actual_area_m2', 'area_suspect']);
        });
    }
};
