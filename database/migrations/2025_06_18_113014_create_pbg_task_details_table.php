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
        Schema::create('pbg_task_details', function (Blueprint $table) {
            $table->id();
            
            // Foreign key relationship
            $table->string('pbg_task_uid')->index();
            
            // Basic information
            $table->string('uid')->unique();
            $table->string('nik');
            $table->string('type_card');
            $table->string('ownership')->nullable();
            $table->string('owner_name');
            
            // Owner location information
            $table->bigInteger('ward_id');
            $table->string('ward_name');
            $table->integer('district_id');
            $table->string('district_name');
            $table->integer('regency_id');
            $table->string('regency_name');
            $table->integer('province_id');
            $table->string('province_name');
            $table->text('address');
            
            // Owner contact information
            $table->string('owner_email');
            $table->string('owner_phone');
            
            // User information
            $table->integer('user');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('user_nik');
            
            // User location information
            $table->integer('user_province_id');
            $table->string('user_province_name');
            $table->integer('user_regency_id');
            $table->string('user_regency_name');
            $table->integer('user_district_id');
            $table->string('user_district_name');
            $table->text('user_address');
            
            // Status information
            $table->integer('status');
            $table->string('status_name');
            $table->integer('slf_status')->nullable();
            $table->string('slf_status_name')->nullable();
            $table->integer('sppst_status');
            $table->string('sppst_file')->nullable();
            $table->string('sppst_status_name');
            
            // Files and documents
            $table->string('file_pbg')->nullable();
            $table->date('file_pbg_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('start_date');
            $table->string('document_number')->nullable();
            $table->string('registration_number');
            
            // Application information
            $table->string('function_type')->nullable();
            $table->string('application_type')->nullable();
            $table->string('application_type_name')->nullable();
            $table->string('consultation_type')->nullable();
            $table->string('condition')->nullable();
            $table->string('prototype')->nullable();
            $table->string('permanency')->nullable();
            
            // Building information
            $table->integer('building_type')->nullable();
            $table->string('building_type_name')->nullable();
            $table->string('building_purpose')->nullable();
            $table->string('building_use')->nullable();
            $table->string('occupancy')->nullable();
            $table->string('name_building')->nullable();
            
            // Building dimensions and specifications
            $table->decimal('total_area', 10, 2);
            $table->decimal('area', 10, 2)->nullable();
            $table->string('area_type')->nullable();
            $table->decimal('height', 8, 2);
            $table->integer('floor');
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->integer('basement');
            $table->decimal('basement_height', 8, 2)->nullable();
            $table->decimal('basement_area', 10, 2);
            $table->integer('unit')->nullable();
            
            // Previous information
            $table->decimal('prev_retribution', 15, 2)->nullable();
            $table->string('prev_pbg')->nullable();
            $table->decimal('prev_total_area', 10, 2)->nullable();
            
            // Coefficients
            $table->decimal('koefisien_dasar_bangunan', 8, 4)->nullable();
            $table->decimal('koefisien_lantai_bangunan', 8, 4)->nullable();
            $table->decimal('koefisien_lantai_hijau', 8, 4)->nullable();
            $table->decimal('koefisien_tapak_basement', 8, 4)->nullable();
            $table->decimal('ketinggian_bangunan', 8, 2)->nullable();
            
            // Road information
            $table->string('jalan_arteri')->nullable();
            $table->string('jalan_kolektor')->nullable();
            $table->string('jalan_bangunan')->nullable();
            $table->decimal('gsb', 8, 2)->nullable();
            $table->string('kkr_number')->nullable();
            
            // Unit data as JSON
            $table->json('unit_data')->nullable();
            
            // Additional flags
            $table->boolean('is_mbr')->default(false);
            $table->string('code');
            
            // Building location information
            $table->bigInteger('building_ward_id');
            $table->string('building_ward_name');
            $table->integer('building_district_id');
            $table->string('building_district_name');
            $table->integer('building_regency_id');
            $table->string('building_regency_name');
            $table->integer('building_province_id');
            $table->string('building_province_name');
            $table->text('building_address');
            
            // Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Additional files
            $table->string('building_photo')->nullable();
            $table->string('pbg_parent')->nullable();
            
            // Original created_at from API
            $table->timestamp('api_created_at')->nullable();
            
            $table->timestamps();
            
            // Add foreign key constraint
            $table->foreign('pbg_task_uid')->references('uuid')->on('pbg_task')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_details');
    }
};
