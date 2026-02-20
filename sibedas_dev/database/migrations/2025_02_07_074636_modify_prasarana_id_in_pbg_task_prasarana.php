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
        Schema::table('pbg_task_prasarana', function (Blueprint $table) {
            $constraintExists = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = 'pbg_task_prasarana' 
                AND index_name = 'pbg_task_prasarana_prasarana_id_unique'
            ");

            if ($constraintExists[0]->count > 0) {
                $table->dropUnique('pbg_task_prasarana_prasarana_id_unique');
            }
            $table->integer('prasarana_id')->nullable()->unique()->change();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_prasarana', function (Blueprint $table) {
            $table->dropUnique('pbg_task_prasarana_prasarana_id_unique');
            $table->integer('prasarana_id')->nullable()->unique()->change();
        });
    }
};
