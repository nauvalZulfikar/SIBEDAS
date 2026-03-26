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
            $table->unsignedBigInteger('pbg_task_retribution_id')->nullable();  // nullable in case some records do not match

            // Step 2: Define the foreign key relation from `table3` to `table2`
            $table->foreign('pbg_task_retribution_id')->references('id')->on('pbg_task_retributions')->onDelete('cascade');
        });

        \DB::table('pbg_task_prasarana')
            ->join('pbg_task', 'pbg_task.uuid', '=', 'pbg_task_prasarana.pbg_task_uid') // Relating pbg_task_prasarana to pbg_task
            ->join('pbg_task_retributions', 'pbg_task_retributions.pbg_task_uid', '=', 'pbg_task.uuid') // Relating pbg_task_retributions to pbg_task
            ->whereNotNull('pbg_task_retributions.id') // Ensure the `pbg_task_retributions` id exists
            ->update(['pbg_task_prasarana.pbg_task_retribution_id' => \DB::raw('pbg_task_retributions.id')]); // Set the foreign key
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_prasarana', function (Blueprint $table) {
            $table->dropForeign(['pbg_task_retribution_id']);
            $table->dropColumn('pbg_task_retribution_id');
        });
    }
};
