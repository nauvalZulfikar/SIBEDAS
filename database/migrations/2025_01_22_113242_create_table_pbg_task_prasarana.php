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
        Schema::create('pbg_task_prasarana', function (Blueprint $table) {
            $table->id();
            $table->string('pbg_task_uid')->nullable();
            $table->integer('prasarana_id')->nullable();
            $table->string('prasarana_type')->nullable();
            $table->string('building_type')->nullable();
            $table->decimal('total')->nullable();
            $table->decimal('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('index_prasarana')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_prasarana');
    }
};
