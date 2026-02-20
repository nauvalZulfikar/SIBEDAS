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
        Schema::create('pbg_task_detail_data_lists', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique(); // UID from response
            $table->string('name'); // Nama data
            $table->text('description')->nullable(); // Deskripsi (bisa null)
            $table->integer('status')->nullable(); // Status (1 = Sesuai, etc)
            $table->string('status_name')->nullable(); // Nama status
            $table->integer('data_type')->nullable(); // Tipe data (1 = Data Teknis Tanah, etc)
            $table->string('data_type_name')->nullable(); // Nama tipe data
            $table->text('file')->nullable(); // Path file
            $table->text('note')->nullable(); // Catatan
            
            // Foreign key ke pbg_task (1 to many relationship)
            $table->string('pbg_task_uuid')->nullable();
            $table->foreign('pbg_task_uuid')->references('uuid')->on('pbg_task')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('uid');
            $table->index('pbg_task_uuid');
            $table->index('status');
            $table->index('data_type');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_detail_data_lists');
    }
};
