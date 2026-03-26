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
        Schema::create('pbg_task_index_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('pbg_task_uid')->nullable();
            $table->string('indeks_fungsi_bangunan')->nullable();
            $table->string('indeks_parameter_kompleksitas')->nullable();
            $table->string('indeks_parameter_permanensi')->nullable();
            $table->string('indeks_parameter_ketinggian')->nullable();
            $table->string('faktor_kepemilikan')->nullable();
            $table->string('indeks_terintegrasi')->nullable();
            $table->decimal('total')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_index_integrations');
    }
};
