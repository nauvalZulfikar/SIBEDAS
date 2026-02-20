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
        Schema::dropIfExists('pbg_task_attachments');
        Schema::create('pbg_task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbg_task_id')->constrained('pbg_task')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('pbg_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_attachments');
    }
};
