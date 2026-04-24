<?php

namespace PiSpace\Logger\Core\Support;

/**
 * Redacts sensitive keys and truncates oversize fields so a log payload
 * is safe to ship to external systems (Loki, Stackdriver, etc.) without
 * leaking credentials or blowing past line-size limits.
 */
class LogPayloadSanitizer
{
    public function __construct(
        private readonly int   $maxFieldBytes,
        private readonly array $redactHeaders,
        private readonly array $redactBodyKeys,
    ) {
    }

    public function sanitize(array $logData): array
    {
        return [
            'method'           => $logData['method'] ?? null,
            'endpoint'         => $logData['endpoint'] ?? null,
            'loggable_type'    => $logData['loggable_type'] ?? null,
            'loggable_id'      => $logData['loggable_id'] ?? null,
            'headers'          => $this->redact($logData['headers'] ?? [], $this->redactHeaders),
            'request_payload'  => $this->redact($logData['request_payload'] ?? [], $this->redactBodyKeys),
            'response_payload' => $this->truncateAny($logData['response_payload'] ?? null),
            'exception'        => $this->truncateAny($logData['exception'] ?? null),
        ];
    }

    private function redact(mixed $data, array $keys): mixed
    {
        if (!is_array($data)) {
            return $this->truncateAny($data);
        }
        $out = [];
        foreach ($data as $k => $v) {
            if (in_array(strtolower((string) $k), $keys, true)) {
                $out[$k] = '[REDACTED]';
                continue;
            }
            $out[$k] = is_array($v) ? $this->redact($v, $keys) : $v;
        }
        return $this->truncateAny($out);
    }

    private function truncateAny(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $encoded = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($encoded === false || strlen($encoded) <= $this->maxFieldBytes) {
            return $value;
        }
        return [
            '_truncated'      => true,
            '_original_bytes' => strlen($encoded),
            '_preview'        => substr($encoded, 0, $this->maxFieldBytes),
        ];
    }
}
