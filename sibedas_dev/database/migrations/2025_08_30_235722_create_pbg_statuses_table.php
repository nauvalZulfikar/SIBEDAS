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
        Schema::create('pbg_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('pbg_task_uuid');
            $table->integer('status');
            $table->string('status_name');
            $table->integer('slf_status')->nullable();
            $table->string('slf_status_name')->nullable();
            $table->date('due_date')->nullable();
    
            // nested "data"
            $table->string('uid')->nullable();
            $table->text('note')->nullable();
            $table->string('file')->nullable();
            $table->date('data_due_date')->nullable();
            $table->timestamp('data_created_at')->nullable();
    
            $table->json('slf_data')->nullable(); // kalau nanti slf_data ada struktur JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_statuses');
    }
};
