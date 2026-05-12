<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // decimal(6,2) only fits ±9999.99 — kelurahan with terbangun >> sat
        // (e.g. coverage gap = sat 0 vs terbangun 200) yields gap_pct that
        // overflows. Widen to decimal(10,2) = ±99,999,999.99 which is plenty.
        DB::statement('ALTER TABLE reconciliation_summary MODIFY gap_pct DECIMAL(10,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reconciliation_summary MODIFY gap_pct DECIMAL(6,2) NULL');
    }
};
