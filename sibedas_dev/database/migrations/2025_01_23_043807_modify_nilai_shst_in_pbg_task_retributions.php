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
        Schema::table('pbg_task_retributions', function (Blueprint $table) {
            $table->decimal('nilai_shst', 20,2)->nullable()->change();
            $table->decimal('nilai_retribusi_bangunan', 20,2)->nullable()->change();
            $table->decimal('indeks_terintegrasi', 20,15)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_retributions', function (Blueprint $table) {
            $table->decimal('nilai_shst', 20,2)->nullable()->change();
            $table->decimal('nilai_retribusi_bangunan',20,2)->nullable()->change();
            $table->decimal('indeks_terintegrasi',20,15)->nullable()->change();
        });
    }
};
