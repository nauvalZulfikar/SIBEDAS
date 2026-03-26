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
        Schema::create('calculable_retributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retribution_calculation_id');
            $table->morphs('calculable'); // calculable_id & calculable_type (automatically creates index)
            $table->boolean('is_active')->default(true)->comment('Status aktif calculation');
            $table->timestamp('assigned_at')->useCurrent()->comment('Kapan calculation di-assign');
            $table->text('notes')->nullable()->comment('Catatan assignment');
            $table->timestamps();
            
            // Additional indexes for better performance
            $table->index('is_active');
            $table->index('assigned_at');
            
            // Foreign key constraint
            $table->foreign('retribution_calculation_id')
                  ->references('id')
                  ->on('retribution_calculations')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculable_retributions');
    }
};
