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
        Schema::table('bigdata_resumes', function (Blueprint $table) {
            $table->integer('spatial_count')->default(0);
            $table->decimal('spatial_sum', 20,2)->default(0);
            $table->string('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bigdata_resumes', function (Blueprint $table) {
            $table->dropColumn('spatial_count');
            $table->dropColumn('spatial_sum');
            $table->dropColumn('year');
        });
    }
};
