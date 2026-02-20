<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessOrIndustry extends Model
{
    protected $table = "business_or_industries";
    protected $fillable = [
        'nama_kecamatan',
        'nama_kelurahan',
        'nop',
        'nama_wajib_pajak',
        'alamat_wajib_pajak',
        'alamat_objek_pajak',
        'luas_bumi',
        'luas_bangunan',
        'njop_bumi',
        'njop_bangunan',
        'ketetapan',
        'tahun_pajak',
    ];
}
