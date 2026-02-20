<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourismBasedKBLI extends Model
{
    protected $table = 'v_tourisms_based_kbli';
    protected $primaryKey = null;

    public $timestamps = false;
    protected $fillable = ['kbli_title', 'total_records'];
}
