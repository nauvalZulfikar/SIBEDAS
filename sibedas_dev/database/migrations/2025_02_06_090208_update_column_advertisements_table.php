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
        //
        Schema::table('advertisements', function (Blueprint $table) {
            // Mengubah tipe data kolom 'village_code' menjadi BIGINT
            $table->string('village_code')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('advertisements', function (Blueprint $table) {
            // Mengubah kembali tipe data kolom 'village_code' ke tipe sebelumnya (misalnya INT)
            $table->integer('village_code')->change();
        });
    }
};
