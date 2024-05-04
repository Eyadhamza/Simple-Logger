<?php

namespace PiSpace\Logger\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PiSpace\Logger\Core\DTO\ExceptionLoggerDto;
use PiSpace\Logger\Core\Models\ApiLog;

class LogExceptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ExceptionLoggerDto $loggerDto, private readonly ApiLog $apiLog)
    {

    }

    public function handle(): void
    {
        $this->apiLog->update([
            'exception' => [
                'message' => $this->loggerDto->message,
                'code' => $this->loggerDto->code,
                'file' => $this->loggerDto->file,
                'line' => $this->loggerDto->line,
                'trace' => $this->loggerDto->trace,
            ],
        ]);
    }
}
