<?php

namespace PiSpace\Logger;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use PiSpace\Logger\Core\DTO\ExceptionLoggerDto;
use PiSpace\Logger\Core\DTO\RequestLoggerDto;
use PiSpace\Logger\Core\DTO\ResponseLoggerDto;
use PiSpace\Logger\Core\Jobs\LogExceptionJob;
use PiSpace\Logger\Core\Jobs\LogLoggableEntityJob;
use PiSpace\Logger\Core\Jobs\LogResponseJob;
use PiSpace\Logger\Core\Models\ApiLog;

class LoggerService
{
    private ?ApiLog $log = null;

    public function __construct(private readonly RequestLoggerDto $requestLoggerDto)
    {
    }

    public function createInitialLog(): void
    {
        self::safeCall(fn() => $this->log = ApiLog::create([
            'method' => $this->requestLoggerDto->method,
            'endpoint' => $this->requestLoggerDto->endpoint,
            'headers' => $this->requestLoggerDto->headers,
            'request_payload' => $this->requestLoggerDto->requestPayload,
        ]));
    }

    public function log(Throwable|Response $loggable): void
    {
        $callback = match (true) {
            $loggable instanceof Throwable => fn() => self::logException($loggable),
            $loggable instanceof Response => fn() => self::logResponse($loggable),
        };

        self::safeCall($callback);
    }

    public function logLoggableEntity(Model $loggableEntity): void
    {
        LogLoggableEntityJob::dispatchAfterResponse($loggableEntity, $this->log);
    }

    private function logResponse(Response $response): void
    {
        if ($response->isEmpty()) {
            return;
        }

        LogResponseJob::dispatchAfterResponse(new ResponseLoggerDto($response->getContent()), $this->log);
    }

    private function logException(Throwable $throwable): void
    {
        LogExceptionJob::dispatchAfterResponse(
            new ExceptionLoggerDto(
                $throwable->getMessage(),
                $throwable->getCode(),
                $throwable->getFile(),
                $throwable->getLine(),
                $throwable->getTraceAsString(),
            ),
            $this->log
        );
    }

    public static function safeCall(Closure $closure): void
    {
        rescue($closure, fn(Throwable $exception) => Log::error('Logger Service Failed!', [
            'exception' => $exception,
        ]), false);
    }
}
