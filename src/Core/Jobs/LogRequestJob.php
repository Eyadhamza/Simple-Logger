<?php

namespace PiSpace\Logger\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PiSpace\Logger\Core\DTO\RequestLoggerDto;
use PiSpace\Logger\LoggerService;

class LogRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly RequestLoggerDto $loggerDto)
    {

    }

    public function handle(): void
    {
        app(LoggerService::class)->updateLog([
            'method' => $this->loggerDto->method,
            'endpoint' => $this->loggerDto->endpoint,
            'headers' => $this->loggerDto->headers,
            'request_payload' => $this->loggerDto->requestPayload,
        ]);
    }
}
