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
        Schema::create('umkm', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->string('business_name');
            $table->string('business_address');
            $table->string('business_desc');
            $table->string('business_contact');
            $table->string('business_id_number')->nullable();
            $table->integer('business_scale_id');
            $table->string('owner_id');
            $table->string('owner_name');
            $table->string('owner_address');
            $table->string('owner_contact');
            $table->string('business_type');
            $table->string('business_form');
            $table->decimal('revenue');
            $table->string('village_code');
            $table->integer('distric_code');
            $table->integer('number_of_employee');
            $table->float('land_area')->nullable();
            $table->integer('permit_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umkm');
    }
};
