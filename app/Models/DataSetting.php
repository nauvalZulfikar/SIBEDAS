<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSetting extends Model
{
    protected $table = "data_settings";
    protected $fillable = [
        "key",
        "value",
        "type"
    ];
}
