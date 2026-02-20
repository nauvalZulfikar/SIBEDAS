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
        Schema::create('retribution_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_planning_id')->nullable()->constrained('spatial_plannings')->onDelete('cascade');
            $table->foreignId('building_function_id')->constrained('building_functions')->onDelete('cascade');
            $table->foreignId('retribution_formula_id')->constrained('retribution_formulas')->onDelete('cascade');
            $table->string('proposal_number')->unique()->comment('Nomor usulan retribusi');
            $table->integer('floor_number')->comment('Nomor lantai (1, 2, 3, dst)');
            $table->decimal('floor_area', 15, 6)->comment('Luas lantai ini (m2)');
            $table->decimal('total_building_area', 15, 6)->comment('Total luas bangunan (m2)');
            $table->decimal('ip_ketinggian', 10, 6)->comment('IP ketinggian untuk lantai ini');
            $table->decimal('floor_retribution_amount', 15, 2)->comment('Jumlah retribusi untuk lantai ini');
            $table->decimal('total_retribution_amount', 15, 2)->comment('Total retribusi keseluruhan');
            $table->json('calculation_parameters')->nullable()->comment('Parameter yang digunakan dalam perhitungan');
            $table->json('calculation_breakdown')->nullable()->comment('Breakdown detail perhitungan');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->datetime('calculated_at')->comment('Waktu perhitungan dilakukan');
            $table->timestamps();
            
            // Indexes untuk performa
            $table->index(['spatial_planning_id', 'floor_number'], 'idx_spatial_floor');
            $table->index(['building_function_id'], 'idx_function');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retribution_proposals');
    }
}; 