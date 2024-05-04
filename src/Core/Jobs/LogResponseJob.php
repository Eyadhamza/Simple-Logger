<?php

namespace PiSpace\Logger\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PiSpace\Logger\Core\DTO\ResponseLoggerDto;
use PiSpace\Logger\Core\Models\ApiLog;

class LogResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ResponseLoggerDto $loggerDto, private readonly ApiLog $apiLog)
    {

    }

    public function handle(): void
    {
        $this->apiLog->update([
            'response_payload' => $this->loggerDto->responseBody,
        ]);
    }
}
