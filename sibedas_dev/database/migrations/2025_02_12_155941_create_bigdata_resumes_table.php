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
        Schema::create('bigdata_resumes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_datasource_id');
            $table->integer('potention_count')->default(0);
            $table->decimal('potention_sum', 20, 2)->default(0);
            $table->integer('non_verified_count')->default(0);
            $table->decimal('non_verified_sum', 20, 2)->default(0);
            $table->integer('verified_count')->default(0);
            $table->decimal('verified_sum', 20, 2)->default(0);
            $table->integer('business_count')->default(0);
            $table->decimal('business_sum', 20, 2)->default(0);
            $table->integer('non_business_count')->default(0);
            $table->decimal('non_business_sum', 20, 2)->default(0);
            $table->timestamps();
            $table->foreign('import_datasource_id')->references('id')->on('import_datasources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bigdata_resumes');
    }
};
