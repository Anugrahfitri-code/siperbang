<?php

namespace App\Exceptions;

use Exception;

class OcrServiceClientException extends Exception
{
    protected $isRetryable;

    public function __construct(string $message, int $code = 0, \Throwable $previous = null, bool $isRetryable = false)
    {
        parent::__construct($message, $code, $previous);
        $this->isRetryable = $isRetryable;
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }
}
