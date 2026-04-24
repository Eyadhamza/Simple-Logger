<?php

namespace PiSpace\Logger\Core\Drivers;

use Illuminate\Support\Facades\Log;
use PiSpace\Logger\Core\Contracts\LogDriverInterface;
use PiSpace\Logger\Core\Support\LogPayloadSanitizer;

/**
 * Emits a single structured JSON log line on the configured Laravel log
 * channel. Pair this with a channel that writes to stderr (CloudWatch on
 * Lambda) or a dedicated Monolog Loki handler in your logging config.
 */
class LokiDriver implements LogDriverInterface
{
    public function __construct(
        private readonly LogPayloadSanitizer $sanitizer,
        private readonly ?string             $channel,
    ) {
    }

    public function store(array $logData): void
    {
        $clean = $this->sanitizer->sanitize($logData);
        $status = $this->resolveStatus($clean);
        $hasException = !empty($clean['exception']);

        $level = match (true) {
            $hasException, is_int($status) && $status >= 500 => 'error',
            is_int($status) && $status >= 400                => 'warning',
            default                                          => 'info',
        };

        $message = json_encode(
            ['event' => 'api_log', 'status' => $status] + $clean,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $logger = $this->channel ? Log::channel($this->channel) : Log::getFacadeRoot();
        $logger->log($level, $message);
    }

    private function resolveStatus(array $clean): int|string|null
    {
        $resp = $clean['response_payload'] ?? null;
        if (is_array($resp)) {
            return $resp['status'] ?? $resp['code'] ?? null;
        }
        return null;
    }
}
