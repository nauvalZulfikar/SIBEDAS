<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('detected_buildings', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude',15,8);
            $table->decimal('longitude',15,8);
            $table->decimal('estimated_area_m2',10,2)->nullable();
            $table->decimal('confidence_score',4,3)->nullable();
            $table->string('detection_source',50);
            $table->date('detection_date')->nullable();
            $table->json('geometry_geojson')->nullable();
            $table->unsignedBigInteger('matched_pbg_task_id')->nullable();
            $table->decimal('match_distance_m',8,2)->nullable();
            $table->enum('verification_status',['unverified','confirmed_illegal','confirmed_legal','false_positive','under_review'])->default('unverified');
            $table->string('building_district_name')->nullable();
            $table->string('building_ward_name')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['latitude','longitude']);
            $table->index('detection_source');
            $table->index('verification_status');
            $table->index('matched_pbg_task_id');
            $table->index('building_district_name');
        });
    }
    public function down(): void { Schema::dropIfExists('detected_buildings'); }
};
