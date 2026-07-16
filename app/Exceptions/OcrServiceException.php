<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class OcrServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 0,
        private readonly bool $retryable = false,
        private readonly array $contextData = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            $httpStatus,
            $previous,
        );
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}
