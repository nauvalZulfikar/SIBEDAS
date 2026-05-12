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
        'without_permit_total',
        'pbg_total', 'pbg_terbit', 'pbg_proses', 'pbg_ditolak',
        'notes', 'verified_by', 'verified_at', 'refreshed_at',
    ];

    protected $casts = [
        'refreshed_at' => 'datetime',
        'verified_at'  => 'datetime',
    ];
}
