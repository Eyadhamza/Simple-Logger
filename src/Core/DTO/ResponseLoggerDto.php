<?php

namespace PiSpace\Logger\Core\DTO;

class ResponseLoggerDto
{
    /**
     * @param string $responseBody json encoded response
     */
    public function __construct(
        public readonly string $responseBody,
    )
    {
    }
}
