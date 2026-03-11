<?php

namespace App\Enums;

enum ImportDatasourceStatus: string
{
    case Processing = 'processing';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Success = 'success';
    case Failed = 'failed';
}
