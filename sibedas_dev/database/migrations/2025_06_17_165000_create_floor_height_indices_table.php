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
        Schema::create('floor_height_indices', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_number')->comment('Nomor lantai');
            $table->decimal('ip_ketinggian', 10, 6)->comment('Indeks ketinggian per lantai');
            $table->text('description')->nullable()->comment('Deskripsi indeks ketinggian');
            $table->timestamps();
            
            // Unique constraint untuk floor_number
            $table->unique('floor_number');
            $table->index('floor_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floor_height_indices');
    }
}; 