<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbgTaskAttachment extends Model
{
    protected $fillable = ['pbg_task_id', 'file_name', 'file_path', 'pbg_type'];

    public function task(){
        return $this->belongsTo(PbgTask::class);
    }
}
