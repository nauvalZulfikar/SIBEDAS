<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PbbKelurahanLookup extends Model
{
    protected $table = 'pbb_kelurahan_lookup';

    protected $fillable = [
        'djp_kec_code',
        'djp_desa_code',
        'kelurahan_name',
        'bps_village_code',
        'nop_count',
        'terbangun_count',
        'sum_luas_bangunan_m2',
        'last_synced_at',
    ];

    protected $casts = [
        'nop_count' => 'integer',
        'terbangun_count' => 'integer',
        'sum_luas_bangunan_m2' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(PbbKecamatanLookup::class, 'djp_kec_code', 'djp_code');
    }

    public function records(): HasMany
    {
        return $this->hasMany(PbbRecord::class, 'desa_djp_code', 'djp_desa_code')
            ->where('kecamatan_djp_code', $this->djp_kec_code);
    }
}
