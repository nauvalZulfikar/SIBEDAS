<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'username', 'email', 'phone_number', 'role',
        'role_name', 'is_active', 'file', 'expertise', 'experience',
        'is_verif', 'uid', 'status', 'status_name', 'note', 'pbg_task_uid', 'tas_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verif' => 'boolean',
        'file' => 'array', // JSON field casting
    ];

    public function pbgTask()
    {
        return $this->belongsTo(PbgTask::class, 'pbg_task_uid', 'uuid');
    }
}
