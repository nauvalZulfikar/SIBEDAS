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
        Schema::table('pbg_task_index_integrations', function (Blueprint $table) {
            $constraintExists = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = 'pbg_task_index_integrations' 
                AND index_name = 'pbg_task_index_integrations_pbg_task_uid_unique'
            ");

            if ($constraintExists[0]->count > 0) {
                $table->dropUnique('pbg_task_index_integrations_pbg_task_uid_unique');
            }
            $table->string('pbg_task_uid')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_index_integrations', function (Blueprint $table) {
            $table->dropUnique('pbg_task_index_integrations_pbg_task_uid_unique');
            $table->string('pbg_task_uid')->unique()->change();
        });
    }
};
