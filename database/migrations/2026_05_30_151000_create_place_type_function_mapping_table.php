<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_type_function_mapping', function (Blueprint $table) {
            // Samakan collation dgn property_enrichment.place_type (utf8mb4_general_ci)
            // supaya JOIN ON place_type tak kena "illegal mix of collations" (DB
            // default sibedas = unicode_ci, jadi harus dipaksa eksplisit).
            // place_type Google Places → fungsi_bg (FK logis ke retribution_estimates.fungsi_bg).
            $table->string('place_type', 100)->collation('utf8mb4_general_ci')->primary();
            $table->string('fungsi_bg', 255);
            $table->enum('confidence', ['auto', 'manual_review', 'low'])->default('auto');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_type_function_mapping');
    }
};
