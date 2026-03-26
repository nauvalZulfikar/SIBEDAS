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
        Schema::dropIfExists('tourisms'); 
        Schema::create('tourisms', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->string('project_id');
            $table->string('project_type_id');
            $table->string('nib');
            $table->string('business_name');
            $table->datetime('oss_publication_date');
            $table->string('investment_status_description');
            $table->string('business_form');
            $table->string('project_risk');
            $table->string('project_name');
            $table->string('business_scale');
            $table->string('business_address');
            $table->integer('district_code');
            $table->integer('village_code');
            $table->string('longitude');
            $table->string('latitude');
            $table->datetime('project_submission_date');
            $table->string('kbli');
            $table->string('kbli_title');
            $table->string('supervisory_sector');
            $table->string('user_name');
            $table->string('email');
            $table->string('contact');
            $table->string('land_area_in_m2');
            $table->string('investment_amount');
            $table->string('tki');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tourisms');
    }
};
