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
        Schema::table('spatial_plannings', function (Blueprint $table) {
            $table->text('no_tapak')->nullable();
            $table->text('no_skkl')->nullable();
            $table->text('no_ukl')->nullable();
            $table->string('building_function')->nullable();
            $table->string('sub_building_function')->nullable();
            $table->integer('number_of_floors')->default(1);
            $table->decimal('land_area', 18, 6)->nullable();
            $table->decimal('site_bcr', 10, 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_plannings', function (Blueprint $table) {
            $table->dropColumn([
                'no_tapak',
                'no_skkl',
                'no_ukl',
                'building_function',
                'sub_building_function',
                'number_of_floors',
                'land_area',
                'site_bcr',
            ]);
        });
    }
};
