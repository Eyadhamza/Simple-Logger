<?php

namespace Zeal\Logger\Core\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use GeneratesUuid;

    protected $fillable = [
        'loggable_id',
        'loggable_type',
        'request_payload',
        'response_payload',
        'headers',
        'endpoint',
        'method',
        'exception',
    ];

    protected $casts = [
        'headers' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'exception' => 'array',
    ];
}
