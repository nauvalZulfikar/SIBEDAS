<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbgTaskIndexIntegrations extends Model
{
    use HasFactory;

    protected $table = "pbg_task_index_integrations";

    protected $fillable = [
        'pbg_task_uid',
        'indeks_fungsi_bangunan',
        'indeks_parameter_kompleksitas',
        'indeks_parameter_permanensi',
        'indeks_parameter_ketinggian',
        'faktor_kepemilikan',
        'indeks_terintegrasi',
        'total',
    ];

    public function pbg_task(){
        return $this->belongsTo(PbgTask::class, 'pbg_task_uid', 'uuid');
    }
}
