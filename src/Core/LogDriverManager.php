<?php

namespace PiSpace\Logger\Core;

use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;
use PiSpace\Logger\Core\Contracts\LogDriverInterface;
use PiSpace\Logger\Core\Drivers\DatabaseDriver;
use PiSpace\Logger\Core\Drivers\LokiDriver;
use PiSpace\Logger\Core\Drivers\StackDriver;
use PiSpace\Logger\Core\Support\LogPayloadSanitizer;

class LogDriverManager
{
    /** @var array<string, LogDriverInterface> */
    private array $resolved = [];

    public function __construct(private readonly Repository $config)
    {
    }

    public function default(): LogDriverInterface
    {
        return $this->driver((string) $this->config->get('simple-logger.default', 'database'));
    }

    public function driver(string $name): LogDriverInterface
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $cfg = $this->config->get("simple-logger.drivers.$name");
        if (!is_array($cfg) || !isset($cfg['driver'])) {
            throw new InvalidArgumentException("simple-logger driver [$name] is not configured.");
        }

        return $this->resolved[$name] = match ($cfg['driver']) {
            'database' => new DatabaseDriver(),
            'loki'     => $this->makeLoki($cfg),
            'stack'    => $this->makeStack($cfg),
            'null'     => new class implements LogDriverInterface { public function store(array $d): void {} },
            default    => throw new InvalidArgumentException("Unknown simple-logger driver type [{$cfg['driver']}]"),
        };
    }

    private function makeLoki(array $cfg): LokiDriver
    {
        $sanitizer = new LogPayloadSanitizer(
            maxFieldBytes: (int) ($cfg['max_field_bytes'] ?? 8192),
            redactHeaders: array_map('strtolower', (array) ($cfg['redact_headers'] ?? [])),
            redactBodyKeys: array_map('strtolower', (array) ($cfg['redact_body_keys'] ?? [])),
        );
        return new LokiDriver($sanitizer, $cfg['channel'] ?? null);
    }

    private function makeStack(array $cfg): StackDriver
    {
        $children = [];
        foreach ((array) ($cfg['drivers'] ?? []) as $childName) {
            $children[] = $this->driver((string) $childName);
        }
        return new StackDriver($children);
    }
}
