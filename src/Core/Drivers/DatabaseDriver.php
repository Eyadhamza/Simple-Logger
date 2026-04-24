<?php

namespace PiSpace\Logger\Core\Drivers;

use PiSpace\Logger\Core\Contracts\LogDriverInterface;
use PiSpace\Logger\Core\Models\ApiLog;

class DatabaseDriver implements LogDriverInterface
{
    public function store(array $logData): void
    {
        ApiLog::create($logData);
    }
}
