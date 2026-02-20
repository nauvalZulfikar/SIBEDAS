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
        Schema::table('pbg_task_details', function (Blueprint $table) {
            $table->string("nik")->nullable()->change();
            $table->string("type_card")->nullable()->change();
            $table->string("basement")->nullable()->change();
            $table->decimal('latitude', 15, 8)->nullable()->change();
            $table->decimal('longitude', 15, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_details', function (Blueprint $table) {
            $table->string("nik")->nullable()->change();
            $table->string("type_card")->nullable()->change();
            $table->string("basement")->nullable()->change();
            $table->decimal('latitude', 15, 8)->nullable()->change();
            $table->decimal('longitude', 15, 8)->nullable()->change();
        });
    }
};
