<?php

namespace PiSpace\Logger\Core\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use PiSpace\Logger\Core\LogDriverManager;

/**
 * Dispatched via `dispatchAfterResponse` from LoggerService, so `handle()`
 * runs in the same PHP process AFTER the HTTP response has been sent to
 * the client. No queue worker required.
 *
 * If you need retry-on-failure or cross-process isolation, wrap or subclass
 * this job and add `implements ShouldQueue`.
 */
class CreateLogJob
{
    use Dispatchable;

    public function __construct(private readonly array $logData)
    {
    }

    public function handle(LogDriverManager $drivers): void
    {
        $drivers->default()->store($this->logData);
    }
}
