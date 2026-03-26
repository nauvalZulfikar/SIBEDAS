<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbgTaskPrasarana extends Model
{
    use HasFactory;

    protected $table = "pbg_task_prasarana";

    protected $fillable = [
        'pbg_task_uid',
        'prasarana_id',
        'prasarana_type',
        'building_type',
        'total',
        'quantity',
        'unit',
        'index_prasarana',
        'pbg_task_retribution_id'
    ];

    public function pbg_task_retributions(){
        return $this->hasMany(PbgTaskRetributions::class, 'pbg_task_retribution_id', 'id');
    }
}
