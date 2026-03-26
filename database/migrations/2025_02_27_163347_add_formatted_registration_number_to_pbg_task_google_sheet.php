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
        Schema::table('pbg_task_google_sheet', function (Blueprint $table) {
            $table->string('formatted_registration_number')->nullable()->after('no_registrasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_google_sheet', function (Blueprint $table) {
            $table->dropColumn('formatted_registration_number');
        });
    }
};
