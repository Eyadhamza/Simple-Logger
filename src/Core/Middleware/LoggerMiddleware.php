<?php

namespace PiSpace\Logger\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PiSpace\Logger\LoggerService;

class LoggerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(LoggerService::class)->log($response);
    }
}
