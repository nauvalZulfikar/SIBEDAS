<?php

namespace App\Enums;

enum ImportDatasourceStatus: string
{
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
}
