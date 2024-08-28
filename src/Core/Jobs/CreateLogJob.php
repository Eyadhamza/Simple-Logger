<?php

namespace PiSpace\Logger\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use PiSpace\Logger\Core\Models\ApiLog;

class CreateLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private readonly array $logData)
    {

    }

    public function handle(): void
    {
        ApiLog::create($this->logData);
    }
}