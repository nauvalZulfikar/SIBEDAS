<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PbbKecamatanLookup extends Model
{
    protected $table = 'pbb_kecamatan_lookup';
    protected $primaryKey = 'djp_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'djp_code',
        'kecamatan_name',
        'bps_district_code',
        'nop_count',
        'terbangun_count',
        'sum_luas_bumi_m2',
        'sum_luas_bangunan_m2',
        'kelurahan_count',
        'last_synced_at',
    ];

    protected $casts = [
        'bps_district_code' => 'integer',
        'nop_count' => 'integer',
        'terbangun_count' => 'integer',
        'sum_luas_bumi_m2' => 'integer',
        'sum_luas_bangunan_m2' => 'integer',
        'kelurahan_count' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(PbbRecord::class, 'kecamatan_djp_code', 'djp_code');
    }

    public function kelurahans(): HasMany
    {
        return $this->hasMany(PbbKelurahanLookup::class, 'djp_kec_code', 'djp_code');
    }
}
