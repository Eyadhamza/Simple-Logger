<?php

namespace PiSpace\Logger\Core\DTO;

class ExceptionLoggerDto
{
    public function __construct(
        public readonly string $message,
        public readonly int    $code,
        public readonly string $file,
        public readonly string $line,
        public readonly string $trace,
    )
    {
    }


    public static function make(\Throwable $throwable)
    {
        return new self(
            $throwable->getMessage(),
            $throwable->getCode(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString(),
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->trace,
        ];
    }
}
