<?php

namespace PiSpace\Logger\Core\Contracts;

interface LogDriverInterface
{
    public function store(array $logData): void;
}
