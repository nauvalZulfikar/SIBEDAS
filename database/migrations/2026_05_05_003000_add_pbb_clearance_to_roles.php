<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->enum('pbb_clearance', ['level_1', 'level_2', 'level_3'])
                ->default('level_1')
                ->after('description');
        });

        // Default mapping: superadmin=L3, admin=L2, operator=L1, user=L1
        DB::table('roles')->where('name', 'superadmin')->update(['pbb_clearance' => 'level_3']);
        DB::table('roles')->where('name', 'admin')->update(['pbb_clearance' => 'level_2']);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('pbb_clearance');
        });
    }
};
