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
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->json('expertise')->nullable()->change();
            $table->json('experience')->nullable()->change();
            $table->bigInteger('ta_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->text('expertise')->nullable()->change();
            $table->text('experience')->nullable()->change();
            $table->dropColumn('ta_id');
        });
    }
};
