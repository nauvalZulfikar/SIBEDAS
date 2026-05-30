<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KecamatanStat extends Model
{
    protected $table = 'kecamatan_stats';

    protected $fillable = [
        'kecamatan', 'district_code', 'min_area_bucket',
        'total_detected',
        'unmatched_count', 'orphan_count',
        'permit_valid_count', 'permit_in_process_count', 'permit_rejected_count',
        'without_permit_total', 'without_permit_area_m2', 'without_permit_retribution',
        'without_permit_enriched_area_m2', 'without_permit_enriched_retribution',
        'actual_retribution_paid', 'actual_retribution_pending', 'total_potensi_combined',
        'pbg_total', 'pbg_terbit', 'pbg_proses', 'pbg_ditolak',
        'notes', 'verified_by', 'verified_at', 'refreshed_at',
    ];

    protected $casts = [
        'without_permit_area_m2'     => 'decimal:2',
        'without_permit_retribution' => 'decimal:2',
        'without_permit_enriched_area_m2'     => 'decimal:2',
        'without_permit_enriched_retribution' => 'decimal:2',
        'actual_retribution_paid'    => 'decimal:2',
        'actual_retribution_pending' => 'decimal:2',
        'total_potensi_combined'     => 'decimal:2',
        'refreshed_at' => 'datetime',
        'verified_at'  => 'datetime',
    ];
}
