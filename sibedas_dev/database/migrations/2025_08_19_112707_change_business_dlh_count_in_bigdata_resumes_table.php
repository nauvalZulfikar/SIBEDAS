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
            $table->renameColumn('non_business_dlh_count', 'business_dlh_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bigdata_resumes', function (Blueprint $table) {
            $table->renameColumn('business_dlh_count', 'non_business_dlh_count');
        });
    }
};
