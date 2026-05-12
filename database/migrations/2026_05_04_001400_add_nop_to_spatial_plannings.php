<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spatial_plannings', function (Blueprint $table) {
            $table->string('nop', 32)->nullable()->after('id');
            $table->index('nop');
        });
    }

    public function down(): void
    {
        Schema::table('spatial_plannings', function (Blueprint $table) {
            $table->dropIndex(['nop']);
            $table->dropColumn('nop');
        });
    }
};
