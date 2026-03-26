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
        Schema::table('umkms', function (Blueprint $table) {
            // Mengubah kolom 'revenue' menjadi decimal(20, 2)
            $table->decimal('revenue', 20, 2)->change();

            // Mengubah kolom 'land_area' menjadi decimal(20, 2)
            $table->integer('land_area')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            // Mengembalikan kolom 'revenue' ke decimal default (jika ada)
            $table->decimal('revenue')->change();

            // Mengembalikan kolom 'land_area' ke tipe sebelumnya (float atau lainnya)
            $table->float('land_area')->nullable()->change();
        });
    }
};
