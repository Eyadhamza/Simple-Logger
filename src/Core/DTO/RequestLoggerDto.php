<?php

namespace PiSpace\Logger\Core\DTO;

use Illuminate\Http\Request;

class RequestLoggerDto
{
    public function __construct(
        public readonly string $method,
        public readonly string $endpoint,
        public readonly array  $requestPayload,
        public readonly array  $headers,
    )
    {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->method(),
            $request->path(),
            $request->all(),
            $request->headers->all(),
        );
    }
}
