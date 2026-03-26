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
            $indexes = DB::select("SHOW INDEXES FROM task_assignments WHERE Key_name = 'task_assignments_email_unique'");

            if (!empty($indexes)) {
                $table->dropUnique('task_assignments_email_unique');
            }

            $indexes = DB::select("SHOW INDEXES FROM task_assignments WHERE Key_name = 'task_assignments_username_unique'");

            if (!empty($indexes)) {
                $table->dropUnique('task_assignments_username_unique');
            }
            $table->string('email')->nullable()->change();
            $table->string('username')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            //
        });
    }
};
