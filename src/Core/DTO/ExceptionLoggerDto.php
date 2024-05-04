<?php

namespace Zeal\Logger\Core\DTO;

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
}
