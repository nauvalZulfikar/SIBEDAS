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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('no');
            $table->string('business_name');
            $table->string('npwpd');
            $table->string('advertisement_type');
            $table->string('advertisement_content');
            $table->string('business_address');
            $table->string('advertisement_location');
            $table->integer('village_code');
            $table->integer('district_code');
            $table->float('length');
            $table->float('width');
            $table->string('viewing_angle');
            $table->string('face');
            $table->string('area');
            $table->string('angle');
            $table->string('contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
