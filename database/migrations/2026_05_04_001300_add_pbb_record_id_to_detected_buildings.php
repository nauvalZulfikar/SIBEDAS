<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detected_buildings', function (Blueprint $table) {
            $table->unsignedBigInteger('pbb_record_id')->nullable()->after('matched_pbg_task_id');
            $table->index('pbb_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('detected_buildings', function (Blueprint $table) {
            $table->dropIndex(['pbb_record_id']);
            $table->dropColumn('pbb_record_id');
        });
    }
};
