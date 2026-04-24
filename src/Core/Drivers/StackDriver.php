<?php

namespace PiSpace\Logger\Core\Drivers;

use PiSpace\Logger\Core\Contracts\LogDriverInterface;

/**
 * Fans out a log record to multiple drivers. Failure in any child driver
 * is caught so one broken sink never blocks the others.
 */
class StackDriver implements LogDriverInterface
{
    /** @param LogDriverInterface[] $drivers */
    public function __construct(private readonly array $drivers)
    {
    }

    public function store(array $logData): void
    {
        foreach ($this->drivers as $driver) {
            try {
                $driver->store($logData);
            } catch (\Throwable $e) {
                // Log to default channel and continue with remaining drivers.
                \Illuminate\Support\Facades\Log::error(
                    'simple-logger driver failed: ' . $driver::class . ' - ' . $e->getMessage(),
                );
            }
        }
    }
}
