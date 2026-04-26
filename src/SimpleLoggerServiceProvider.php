<?php

namespace PiSpace\Logger;


use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use PiSpace\Logger\Core\DTO\RequestLoggerDto;
use PiSpace\Logger\Core\LogDriverManager;
use PiSpace\Logger\Core\Middleware\LoggerMiddleware;
use PiSpace\Logger\Core\Support\LogPayloadSanitizer;

class SimpleLoggerServiceProvider extends PackageServiceProvider
{
    public function register()
    {
        // Single redaction policy shared by every driver. Reads top-level
        // `simple-logger.redact.*` and falls back to the legacy per-driver
        // `drivers.loki.redact_*` keys for backward compatibility.
        $this->app->singleton(LogPayloadSanitizer::class, function (Application $app) {
            $config = $app->make(Repository::class);

            $headers = (array) $config->get(
                'simple-logger.redact.headers',
                $config->get('simple-logger.drivers.loki.redact_headers', []),
            );
            $bodyKeys = (array) $config->get(
                'simple-logger.redact.body_keys',
                $config->get('simple-logger.drivers.loki.redact_body_keys', []),
            );
            $maxBytes = (int) $config->get(
                'simple-logger.redact.max_field_bytes',
                $config->get('simple-logger.drivers.loki.max_field_bytes', 8192),
            );

            return new LogPayloadSanitizer(
                maxFieldBytes: $maxBytes,
                redactHeaders: array_map('strtolower', $headers),
                redactBodyKeys: array_map('strtolower', $bodyKeys),
            );
        });

        $this->app->singleton(LoggerService::class, function (Application $app) {
            return new LoggerService(
                RequestLoggerDto::fromRequest($app->make(Request::class)),
                $app->make(LogPayloadSanitizer::class),
            );
        });

        $this->app->singleton(LogDriverManager::class, function (Application $app) {
            return new LogDriverManager(
                $app->make(Repository::class),
                $app->make(LogPayloadSanitizer::class),
            );
        });

        $this->app->singleton(LoggerMiddleware::class);
        parent::register();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('simple-logger')
            ->hasConfigFile()
            ->hasMigration('create_api_logs_table');
    }
}
