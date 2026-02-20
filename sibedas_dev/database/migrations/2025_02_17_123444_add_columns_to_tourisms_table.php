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
        Schema::table('tourisms', function (Blueprint $table) {
            $table->integer('project_id')->nullable()->after('id');
            $table->string('jenis_proyek')->nullable()->after('project_id');
            $table->string('nib')->nullable()->after('jenis_proyek');
            $table->integer('business_scale_id')->nullable()->after('project_name');
            $table->date('terbit_oss')->nullable()->after('business_name');
            $table->string('status_penanaman_modal')->nullable()->after('terbit_oss');
            $table->string('business_form')->nullable()->after('status_penanaman_modal');
            $table->string('uraian_resiko_proyek')->nullable()->after('business_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tourisms', function (Blueprint $table) {
            $table->dropColumn(['project_id', 'jenis_proyek', 'nib', 'business_scale_id', 'terbit_oss',
                                'status_penanaman_modal', 'business_form', 'uraian_resiko_proyek']);
        });
    }
};
