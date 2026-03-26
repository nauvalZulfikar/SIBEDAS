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
        // 1. Tabel Fungsi Bangunan (Simplified)
        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Kode fungsi bangunan');
            $table->string('name', 100)->comment('Nama fungsi bangunan');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent ID untuk hierarki');
            $table->tinyInteger('level')->default(1)->comment('Level hierarki (1=parent, 2=child)');
            $table->boolean('is_free')->default(false)->comment('Apakah gratis (keagamaan, MBR)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['parent_id', 'level']);
            $table->index('is_active');
            $table->foreign('parent_id')->references('id')->on('building_types')->onDelete('cascade');
        });

        // 2. Tabel Parameter Indeks (Simplified)
        Schema::create('retribution_indices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_type_id');
            $table->decimal('coefficient', 8, 4)->comment('Koefisien fungsi bangunan');
            $table->decimal('ip_permanent', 8, 4)->comment('Indeks Permanensi');
            $table->decimal('ip_complexity', 8, 4)->comment('Indeks Kompleksitas');
            $table->decimal('locality_index', 8, 4)->comment('Indeks Lokalitas');
            $table->decimal('infrastructure_factor', 8, 4)->default(0.5)->comment('Faktor prasarana (default 50%)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique('building_type_id');
            $table->foreign('building_type_id')->references('id')->on('building_types')->onDelete('cascade');
        });

        // 3. Tabel Indeks Ketinggian (Simplified)
        Schema::create('height_indices', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('floor_number')->unique()->comment('Nomor lantai');
            $table->decimal('height_index', 8, 6)->comment('Indeks ketinggian');
            $table->timestamps();
            
            $table->index('floor_number');
        });

        // 4. Tabel Konfigurasi Global
        Schema::create('retribution_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->comment('Kunci konfigurasi');
            $table->decimal('value', 15, 2)->comment('Nilai konfigurasi');
            $table->string('description', 200)->comment('Deskripsi konfigurasi');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Tabel Hasil Perhitungan (Simplified)
        Schema::create('retribution_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('calculation_id', 20)->unique()->comment('ID unik perhitungan');
            $table->unsignedBigInteger('building_type_id');
            $table->tinyInteger('floor_number');
            $table->decimal('building_area', 12, 2)->comment('Luas bangunan (m2)');
            $table->decimal('retribution_amount', 15, 2)->comment('Jumlah retribusi');
            $table->json('calculation_detail')->comment('Detail perhitungan');
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['building_type_id', 'floor_number']);
            $table->index('calculated_at');
            $table->foreign('building_type_id')->references('id')->on('building_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retribution_calculations');
        Schema::dropIfExists('retribution_configs');
        Schema::dropIfExists('height_indices');
        Schema::dropIfExists('retribution_indices');
        Schema::dropIfExists('building_types');
    }
}; 