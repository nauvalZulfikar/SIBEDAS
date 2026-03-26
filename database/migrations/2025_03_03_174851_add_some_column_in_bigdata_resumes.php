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
            $table->integer('waiting_click_dpmptsp_count')->default(0);
            $table->decimal('waiting_click_dpmptsp_sum', 20,2)->default(0);
            $table->integer('issuance_realization_pbg_count')->default(0);
            $table->decimal('issuance_realization_pbg_sum', 20,2)->default(0);
            $table->integer('process_in_technical_office_count')->default(0);
            $table->decimal('process_in_technical_office_sum', 20,2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bigdata_resumes', function (Blueprint $table) {
            $table->dropColumn('waiting_click_dpmptsp_count');
            $table->dropColumn('waiting_click_dpmptsp_sum');
            $table->dropColumn('issuance_realization_pbg_count');
            $table->dropColumn('issuance_realization_pbg_sum');
            $table->dropColumn('process_in_technical_office_count');
            $table->dropColumn('process_in_technical_office_sum');
        });
    }
};
