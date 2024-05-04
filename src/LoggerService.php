<?php

namespace Zeal\Logger;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Zeal\Logger\Core\DTO\ExceptionLoggerDto;
use Zeal\Logger\Core\DTO\RequestLoggerDto;
use Zeal\Logger\Core\DTO\ResponseLoggerDto;
use Zeal\Logger\Core\Jobs\LogExceptionJob;
use Zeal\Logger\Core\Jobs\LogLoggableEntityJob;
use Zeal\Logger\Core\Jobs\LogResponseJob;
use Zeal\Logger\Core\Models\ApiLog;

class LoggerService
{
    private static ApiLog $log;

    public function __construct(private readonly RequestLoggerDto $requestLoggerDto)
    {
    }

    public function createInitialLog(): void
    {
        self::safeCall(fn() => self::$log = ApiLog::create([
            'method' => $this->requestLoggerDto->method,
            'endpoint' => $this->requestLoggerDto->endpoint,
            'headers' => $this->requestLoggerDto->headers,
            'request_payload' => $this->requestLoggerDto->requestPayload,
        ]));
    }

    public static function log(Throwable|Response $loggable): void
    {
        $callback = match (true) {
            $loggable instanceof Throwable => fn() => self::logException($loggable),
            $loggable instanceof Response => fn() => self::logResponse($loggable),
        };

        self::safeCall($callback);
    }

    public static function logLoggableEntity(Model $loggableEntity): void
    {
        LogLoggableEntityJob::dispatchAfterResponse($loggableEntity, self::$log);
    }

    private static function logResponse(Response $response): void
    {
        if ($response->isEmpty()) {
            return;
        }

        LogResponseJob::dispatchAfterResponse(new ResponseLoggerDto($response->getContent()), self::$log);
    }

    private static function logException(Throwable $throwable): void
    {
        LogExceptionJob::dispatchAfterResponse(
            new ExceptionLoggerDto(
                $throwable->getMessage(),
                $throwable->getCode(),
                $throwable->getFile(),
                $throwable->getLine(),
                $throwable->getTraceAsString(),
            ), self::$log);
    }

    public static function safeCall(Closure $closure): void
    {
        rescue($closure, fn(Throwable $exception) => Log::error('Logger Service Failed!', [
            'exception' => $exception,
        ]), false);
    }
}
