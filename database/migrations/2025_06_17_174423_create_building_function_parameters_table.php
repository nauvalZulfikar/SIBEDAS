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
        Schema::create('building_function_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_function_id')->constrained('building_functions')->onDelete('cascade');
            $table->decimal('fungsi_bangunan', 10, 6)->nullable()->comment('Parameter fungsi bangunan');
            $table->decimal('ip_permanen', 10, 6)->nullable()->comment('Parameter IP permanen');
            $table->decimal('ip_kompleksitas', 10, 6)->nullable()->comment('Parameter IP kompleksitas');
            $table->decimal('indeks_lokalitas', 10, 6)->nullable()->comment('Parameter indeks lokalitas');
            $table->decimal('asumsi_prasarana', 8, 6)->nullable()->comment('Parameter asumsi prasarana untuk perhitungan retribusi');
            $table->decimal('koefisien_dasar', 15, 6)->nullable()->comment('Koefisien dasar perhitungan');
            $table->timestamps();
            
            // Unique constraint untuk 1:1 relationship
            $table->unique('building_function_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_function_parameters');
    }
};
