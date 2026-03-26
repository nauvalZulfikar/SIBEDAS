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
            $table->string('resume_type')->nullable();
            $table->integer('business_rab_count')->default(0);
            $table->integer('business_krk_count')->default(0);
            $table->integer('non_business_rab_count')->default(0);
            $table->integer('non_business_krk_count')->default(0);
            $table->integer('non_business_dlh_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bigdata_resumes', function (Blueprint $table) {
            $table->dropColumn('resume_type');
            $table->dropColumn('business_rab_count');
            $table->dropColumn('business_krk_count');
            $table->dropColumn('non_business_rab_count');
            $table->dropColumn('non_business_krk_count');
            $table->dropColumn('non_business_dlh_count');
        });
    }
};
