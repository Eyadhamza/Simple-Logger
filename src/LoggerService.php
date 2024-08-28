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

class LoggerService
{
    private ApiLog $log;

    public function __construct(RequestLoggerDto $requestLoggerDto)
    {
        $this->log = ApiLog::make($requestLoggerDto->toArray());
    }

    public function log(Throwable|Response $loggable): void
    {
        match (true) {
            $loggable instanceof Throwable => $this->logException($loggable),
            $loggable instanceof Response => $this->logResponse($loggable),
        };

        CreateLogJob::dispatchAfterResponse($this->log->toArray());
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