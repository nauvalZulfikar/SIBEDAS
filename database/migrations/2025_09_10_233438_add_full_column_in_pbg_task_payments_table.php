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
        Schema::table('pbg_task_payments', function (Blueprint $table) {
            // Drop existing foreign key if present
            if (Schema::hasColumn('pbg_task_payments', 'pbg_task_id')) {
                $table->dropForeign(['pbg_task_id']);
                // Make column nullable
                $table->unsignedBigInteger('pbg_task_id')->nullable()->change();
                // Recreate foreign key
                $table->foreign('pbg_task_id')->references('id')->on('pbg_task')->cascadeOnDelete();
            }

            // Drop legacy columns if no longer needed
            if (Schema::hasColumn('pbg_task_payments', 'registration_number')) {
                $table->dropIndex(['registration_number']);
                $table->dropColumn('registration_number');
            }
            if (Schema::hasColumn('pbg_task_payments', 'sts_form_number')) {
                $table->dropColumn('sts_form_number');
            }
            if (Schema::hasColumn('pbg_task_payments', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::hasColumn('pbg_task_payments', 'pad_amount')) {
                $table->dropColumn('pad_amount');
            }

            // Make pbg_task_uid nullable
            if (Schema::hasColumn('pbg_task_payments', 'pbg_task_uid')) {
                $table->string('pbg_task_uid')->nullable()->change();
            }

            // Add new columns (renamed for table conventions)
            $table->integer('row_no')->nullable();
            $table->string('consultation_type')->nullable();
            $table->string('source_registration_number')->nullable();
            $table->string('owner_name')->nullable();
            $table->text('building_location')->nullable();
            $table->string('building_function')->nullable();
            $table->string('building_name')->nullable();
            $table->date('application_date_raw')->nullable();
            $table->string('verification_status')->nullable();
            $table->string('application_status')->nullable();
            $table->text('owner_address')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('owner_email')->nullable();
            $table->date('note_date_raw')->nullable();
            $table->text('document_shortage_note')->nullable();
            $table->string('image_url')->nullable();
            $table->string('krk_kkpr')->nullable();
            $table->string('krk_number')->nullable();
            $table->string('lh')->nullable();
            $table->string('ska')->nullable();
            $table->string('remarks')->nullable();
            $table->string('helpdesk')->nullable();
            $table->string('person_in_charge')->nullable();
            $table->string('pbg_operator')->nullable();
            $table->string('ownership')->nullable();
            $table->string('taru_potential')->nullable();
            $table->string('agency_validation')->nullable();
            $table->string('retribution_category')->nullable();
            $table->string('ba_tpt_number')->nullable();
            $table->date('ba_tpt_date_raw')->nullable();
            $table->string('ba_tpa_number')->nullable();
            $table->date('ba_tpa_date_raw')->nullable();
            $table->string('skrd_number')->nullable();
            $table->date('skrd_date_raw')->nullable();
            $table->string('ptsp_status')->nullable();
            $table->string('issued_status')->nullable();
            $table->date('payment_date_raw')->nullable();
            $table->string('sts_format')->nullable();
            $table->integer('issuance_year')->nullable();
            $table->integer('current_year')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->decimal('building_area', 18, 2)->nullable()->default(0);
            $table->decimal('building_height', 18, 2)->nullable()->default(0);
            $table->integer('floor_count')->nullable();
            $table->integer('unit_count')->nullable();
            $table->decimal('proposed_retribution', 18, 2)->nullable()->default(0);
            $table->decimal('retribution_total_simbg', 18, 2)->nullable()->default(0);
            $table->decimal('retribution_total_pad', 18, 2)->nullable()->default(0);
            $table->decimal('penalty_amount', 18, 2)->nullable()->default(0);
            $table->string('business_category')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbg_task_payments', function (Blueprint $table) {
            if (Schema::hasColumn('pbg_task_payments', 'pbg_task_id')) {
                // Drop FK, revert to not nullable, recreate FK
                $table->dropForeign(['pbg_task_id']);
                $table->unsignedBigInteger('pbg_task_id')->nullable(false)->change();
                $table->foreign('pbg_task_id')->references('id')->on('pbg_task')->cascadeOnDelete();
            }

            // Revert pbg_task_uid to not nullable
            if (Schema::hasColumn('pbg_task_payments', 'pbg_task_uid')) {
                $table->string('pbg_task_uid')->nullable(false)->change();
            }

            // Drop the added columns
            $columns = [
                'row_no','consultation_type','source_registration_number','owner_name','building_location','building_function','building_name','application_date_raw',
                'verification_status','application_status','owner_address','owner_phone','owner_email','note_date_raw','document_shortage_note',
                'image_url','krk_kkpr','krk_number','lh','ska','remarks','helpdesk','person_in_charge','pbg_operator','ownership','taru_potential',
                'agency_validation','retribution_category','ba_tpt_number','ba_tpt_date_raw','ba_tpa_number','ba_tpa_date_raw',
                'skrd_number','skrd_date_raw','ptsp_status','issued_status','payment_date_raw','sts_format','issuance_year',
                'current_year','village','district','building_area','building_height','floor_count','unit_count','proposed_retribution','retribution_total_simbg',
                'retribution_total_pad','penalty_amount','business_category'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('pbg_task_payments', $col)) {
                    $table->dropColumn($col);
                }
            }

            // Restore legacy columns
            if (!Schema::hasColumn('pbg_task_payments', 'registration_number')) {
                $table->string('registration_number');
                $table->index('registration_number');
            }
            if (!Schema::hasColumn('pbg_task_payments', 'sts_form_number')) {
                $table->string('sts_form_number')->nullable();
            }
            if (!Schema::hasColumn('pbg_task_payments', 'payment_date')) {
                $table->date('payment_date')->nullable();
            }
            if (!Schema::hasColumn('pbg_task_payments', 'pad_amount')) {
                $table->decimal('pad_amount', 18, 2)->default(0);
            }
        });
    }
};
