<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop unused retribution-related tables that have been replaced with new schema
     */
    public function up(): void
    {
        // Disable foreign key checks to avoid constraint errors
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        try {
            // Drop tables - order doesn't matter now since FK checks are disabled
            
            // 1. Drop retribution_proposals
            if (Schema::hasTable('retribution_proposals')) {
                Schema::dropIfExists('retribution_proposals');
            }
            
            // 2. Drop building_function_parameters
            if (Schema::hasTable('building_function_parameters')) {
                Schema::dropIfExists('building_function_parameters');
            }
            
            // 3. Drop retribution_formulas
            if (Schema::hasTable('retribution_formulas')) {
                Schema::dropIfExists('retribution_formulas');
            }
            
            // 4. Drop building_functions
            if (Schema::hasTable('building_functions')) {
                Schema::dropIfExists('building_functions');
            }
            
            // 5. Drop floor_height_indices
            if (Schema::hasTable('floor_height_indices')) {
                Schema::dropIfExists('floor_height_indices');
            }
            
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This rollback recreates the tables, but they will be empty.
     * Use this only if you need to rollback immediately after running the migration.
     */
    public function down(): void
    {
        // Recreate building_functions table (parent first)
        Schema::create('building_functions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Kode unik fungsi bangunan');
            $table->string('name', 255)->comment('Nama fungsi bangunan');
            $table->text('description')->nullable()->comment('Deskripsi detail fungsi bangunan');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('ID parent untuk hierarki');
            $table->foreign('parent_id')->references('id')->on('building_functions')->onDelete('cascade');
            $table->integer('level')->default(0)->comment('Level hierarki (0=root, 1=child, dst)');
            $table->integer('sort_order')->default(0)->comment('Urutan tampilan');
            $table->decimal('base_tariff', 15, 2)->nullable()->comment('Tarif dasar per m2');
            
            // Indexes untuk performa
            $table->index(['parent_id', 'level']);
            $table->index(['level', 'sort_order']);
            $table->timestamps();
        });

        // Recreate building_function_parameters table
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

        // Recreate retribution_formulas table
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

        // Recreate retribution_proposals table
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

        // Recreate floor_height_indices table
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
};
