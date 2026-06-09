<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a derived `kecamatan` column to pbg_task (+ provenance flag).
     *
     * NOT synced from SIMBG — pbg_task.upsert (ServicePbgTask) neither inserts
     * nor updates these columns, so they survive every sync. Populate via
     * `php artisan pbg:backfill-kecamatan` (point-in-polygon first, address
     * fallback). Additive only — does not touch any existing column.
     */
    public function up(): void
    {
        Schema::table('pbg_task', function (Blueprint $table) {
            $table->string('kecamatan', 50)->nullable()->after('address');
            // 'pip' = verified via point-in-polygon on coords; 'address' = parsed
            // from the SIMBG address string; NULL = could not resolve.
            $table->string('kecamatan_source', 10)->nullable()->after('kecamatan');
            $table->index('kecamatan');
        });
    }

    public function down(): void
    {
        Schema::table('pbg_task', function (Blueprint $table) {
            $table->dropIndex(['kecamatan']);
            $table->dropColumn(['kecamatan', 'kecamatan_source']);
        });
    }
};
