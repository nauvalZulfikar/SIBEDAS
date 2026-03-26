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
        Schema::table('pbg_task_prasarana', function (Blueprint $table) {
            $table->decimal('total', 20,2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_prasarana', function (Blueprint $table) {
            $table->decimal('total',20,2)->nullable()->change();
        });
    }
};
