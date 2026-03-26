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
        Schema::create('building_functions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Kode unik fungsi bangunan');
            $table->string('name', 255)->comment('Nama fungsi bangunan');
            $table->text('description')->nullable()->comment('Deskripsi detail fungsi bangunan');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('ID parent untuk hierarki');
            $table->foreign('parent_id')->references('id')->on('building_functions')->onDelete('cascade');
            $table->integer('level')->default(0)->comment('Level hierarki (0=root, 1=child, dst)');
            $table->integer('sort_order')->default(0)->comment('Urutan tampilan');
            $table->decimal('base_tariff', 15, 2)->nullable()->comment('Tarif dasar per m2');
            
            // Indexes untuk performa
            $table->index(['parent_id', 'level']);
            $table->index(['level', 'sort_order']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_functions');
    }
};
