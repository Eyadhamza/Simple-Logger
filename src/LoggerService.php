<?php

namespace PiSpace\Logger;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use PiSpace\Logger\Core\DTO\ExceptionLoggerDto;
use PiSpace\Logger\Core\DTO\RequestLoggerDto;
use PiSpace\Logger\Core\Jobs\CreateLogJob;
use PiSpace\Logger\Core\Models\ApiLog;
use PiSpace\Logger\Core\Support\LogPayloadSanitizer;

class LoggerService
{
    private ApiLog $log;

    public function __construct(
        RequestLoggerDto $requestLoggerDto,
        private readonly LogPayloadSanitizer $sanitizer,
    ) {
        $this->log = ApiLog::make($requestLoggerDto->toArray());
    }

    public function log(Throwable|Response $loggable): void
    {
        match (true) {
            $loggable instanceof Throwable => $this->logException($loggable),
            $loggable instanceof Response => $this->logResponse($loggable),
        };

        // SECURITY: sanitize once, here, so every driver receives a clean
        // record. Previously sanitization happened inside the LokiDriver only,
        // which meant the database driver wrote raw `Authorization` headers
        // and password fields straight into `api_logs`.
        CreateLogJob::dispatchAfterResponse(
            $this->sanitizer->sanitize($this->log->toArray()),
        );
    }

    public function logLoggableEntity(Model $loggableEntity): void
    {
        $this->log->fill([
            'loggable_id' => $loggableEntity->id,
            'loggable_type' => $loggableEntity->getMorphClass(),
        ]);
    }

    public function logResponse(Response $response): void
    {
        $this->log->fill([
            'response_payload' => json_decode($response->getContent()),
        ]);
    }

    public function logException(Throwable $throwable): void
    {
        $this->log->fill([
            'exception' => ExceptionLoggerDto::make($throwable)->toArray(),
        ]);
    }

    public static function safeCall(Closure $closure): mixed
    {
        return rescue($closure, fn(Throwable $exception) => Log::error('Logger Service Failed!', [
            'exception' => $exception,
        ]), false);
    }
}