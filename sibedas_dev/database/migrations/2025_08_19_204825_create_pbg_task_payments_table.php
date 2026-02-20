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
        Schema::create('pbg_task_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbg_task_id')->constrained('pbg_task', 'id')->onDelete('cascade');
            $table->string('pbg_task_uid');
            $table->string('registration_number');
            $table->string('sts_form_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('pad_amount', 12, 2)->default(0);
            $table->timestamps();
            
            // Add index for better performance
            $table->index('pbg_task_id');
            $table->index('pbg_task_uid');
            $table->index('registration_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbg_task_payments');
    }
};
