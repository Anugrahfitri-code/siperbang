<?php

namespace App\Exceptions;

use Exception;

class OcrServiceException extends Exception
{
    protected array $contextData;

    public function __construct(string $message, int $code = 0, array $contextData = [], ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->contextData = $contextData;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}
