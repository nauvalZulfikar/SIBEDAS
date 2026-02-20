<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = [
        'nomor_pelanggan',
        'kota_pelayanan',
        'nama',
        'alamat',
        'latitude',
        'longitude',
    ];

    
}
