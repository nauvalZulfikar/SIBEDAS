<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportDatasource extends Model
{
    use HasFactory;
    protected $table = 'import_datasources';
    protected $fillable = [
        'id',
        'message',
        'response_body',
        'status',
        'start_time',
        'finish_time',
        'failed_uuid'
    ];
}
