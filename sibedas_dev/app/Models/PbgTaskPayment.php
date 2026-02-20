<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PbgTaskPayment extends Model
{
    protected $fillable = [
        'pbg_task_id',
        'pbg_task_uid',
        // mapped fields
        'row_no',
        'consultation_type',
        'source_registration_number',
        'owner_name',
        'building_location',
        'building_function',
        'building_name',
        'application_date_raw',
        'verification_status',
        'application_status',
        'owner_address',
        'owner_phone',
        'owner_email',
        'note_date_raw',
        'document_shortage_note',
        'image_url',
        'krk_kkpr',
        'krk_number',
        'lh',
        'ska',
        'remarks',
        'helpdesk',
        'person_in_charge',
        'pbg_operator',
        'ownership',
        'taru_potential',
        'agency_validation',
        'retribution_category',
        'ba_tpt_number',
        'ba_tpt_date_raw',
        'ba_tpa_number',
        'ba_tpa_date_raw',
        'skrd_number',
        'skrd_date_raw',
        'ptsp_status',
        'issued_status',
        'payment_date_raw',
        'sts_format',
        'issuance_year',
        'current_year',
        'village',
        'district',
        'building_area',
        'building_height',
        'floor_count',
        'unit_count',
        'proposed_retribution',
        'retribution_total_simbg',
        'retribution_total_pad',
        'penalty_amount',
        'business_category',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'application_date_raw' => 'date',
        'note_date_raw' => 'date',
        'ba_tpt_date_raw' => 'date',
        'ba_tpa_date_raw' => 'date',
        'skrd_date_raw' => 'date',
        'payment_date_raw' => 'date',
        'issuance_year' => 'integer',
        'current_year' => 'integer',
        'floor_count' => 'integer',
        'unit_count' => 'integer',
        'building_area' => 'decimal:2',
        'building_height' => 'decimal:2',
        'proposed_retribution' => 'decimal:2',
        'retribution_total_simbg' => 'decimal:2',
        'retribution_total_pad' => 'decimal:2',
        'penalty_amount' => 'decimal:2'
    ];

    /**
     * Get the PBG task that owns this payment
     */
    public function pbgTask(): BelongsTo
    {
        return $this->belongsTo(PbgTask::class, 'pbg_task_id', 'id');
    }

    /**
     * Clean and convert registration number for matching
     */
    public static function cleanRegistrationNumber(string $registrationNumber): string
    {
        return trim($registrationNumber);
    }

    /**
     * Convert pad amount string to decimal
     */
    public static function convertPadAmount(?string $padAmount): float
    {
        if (empty($padAmount)) {
            return 0.0;
        }

        // Remove dots (thousands separator) and convert to float
        $cleaned = str_replace('.', '', $padAmount);
        $cleaned = str_replace(',', '.', $cleaned); // Handle comma as decimal separator if present
        
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /**
     * Convert date string to proper format
     */
    public static function convertPaymentDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
