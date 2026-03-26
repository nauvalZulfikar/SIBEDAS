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
        Schema::create('pbg_task', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('application_type')->nullable();
            $table->string('application_type_name')->nullable();
            $table->string('condition')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('document_number')->nullable();
            $table->string('address')->nullable();
            $table->integer('status')->nullable();
            $table->string('status_name')->nullable();
            $table->string('slf_status')->nullable();
            $table->string('slf_status_name')->nullable();
            $table->string('function_type')->nullable();
            $table->string('consultation_type')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('land_certificate_phase')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task');
    }
};
