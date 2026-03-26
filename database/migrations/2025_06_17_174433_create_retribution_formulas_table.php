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
        Schema::create('retribution_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_function_id')->constrained('building_functions')->onDelete('cascade');
            $table->string('name', 255)->comment('Nama formula');
            $table->integer('floor_number')->comment('Nomor lantai (1, 2, 3, dst, 0=semua lantai)');
            $table->text('formula_expression')->comment('Rumus matematika untuk perhitungan');
            $table->timestamps();
            
            // Indexes untuk performa
            $table->index(['building_function_id', 'floor_number'], 'idx_building_floor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retribution_formulas');
    }
};
