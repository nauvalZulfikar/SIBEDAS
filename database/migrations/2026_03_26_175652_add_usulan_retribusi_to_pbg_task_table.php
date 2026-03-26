<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbg_task', function (Blueprint $table) {
            $table->decimal('usulan_retribusi', 20, 2)->default(0)->after('retribution')->comment('Usulan retribusi = retribusi_per_m2 x total_area x unit');
        });
    }

    public function down(): void
    {
        Schema::table('pbg_task', function (Blueprint $table) {
            $table->dropColumn('usulan_retribusi');
        });
    }
};
