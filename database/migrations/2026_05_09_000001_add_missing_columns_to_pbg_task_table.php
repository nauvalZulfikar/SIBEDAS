<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbg_task', function (Blueprint $table) {
            if (!Schema::hasColumn('pbg_task', 'start_date')) {
                $table->date('start_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('pbg_task', 'retribution')) {
                $table->decimal('retribution', 20, 2)->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('pbg_task', 'total_area')) {
                $table->decimal('total_area', 10, 2)->nullable()->after('retribution');
            }
            if (!Schema::hasColumn('pbg_task', 'unit')) {
                $table->integer('unit')->nullable()->after('total_area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pbg_task', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('pbg_task', 'start_date') ? 'start_date' : null,
                Schema::hasColumn('pbg_task', 'retribution') ? 'retribution' : null,
                Schema::hasColumn('pbg_task', 'total_area') ? 'total_area' : null,
                Schema::hasColumn('pbg_task', 'unit') ? 'unit' : null,
            ]));
        });
    }
};
