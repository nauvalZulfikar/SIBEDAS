<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $table = 'taxs';
    protected $fillable = [
        'tax_code',
        'tax_no',
        'npwpd',
        'wp_name',
        'business_name',
        'address',
        'start_validity',
        'end_validity',
        'tax_value',
        'subdistrict',
        'village',
    ];
}
