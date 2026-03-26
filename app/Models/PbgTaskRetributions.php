<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PbgTaskRetributions extends Model
{
    use HasFactory;
    protected $table = "pbg_task_retributions";

    protected $fillable = [
        'detail_id',
        'detail_created_at',
        'detail_updated_at',
        'detail_uid',
        'luas_bangunan',
        'indeks_lokalitas',
        'wilayah_shst',
        'kegiatan_id',
        'kegiatan_name',
        'nilai_shst',
        'indeks_terintegrasi',
        'indeks_bg_terbangun',
        'nilai_retribusi_bangunan',
        'nilai_prasarana',
        'created_by',
        'pbg_document',
        'underpayment',
        'skrd_amount',
        'pbg_task_uid'
    ];

    public function pbg_task(){
        return $this->belongsTo(PbgTask::class, 'pbg_task_uid', 'uuid');
    }

    public function pbg_task_prasarana(){
        return $this->hasMany(PbgTaskPrasarana::class, 'pbg_task_retribution_id', 'id');
    }
}
