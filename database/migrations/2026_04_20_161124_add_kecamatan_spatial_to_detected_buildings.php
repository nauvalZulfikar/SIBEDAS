<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detected_buildings', function (Blueprint $table) {
            $table->string('kecamatan', 50)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('detected_buildings', function (Blueprint $table) {
            $table->dropIndex(['kecamatan']);
            $table->dropColumn('kecamatan');
        });
    }
};
