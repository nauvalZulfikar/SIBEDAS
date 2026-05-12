<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PbbRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'nop',
        'nama_wp',
        'alamat',
        'terbangun_flag',
        'nama_bangunan',
        'luas_bumi',
        'luas_bangunan',
        'kecamatan_djp_code',
        'desa_djp_code',
        'kecamatan_name',
        'kelurahan_name',
        'source_sheet',
        'imported_at',
    ];

    protected $casts = [
        'luas_bumi' => 'integer',
        'luas_bangunan' => 'integer',
        'imported_at' => 'datetime',
    ];

    public function kecamatanLookup(): BelongsTo
    {
        return $this->belongsTo(PbbKecamatanLookup::class, 'kecamatan_djp_code', 'djp_code');
    }

    public function detectedBuildings(): HasMany
    {
        return $this->hasMany(DetectedBuilding::class, 'pbb_record_id');
    }

    public function spatialPlannings(): HasMany
    {
        return $this->hasMany(SpatialPlanning::class, 'nop', 'nop');
    }

    public function scopeTerbangun($query)
    {
        return $query->where('luas_bangunan', '>', 0);
    }

    public function scopeLahanKosong($query)
    {
        return $query->where('luas_bangunan', '=', 0);
    }

    public function scopeByKec($query, string $name)
    {
        return $query->where('kecamatan_name', strtoupper($name));
    }

    public function scopeByKelurahan($query, string $name)
    {
        return $query->where('kelurahan_name', strtoupper($name));
    }

    public function scopeByDjpCode($query, string $kecCode, ?string $desaCode = null)
    {
        $query->where('kecamatan_djp_code', $kecCode);
        if ($desaCode !== null) {
            $query->where('desa_djp_code', $desaCode);
        }
        return $query;
    }
}
