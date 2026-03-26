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
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            
            // Foreign key reference to pbg_tasks (uid column)
            $table->string('pbg_task_uid');
            $table->foreign('pbg_task_uid')->references('uuid')->on('pbg_task')->onDelete('cascade');

            $table->unsignedBigInteger('user_id'); // Reference to users table
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->unsignedInteger('role'); // Assuming role is numeric
            $table->string('role_name');
            $table->boolean('is_active')->default(true);
            $table->json('file')->nullable(); // Store as JSON if 'file' is an array
            $table->string('expertise')->nullable();
            $table->string('experience')->nullable();
            $table->boolean('is_verif')->default(false);
            $table->string('uid')->unique();
            $table->unsignedTinyInteger('status')->default(0); // Assuming status is a small integer
            $table->string('status_name')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
