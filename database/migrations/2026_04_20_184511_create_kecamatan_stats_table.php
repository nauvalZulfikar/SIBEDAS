<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecamatan_stats', function (Blueprint $table) {
            $table->id();

            // Identifier
            $table->string('kecamatan', 50)->index();
            $table->integer('district_code')->nullable()->comment('FK districts.district_code');
            $table->unsignedSmallInteger('min_area_bucket')->default(0)->comment('Filter luas minimum: 0/50/100/200/500/1000');

            // Hasil breakdown detected_buildings di kecamatan ini pada bucket luas
            $table->unsignedInteger('total_detected')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0)->comment('matched_pbg_task_id IS NULL');
            $table->unsignedInteger('orphan_count')->default(0)->comment('matched_pbg_task_id menunjuk PBG yg udah dihapus');
            $table->unsignedInteger('permit_valid_count')->default(0)->comment('Match ke PBG SK Terbit (status=20)');
            $table->unsignedInteger('permit_in_process_count')->default(0);
            $table->unsignedInteger('permit_rejected_count')->default(0);
            $table->unsignedInteger('without_permit_total')->default(0)->comment('unmatched + orphan + rejected');

            // Breakdown PBG task records di kecamatan ini (dari pbg_task_details, independen dari detected)
            $table->unsignedInteger('pbg_total')->default(0);
            $table->unsignedInteger('pbg_terbit')->default(0);
            $table->unsignedInteger('pbg_proses')->default(0);
            $table->unsignedInteger('pbg_ditolak')->default(0);

            // Metadata — nullable supaya staff PUTR bisa isi manual kalau perlu audit
            $table->text('notes')->nullable();
            $table->string('verified_by', 100)->nullable()->comment('Staff PUTR yg terakhir mengecek');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('refreshed_at')->nullable()->comment('Terakhir refresh dari compute job');

            $table->timestamps();

            $table->unique(['kecamatan', 'min_area_bucket'], 'uq_kec_bucket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecamatan_stats');
    }
};
