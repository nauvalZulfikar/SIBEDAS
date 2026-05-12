<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pbb_access_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_email', 191)->nullable();
            $table->string('clearance_required', 16);   // level_1 / level_2 / level_3
            $table->string('endpoint', 128);            // e.g. api.reconciliation.no-satellite-nop
            $table->string('method', 8);                // GET / POST
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('query_params')->nullable();   // masked params (no PII)
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamp('accessed_at')->useCurrent();
            $table->index(['accessed_at', 'clearance_required'], 'pbb_log_time_lvl_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbb_access_log');
    }
};
